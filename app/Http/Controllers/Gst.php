<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GstModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Gst extends Controller
{
    //
     public function index()
    {
        $page_title = 'Gst ';
        $page_name = 'Gst';
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company/master/gst/gst', compact('page_title', 'page_name'));
    }

     public function storeOrUpdate(Request $request)
    {
        try {
            $ids = $request->input('gst_id'); // could be array or single value or null
            $names = $request->input('gst_no'); // array of names

            if (empty($names) || !is_array($names)) {
               // return response()->json(['status' => 'error', 'message' => 'At least one Gst No is required.'], 422);
                 return response()->json([
                    'errors' => [
                        'gst_no.0' => ['At least one Gst No is required.']
                    ]
                ], 422);
            }

            if (!is_array($ids)) {
                $ids = $ids ? [$ids] : [];
            }

            $updated = false;
            $created = false;

            foreach ($names as $index => $name) {
                if (empty(trim($name))) {
                    //return response()->json(['status' => 'error', 'message' => "Gst no at position $index is required."], 422);
                     return response()->json([
                        'errors' => [
                            "gst_no.$index" => ["Gst No at position $index is required."]
                        ]
                    ], 422);
                }

                $id = $ids[$index] ?? null;

                $exists = GstModel::where('gst_no', $name)->where('status',0)
                    ->when($id, fn($q) => $q->where('gst_id', '!=', $id))
                    ->exists();

                if ($exists) {
                    //return response()->json(['status' => 'error', 'message' => "This status '$name' already exists."], 422);
                    return response()->json([
                        'errors' => [
                            "gst_no.$index" => ["This Gst No '$name' already exists."]
                        ]
                    ], 422);
                }

                if ($id) {
                    $item = GstModel::find($id);
                    if ($item) {
                        $item->update([
                            'gst_no' => $name,
                            'updated_by' => getUpdatedBy(),
                            'updated_at' => now()
                        ]);
                        $updated = true;
                    }
                } else {
                    GstModel::create([
                        'gst_no' => $name,
                        'status' => 0,
                        'created_by' => getCreatedBy(),
                        'created_at' => now()
                    ]);
                    $created = true;
                }
            }
            DB::commit();
            if ($updated && $created) {
                $message = 'Gst nos updated and added successfully.';
            } elseif ($updated) {
                $message = 'Gst no updated successfully.';
            } elseif ($created) {
                $message = 'New Gst nos added successfully.';
            } else {
                $message = 'No changes were made.';
            }

            return response()->json(['status' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
              DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

   public function getList()
{
    try {
        $permissions = checkPermissions(get_index_route($this));
        $canUpdate = $permissions['canUpdate'] ?? false;
        $canDelete = $permissions['canDelete'] ?? false;

        $query = GstModel::select(['gst_id', 'gst_no', 'created_at'])
            ->where('status', 0)
            ->orderBy('created_at', 'desc');

        return DataTables::of($query)
            ->addColumn('action', function ($row) use ($canUpdate, $canDelete) {
                $buttons = '';

                if ($canUpdate) {
                    $buttons .= '<button class="btn btn-sm btn-primary edit-group" data-id="' . $row->gst_id . '">
                                    <i class="fas fa-edit"></i> Edit
                                </button>';
                }

                if ($canDelete) {
                    $buttons .= '<button class="btn btn-sm btn-danger delete-group" data-id="' . $row->gst_id . '">
                                    <i class="fas fa-trash"></i> Delete
                                </button>';
                }

                return $buttons;
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}
public function edit($id)
    {
        try {



            $group = GstModel::where('gst_id', $id)
                ->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gst no not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching Gst No: ' . $e->getMessage()
            ], 500);
        }
    }
public function destroy(Request $request)
    {
        $gst_id = $request->gst_id;

        try {
            DB::beginTransaction();

            $stockGroup = GstModel::find($gst_id);
            if (!$stockGroup) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gst no not found.'
                ]);
            }

            // Just update status to 1
            $stockGroup->update([
                'status' => 1,
                'updated_at' => now(),
                'updated_by' => getUpdatedBy()
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Gst no marked as inactive.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Gst no: ' . $e->getMessage()
            ]);
        }
    }


}
