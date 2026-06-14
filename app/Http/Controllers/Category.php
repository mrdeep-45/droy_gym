<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Category extends Controller
{
    //
     public function index()
    {
        $page_title = 'Category';
        $page_name = 'Category';
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company/master/category/category', compact('page_title', 'page_name'));
    }
   public function storeOrUpdate(Request $request)
{
    try {
        $ids = $request->input('id'); // could be array or single value or null
        $names = $request->input('name'); // array of names
        $isServices = $request->input('is_service', []); // array of is_service, default empty

        if (empty($names) || !is_array($names)) {
           // return response()->json(['status' => 'error', 'message' => 'At least one Category is required.'], 422);
             return response()->json([
                    'errors' => [
                        'name.0' => ['At least one Category is required.']
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
               // return response()->json(['status' => 'error', 'message' => "Category at position $index is required."], 422);
                 return response()->json([
                        'errors' => [
                            "name.$index" => ["Category at position $index is required."]
                        ]
                    ], 422);
            }

            $id = $ids[$index] ?? null;
            $isService = $isServices[$index] ?? 0;

            $exists = CategoryModel::where('name', $name)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($exists) {
               // return response()->json(['status' => 'error', 'message' => "This Category '$name' already exists."], 422);
                return response()->json([
                        'errors' => [
                            "name.$index" => ["This Category '$name' already exists."]
                        ]
                    ], 422);
            }

            if ($id) {
                $item = CategoryModel::find($id);
                if ($item) {
                    $item->update([
                        'name' => $name,
                        'is_service' => $isService,
                        'updated_by' => getUpdatedBy(),
                        'updated_at' => now()
                    ]);
                    $updated = true;
                }
            } else {
                CategoryModel::create([
                    'name' => $name,
                    'is_service' => $isService,
                    'status' => 1,
                    'created_by' => getCreatedBy(),
                    'created_at' => now()
                ]);
                $created = true;
            }
        }

        if ($updated && $created) {
            $message = 'Category updated and added successfully.';
        } elseif ($updated) {
            $message = 'Category updated successfully.';
        } elseif ($created) {
            $message = 'New Category added successfully.';
        } else {
            $message = 'No changes were made.';
        }

        return response()->json(['status' => 'success', 'message' => $message]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

    public function getList()
{
    try {
        $permissions = checkPermissions(get_index_route($this));
        $canUpdate = $permissions['canUpdate'] ?? false;
        $canDelete = $permissions['canDelete'] ?? false;

        $query = CategoryModel::select(['id', 'name', 'created_at'])
            ->where('status', 1)
            ->orderBy('created_at', 'desc');

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
        $group = CategoryModel::where('id', $id)->first();

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $group  // $group->is_service will be included automatically
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching Category: ' . $e->getMessage()
        ], 500);
    }
}

    public function destroy(Request $request)
    {
        $id = $request->id;

        try {
            DB::beginTransaction();

            $stockGroup = CategoryModel::find($id);
            if (!$stockGroup) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found.'
                ]);
            }
            
            // Just update status to 1
            $stockGroup->update([
                'status' => 0,
                'updated_at' => now(),
                'updated_by' => getUpdatedBy()
            ]);
            

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Category marked as inactive.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Category: ' . $e->getMessage()
            ]);
        }
    }
}
