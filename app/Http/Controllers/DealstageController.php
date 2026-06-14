<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DealStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class DealstageController extends Controller
{
    //
    public function index()
    {
        $page_title = 'Deal Stage';
        $page_name = 'Deal Stage';

        //return view('countries.index', compact('page_title', 'page_name'));
        return view('company/master/dealstage/dealstage', compact('page_title', 'page_name'));
    }

    public function getList()
{
    try {
        $permissions = checkPermissions(get_index_route($this));
        $canUpdate = $permissions['canUpdate'] ?? false;
        $canDelete = $permissions['canDelete'] ?? false;

        $query = DealStage::select(['id', 'stage_name'])
            ->where('status', 0)
            ->orderBy('id', 'desc');

        return DataTables::of($query)
            ->addColumn('action', function ($row) use ($canUpdate, $canDelete) {
                $buttons = '';

                if ($canUpdate) {
                    $buttons .= '<button class="btn btn-sm btn-primary edit-group" data-id="' . $row->id . '">
                                    <i class="fas fa-edit"></i> Edit
                                </button>';
                }

                if ($canDelete) {
                    $buttons .= '<button class="btn btn-sm btn-danger delete-group" data-id="' . $row->id . '">
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
            $group = DealStage::where('id', $id)->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dealstage not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $group  // $group->is_service will be included automatically
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching Dealstage: ' . $e->getMessage()
            ], 500);
        }
    }
    public function storeOrUpdate(Request $request)
    {
        try {
            $ids = $request->input('id'); // could be array or single value or null
            $stagenames = $request->input('stage_name'); // array of stagenames
            $colors = $request->input('color', []); // array of is_service, default empty

            if (empty($stagenames) || !is_array($stagenames)) {
               // return response()->json(['status' => 'error', 'message' => 'At least one Category is required.'], 422);
                 return response()->json([
                    'errors' => [
                        'stage_name.0' => ['At least one Deal-stage Name is required.']
                    ]
                ], 422);
            }

            if (!is_array($ids)) {
                $ids = $ids ? [$ids] : [];
            }

            $updated = false;
            $created = false;

            foreach ($stagenames as $index => $name) {
                if (empty(trim($name))) {
                    //return response()->json(['status' => 'error', 'message' => "Dealstage at position $index is required."], 422);
                     return response()->json([
                        'errors' => [
                            "stage_name.$index" => ["Dealstage at position $index is required."]
                        ]
                    ], 422);
                }

                $id = $ids[$index] ?? null;
                $color = $colors[$index] ?? null;

                $exists = DealStage::where('stage_name', $name)
                    ->when($id, fn($q) => $q->where('id', '!=', $id))
                    ->exists();

                if ($exists) {
                    //return response()->json(['status' => 'error', 'message' => "This Stage Name '$name' already exists."], 422);
                     return response()->json([
                        'errors' => [
                            "stage_name.$index" => ["This Dealstage '$name' already exists."]
                        ]
                    ], 422);
                }

                if ($id) {
                    $item = DealStage::find($id);
                    if ($item) {
                        $item->update([
                            'stage_name' => $name,
                            'color' => $color

                        ]);
                        $updated = true;
                    }
                } else {
                    DealStage::create([
                        'stage_name' => $name,
                        'color' => $color,
                        'status' => 0

                    ]);
                    $created = true;
                }
            }

            if ($updated && $created) {
                $message = 'Deal Stage updated and added successfully.';
            } elseif ($updated) {
                $message = 'Deal Stage updated successfully.';
            } elseif ($created) {
                $message = 'New Deal Stage added successfully.';
            } else {
                $message = 'No changes were made.';
            }

            return response()->json(['status' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    public function destroy(Request $request)
    {
        $id = $request->id;

        try {
            DB::beginTransaction();

            $dealStage = DealStage::find($id);
            if (!$dealStage) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dealstage not found.'
                ]);
            }

            $protectedNames = ['Closed Won', 'Closed Lost'];
            if (in_array($dealStage->stage_name, $protectedNames)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "You cannot delete the stage '{$dealStage->stage_name}'."
                ]);
            }

            $dealStage->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Dealstage deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Dealstage: ' . $e->getMessage()
            ]);
        }
    }
}
