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
use Yajra\DataTables\DataTables;

class Documents extends Controller
{
    //
    public function index()
    {
        $page_title = 'Company Document';
        $page_name = 'Company Document';
        $category = DB::table('document_category')->get();
        return view('company/documents/document', compact('page_title', 'page_name', 'category'));
    }
    public function category_store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|array',
            'category_name.*' => 'required|string|max:255'
        ]);

        $categories = [];
        foreach ($request->category_name as $category) {
            $categories[] = [
                'd_category' => $category,
                'created_by' => getCreatedBy(),
                'updated_by' => getCreatedBy(),
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        Document_categoryModel::insert($categories);

        return response()->json([
            'status' => true,
            'message' => count($categories) . ' categories added successfully'
        ]);
    }
    public function get_document_category()
    {
        $categories = Document_categoryModel::where('status', 0)
            ->withCount('documents')
            ->orderBy('d_category', 'asc')
            ->get();

        foreach ($categories as $category) {
            $category->encrypted_id = encrypt($category->dc_id);
        }

        return response()->json([
            'status' => true,
            'categories' => $categories
        ]);
    }
    public function category_update(Request $request)
    {
        $validated = $request->validate([
            'encrypted_id' => 'required',
            'd_category' => 'required|string|max:255'
        ]);

        try {
            $id = decrypt($validated['encrypted_id']);
            $category = Document_categoryModel::findOrFail($id);

            $category->update([
                'd_category' => $validated['d_category'],
                'updated_at' => now(),
                'updated_by' => getUpdatedBy(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update category'
            ], 500);
        }
    }
    public function category_delete(Request $request)
    {
        $dc_id = decrypt($request->id);

        DB::table('document_mngt')->where('dc_id', $dc_id)->delete();
        DB::table('document_mngt_history')->where('dc_id', $dc_id)->delete();

        $category = Document_categoryModel::find($dc_id);
        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    public function document_list($id, Request $request)
    {
        $dc_id = decrypt($id);

        $highlightDmId = null;
        if ($request->has('dm_id')) {
            $highlightDmId = decrypt($request->dm_id);
        }


        $dc_name = get_document_name($dc_id);
        $page_title = $dc_name . ' Document';
        $page_name = $dc_name . ' Document';

        return view('company/documents/document_list', compact(
            'page_title',
            'page_name',
            'dc_name',
            'id',
            'highlightDmId'
        ));
    }

    public function documents_list_category_wise(Request $request)
    {

        $query = Document_mngtModel::select([
            'dm_id',
            'dc_id',
            'certificate',
            'expiry_date',
            'is_expiry_date',
            'file_name',
            'batch_id'
        ]);

        if ($request->has('dc_id') && !empty($request->dc_id)) {
            $query->where('dc_id', decrypt($request->dc_id));
        }
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('certificate', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('expiry_date', 'LIKE', "%{$searchTerm}%");
            });
        }


        $documents = $query->get()->map(function ($row) {
            // Check if history exists for this batch_id
            $hasHistory = DB::table('document_mngt_history')
                ->where('batch_id', $row->batch_id)
                ->exists();

            // Process file_name (same as before)
            $file_name_html = 'N/A';
            if ($row->file_name) {
                $files = explode(',', $row->file_name);
                $html = '';

                foreach ($files as $file) {
                    $img_path = config('app.img_path');
                    $file = trim($file);
                    if (empty($file)) continue;

                    $filePath = $img_path . '/company_doc/' . $file;
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                        $html .= '<a href="' . $filePath . '" target="_blank" data-bs-toggle="tooltip" title="View Image">
                    <img src="' . $filePath . '" style="max-width:50px;max-height:50px;margin:3px;border-radius:4px;">
                </a>';
                    } elseif ($ext === 'pdf') {
                        $html .= '<a href="' . $filePath . '" target="_blank" data-bs-toggle="tooltip" title="View PDF" style="margin:3px;">
                    <i class="las la-file-pdf text-danger" style="font-size:48px;"></i>
                </a>';
                    }
                }

                $file_name_html = $html ?: 'N/A';
            }

            // Process expiry status (same as before)
            $expiry_status = 'N/A';
            if ($row->is_expiry_date) {
                $expiryDate = Carbon::parse($row->expiry_date);
                $now = now();

                if ($expiryDate->isPast()) {
                    $expiry_status = '<span class="badge bg-danger">Expired</span>';
                } else {
                    $remainingDays = $now->diffInDays($expiryDate, false);
                    $remainingDays = $remainingDays > 0 ? ceil($remainingDays) : 0;

                    if ($remainingDays <= 30) {
                        $expiry_status = '<span class="badge bg-warning">Expiring Soon (' . $remainingDays . ' days left)</span>';
                    } else {
                        $expiry_status = '<span class="badge bg-success">Valid (' . $remainingDays . ' days left)</span>';
                    }
                }
            }


            // Process action buttons - Add history button if history exists
            $action = '<div class="d-flex">';
            if ($hasHistory) {
                $action .= '<button class="btn btn-sm btn-outline-primary me-1 view-history" data-batch-id="' . $row->batch_id . '" data-bs-toggle="tooltip" title="View History">
                <i class="bx bx-history"></i>
            </button>';
            }
            $action .= '<button class="btn btn-sm btn-outline-info me-1 edit-document" data-id="' . $row->dm_id . '" data-bs-toggle="tooltip" title="Edit">
            <i class="bx bx-edit"></i>
        </button>';


            $action .= '<button class="btn btn-sm btn-outline-danger me-1 delete-document" data-id="' . $row->dm_id . '" data-bs-toggle="tooltip" title="Delete">
            <i class="bx bx-trash"></i>
        </button>';

            $action .= '</div>';

            return [
                'id' => $row->dm_id,
                'dm_id' => $row->dm_id,
                'certificate' => $row->certificate,
                'expiry_date' => $row->is_expiry_date ? ($row->expiry_date ?? 'N/A') : 'N/A',
                'file_name' => $file_name_html,
                'expiry_status' => $expiry_status,
                'action' => $action
            ];
        });

        return response()->json($documents);
    }
    public function get_document_edit(Request $request)
    {
        $request->validate([
            'document_id' => 'required|integer'
        ]);

        $document = Document_mngtModel::find($request->document_id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $document
        ]);
    }


    public function save_document(Request $request)
    {
        $request->validate([
            'dc_id' => 'required',
            'certificate' => 'required|string|max:255',
            'is_expiry_date' => 'required|boolean',
            'expiry_date' => 'nullable|date',
            'file' => 'nullable',
            'file.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'document_id' => 'nullable|integer',
            'batch_id' => 'nullable|string'
        ]);

        $fileNames = [];
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $fileName = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/uploads/company_doc'), $fileName);
                $fileNames[] = $fileName;
            }
        }

        if ($request->document_id) {
            // Edit existing document
            $document = Document_mngtModel::findOrFail($request->document_id);
            $batch_id = $request->batch_id ?: $document->batch_id;

            // Prepare the new expiry date value
            $newExpiryDate = $request->is_expiry_date && $request->expiry_date
                ? Carbon::parse($request->expiry_date)->format('Y-m-d')
                : null;

            // Check for specific changes that require history entry
            $expiryDateChanged = $document->expiry_date != $newExpiryDate;
            $fileChanged = !empty($fileNames) && $document->file_name != implode(',', $fileNames);

            $changesMade = $expiryDateChanged || $fileChanged;

            if ($changesMade) {
                // Create history entry only if expiry date or files changed
                DB::table('document_mngt_history')->insert([
                    'batch_id' => $batch_id,
                    'dc_id' => $document->dc_id,
                    'certificate' => $document->certificate,
                    'expiry_date' => $document->expiry_date,
                    'is_expiry_date' => $document->is_expiry_date,
                    'file_name' => $document->file_name,
                    'created_by' => $document->created_by,
                    'status' => $document->status,
                    'action' => 'updated',
                    'created_at' => now(),
                    'change_reason' => $this->getChangeReason($expiryDateChanged, $fileChanged),
                ]);
            }
            $updateData = [
                'certificate' => $request->certificate,
                'expiry_date' => $newExpiryDate,
                'is_expiry_date' => $request->is_expiry_date,
            ];

            if (!empty($fileNames)) {
                $updateData['file_name'] = implode(',', $fileNames);
            }

            $document->update($updateData);

            return response()->json([
                'success' => true,
                'message' => $changesMade ? 'Document updated with history' : 'Document updated (no significant changes)',
                'data' => $document,
                'changes' => [
                    'expiry_date' => $expiryDateChanged,
                    'file' => $fileChanged
                ]
            ]);
        } else {
            // Create new document
            $batch_id = uniqid();

            $document = Document_mngtModel::create([
                'batch_id' => $batch_id,
                'dc_id' => decrypt($request->dc_id),
                'certificate' => $request->certificate,
                'expiry_date' => $request->is_expiry_date && $request->expiry_date
                    ? Carbon::parse($request->expiry_date)->format('Y-m-d')
                    : null,
                'is_expiry_date' => $request->is_expiry_date,
                'file_name' => implode(',', $fileNames),
                'created_by' => getCreatedBy(),
                'status' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document saved successfully',
                'data' => $document
            ]);
        }
    }

    private function getChangeReason(bool $expiryDateChanged, bool $fileChanged): string
    {
        if ($expiryDateChanged && $fileChanged) {
            return 'Both expiry date and file were updated';
        }
        if ($expiryDateChanged) {
            return 'Expiry date was updated';
        }
        if ($fileChanged) {
            return 'File was updated';
        }
        return 'No significant changes';
    }
    public function get_document_history(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|string'
        ]);

        $history = DB::table('document_mngt_history')
            ->where('batch_id', $request->batch_id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                // Process file names if they exist
                $filesHtml = 'N/A';
                if ($item->file_name) {
                    $files = explode(',', $item->file_name);
                    $html = '';

                    foreach ($files as $file) {
                        $file = trim($file);
                        if (empty($file)) continue;

                        $filePath = asset('assets/uploads/company_doc/' . $file);
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                            $html .= '<a href="' . $filePath . '" target="_blank" class="d-block mb-2">
                            <img src="' . $filePath . '" style="max-width:100px;max-height:100px;border-radius:4px;">
                        </a>';
                        } elseif ($ext === 'pdf') {
                            $html .= '<a href="' . $filePath . '" target="_blank" class="d-block mb-2">
                            <i class="fas fa-file-pdf fa-2x text-danger"></i> PDF File
                        </a>';
                        }
                    }

                    $filesHtml = $html ?: 'N/A';
                }

                return [
                    'certificate' => $item->certificate,
                    'expiry_date' => $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('d-M-Y') : 'N/A',
                    'is_expiry_date' => $item->is_expiry_date ? 'Yes' : 'No',
                    'file_name' => $filesHtml,
                    'action' => ucfirst($item->action),
                    'changed_at' => \Carbon\Carbon::parse($item->created_at)->format('d-M-Y h:i A'),
                    'changed_by' => get_createdby_name($item->created_by) ?? 'System'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}
