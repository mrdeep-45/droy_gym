<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitModel;
use App\Models\Stock_category_Model;
use App\Models\Document_mngtModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Document_categoryModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class CompanyDocument extends Controller
{
    //
    public function index()
    {
        $page_title = 'Company Dashboard';
        $page_name = 'Company Dashboard';
        $category = DB::table('document_category')->get();
        return view('company/document', compact('page_title', 'page_name', 'category'));
    }


    public function store_document(Request $request)
    {
        $request->validate([
            'dc_id' => 'required|exists:document_category,dc_id',
            'certificate' => 'required|string|max:255',
            'has_expiry_date' => 'sometimes|boolean',
            'expiry_date' => 'required_if:has_expiry_date,true|date|after_or_equal:today',
            'file_name' => 'required|array',
            'file_name.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240'
        ]);

        try {
            $companyId = Session::get('company_id');
            $destinationPath = public_path('assets/uploads/company_doc');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $filenames = [];

            foreach ($request->file('file_name') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);
                $filenames[] = $filename;
            }

            $commaSeparatedFilenames = implode(',', $filenames);

            Document_mngtModel::create([
                'category' => $request->dc_id,
                'certificate' => $request->certificate,
                'expiry_date' => $request->has_expiry_date ? $request->expiry_date : null,
                'file_name' => $commaSeparatedFilenames,
                // 'file_path' => 'assets/uploads/company_doc/',
                'created_by' => getCreatedBy(),
                'company_id' => $companyId,
                'status' => 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Uploaded successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error uploading documents: ' . $e->getMessage()
            ], 500);
        }
    }
    public function groupedDocuments()
    {
        try {
            $documents = Document_mngtModel::with('categoryRelation')
                ->where('status', 0)
                ->orderBy('dm_id', 'desc')
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->dm_id,
                        'category_id' => $doc->category,
                        'category_name' => $doc->categoryRelation->d_category ?? 'N/A',
                        'certificate' => $doc->certificate,
                        'file_name' => $doc->file_name,
                        // 'file_path' => $doc->file_path,
                        'created_at' => $doc->created_at,
                        'expiry_date' => $doc->expiry_date ? Carbon::parse($doc->expiry_date)->format('d M Y') : null,
                    ];
                });

            return response()->json($documents);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $document = Document_mngtModel::findOrFail($id);

            // Delete associated files
            if ($document->file_name) {
                $filenames = explode(',', $document->file_name);
                foreach ($filenames as $filename) {
                    $path = public_path('assets/uploads/company_doc/' . trim($filename));
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }

            $document->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete document: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getExpiringDocuments()
    {
        $today = Carbon::today();
        $oneMonthLater = $today->copy()->addMonth();
        $oneWeekLater = $today->copy()->addWeek();

        $documents = Document_mngtModel::with('categoryRelation')
            ->whereBetween('expiry_date', [$today, $oneMonthLater])
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function ($item, $key) use ($today, $oneWeekLater) {
                $expiryDate = Carbon::parse($item->expiry_date);

                // Determine status based on dates
                $status = 'Expiring';
                $statusClass = 'warning'; // default

                if ($expiryDate->isSameDay($today)) {
                    $status = 'Expires Today';
                    $statusClass = 'danger';
                } elseif ($expiryDate->isPast()) {
                    $status = 'Expired';
                    $statusClass = 'danger';
                } elseif ($expiryDate->between($today, $oneWeekLater)) {
                    $status = 'Expiring Soon';
                    $statusClass = 'warning';
                }

                return [
                    'sr_no' => $key + 1,
                    'category' => $item->categoryRelation->d_catagory ?? 'N/A',
                    'certificate' => $item->certificate,
                    'expiry_date' => $expiryDate->format('d M y'),
                    'status' => $status,
                    'status_class' => $statusClass,
                    'raw_date' => $expiryDate->format('Y-m-d') // for sorting if needed
                ];
            });

        return response()->json([
            'data' => $documents,
            'count' => count($documents)
        ]);
    }

    public function edit($id)
    {
        try {
            $doc = Document_mngtModel::with('categoryRelation')->findOrFail($id);

            return response()->json([
                'id' => $doc->dm_id,
                'dc_id' => $doc->category,
                'certificate' => $doc->certificate,
                'expiry_date' => $doc->expiry_date,
                'file_name' => $doc->file_name,
                // 'file_path' => $doc->file_path,
                // 'file_path' => $doc->file_path ?? 'assets/uploads/company_doc/',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found: ' . $e->getMessage()
            ], 404);
        }
    }


    // public function update_document(Request $request, $id)
    // {
    //     $request->validate([
    //         'dc_id' => 'required|exists:document_category,dc_id',
    //         'certificate' => 'required|string|max:255',
    //         'has_expiry_date' => 'sometimes|boolean',
    //         'expiry_date' => 'required_if:has_expiry_date,true|date|after_or_equal:today',

    //         'file_name.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
    //     ]);

    //     try {
    //         $document = Document_mngtModel::findOrFail($id);
    //         $destinationPath = public_path('assets/uploads/company_doc');

    //         // \Log::info('Starting document update', ['document_id' => $id]);
    //         // \Log::info('Request data:', $request->except(['file_name', 'removed_files']));

    //         // Handle removed files
    //         if ($request->has('removed_files')) {
    //             // \Log::info('Files to remove:', $request->removed_files);

    //             $existingFiles = $document->file_name ? explode(',', $document->file_name) : [];
    //             // \Log::info('Existing files before removal:', $existingFiles);

    //             $remainingFiles = array_diff($existingFiles, $request->removed_files);
    //             // \Log::info('Files after removal:', $remainingFiles);

    //             // Delete from storage
    //             foreach ($request->removed_files as $fileToDelete) {
    //                 $filePath = public_path("assets/uploads/company_doc/{$fileToDelete}");
    //                 if (file_exists($filePath)) {
    //                     // \Log::info("Deleting file: {$filePath}");
    //                     unlink($filePath);
    //                 } else {
    //                     // \Log::warning("File not found: {$filePath}");
    //                 }
    //             }

    //             $document->file_name = !empty($remainingFiles) ? implode(',', $remainingFiles) : null;
    //         }

    //         // Handle new file uploads
    //         if ($request->hasFile('file_name')) {
    //             $filenames = [];

    //             foreach ($request->file('file_name') as $file) {
    //                 $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    //                 $file->move($destinationPath, $filename);
    //                 $filenames[] = $filename;
    //                 // \Log::info("Uploaded new file: {$filename}");
    //             }

    //             $existingFiles = $document->file_name ? explode(',', $document->file_name) : [];
    //             $document->file_name = implode(',', array_merge($existingFiles, $filenames));
    //         }

    //         // \Log::info('Final file_name:', [$document->file_name]);

    //         // Update other fields
    //         $document->category = $request->dc_id;
    //         $document->certificate = $request->certificate;
    //         $document->expiry_date = $request->has_expiry_date ? $request->expiry_date : null;
    //         $document->updated_by = getUpdatedBy();
    //         $document->save();

    //         // \Log::info('Document updated successfully');

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Document updated successfully.'
    //         ]);
    //     } catch (\Exception $e) {
    //         // \Log::error('Error updating document: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error updating document: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function update_document(Request $request, $id)
    {
        $request->validate([
            'dc_id' => 'required|exists:document_category,dc_id',
            'certificate' => 'required|string|max:255',
            'has_expiry_date' => 'sometimes|boolean',
            'expiry_date' => 'required_if:has_expiry_date,true|date|after_or_equal:today',
            'file_name.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
        ]);

        try {
            $document = Document_mngtModel::findOrFail($id);
            $destinationPath = public_path('assets/uploads/company_doc');

            // Handle removed files
            if ($request->has('removed_files')) {
                $existingFiles = $document->file_name ? explode(',', $document->file_name) : [];
                $remainingFiles = array_diff($existingFiles, $request->removed_files);

                // Delete from storage
                foreach ($request->removed_files as $fileToDelete) {
                    $filePath = public_path("assets/uploads/company_doc/{$fileToDelete}");
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                $document->file_name = !empty($remainingFiles) ? implode(',', $remainingFiles) : null;
            }

            // Handle new file uploads (optional)
            $filenames = [];
            if ($request->hasFile('file_name')) {
                foreach ($request->file('file_name') as $file) {
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move($destinationPath, $filename);
                    $filenames[] = $filename;
                }
            }

            // Merge existing files (if not removed) with new files
            $existingFiles = $document->file_name ? explode(',', $document->file_name) : [];
            $allFiles = array_merge($existingFiles, $filenames);

            // Only update file_name if there are files (either existing remaining or new ones uploaded)
            if (!empty($allFiles)) {
                $document->file_name = implode(',', $allFiles);
            } else {
                // If all files were removed and no new ones uploaded
                $document->file_name = null;
            }

            // Update other fields
            $document->update([
                'category' => $request->dc_id,
                'certificate' => $request->certificate,
                'expiry_date' => $request->has_expiry_date ? $request->expiry_date : null,
                'updated_by' => getUpdatedBy()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Document updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating document: ' . $e->getMessage()
            ], 500);
        }
    }
}
