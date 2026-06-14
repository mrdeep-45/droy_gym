<?php

namespace App\Http\Controllers;



use App\Models\Stock_group_Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Stock_category_Model;

class StockCategory extends Controller
{
    public function index()
    {
        $page_title = 'Stock Category';
        $page_name = 'Stock Category';
        $stockGroups = Stock_group_Model::where('status', 0)
            ->orderBy('group_name', 'asc')
            ->get();
        return view('company/master/stock/stock_category', compact('page_title', 'page_name', 'stockGroups'));
    }


    public function storeOrUpdate(Request $request)
    {
        try {
            $groupInput = trim($request->stock_group);
            $categoryNames = $request->certificate ?? [];
            $existingIds = $request->sc_id ?? [];

            $errors = [];

            // STEP 1: Check if groupInput is numeric (i.e. existing sg_id) or new name
            if (is_numeric($groupInput)) {
                $groupId = $groupInput;
            } else {
                // New group name entered manually - check if already exists
                $existingGroup = Stock_group_Model::where('group_name', $groupInput)->first();
                if ($existingGroup) {
                    $groupId = $existingGroup->sg_id;
                } else {

                    $newGroup = Stock_group_Model::create([
                        'group_name' => $groupInput,
                        'status' => 0,
                        'created_by' => getCreatedBy(),
                        'created_at' => now(),
                    ]);
                    $groupId = $newGroup->sg_id;
                }
            }

            // STEP 2: Validate category names for duplicates
            foreach ($categoryNames as $index => $name) {
                $name = trim($name);

                if ($name === '') {
                    $errors["certificate.$index"] = 'Category name cannot be empty.';
                    continue;
                }

                // Check for duplicate category name under this group
                $query = Stock_category_Model::where('sg_id', $groupId)
                    ->where('category_name', $name);

                if (!empty($existingIds[$index])) {
                    $query->where('sc_id', '!=', $existingIds[$index]);
                }

                if ($query->exists()) {
                    $errors["certificate.$index"] = 'This category already exists in the selected group.';
                }
            }

            if (!empty($errors)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed.',
                    'errors' => $errors
                ], 422);
            }

            // STEP 3: Save categories (insert/update)
            foreach ($categoryNames as $index => $name) {
                $name = trim($name);
                $scId = $existingIds[$index] ?? null;

                if ($scId) {
                    // Update existing category
                    Stock_category_Model::where('sc_id', $scId)
                        ->update([
                            'category_name' => $name,
                            'updated_at' => now(),
                            'updated_by' => getUpdatedBy(),
                        ]);
                } else {
                    // Insert new category
                    Stock_category_Model::create([
                        'category_name' => $name,
                        'sg_id' => $groupId,
                        'status' => 0,
                        'created_at' => now(),
                        'created_by' => getCreatedBy(),
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Stock categories saved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }




    // public function getStockCategories()
    // {
    //     try {

    //         // Fetch all categories with their stock group
    //         $categories = Stock_category_Model::with('stockGroup')->orderBy('created_at', 'desc')
    //            ->where('status', 0)
    //         ->get();

    //         // Group by sg_id (not stockGroup)
    //         $grouped = $categories->groupBy('sg_id');

    //         $formattedData = [];

    //         foreach ($grouped as $groupId => $items) {
    //             $first = $items->first();
    //             $formattedData[] = [
    //                 'sg_id' => $groupId,
    //                 'group_name' => $first->stockGroup->group_name ?? 'N/A',
    //                 'category_list' => $items->pluck('category_name')->toArray(),
    //                 'sc_ids' => $items->pluck('sc_id')->toArray(), // For edit/delete
    //             ];
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $formattedData
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error fetching stock categories: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }



    public function getStockCategories()
    {
        try {
            // Fetch all active categories with their active stock groups
            $categories = Stock_category_Model::with(['stockGroup' => function ($query) {
                $query->where('status', 0); // Only active stock groups
            }])
                ->where('status', 0) // Only active categories
                ->orderBy('created_at', 'desc')
                ->get();

            // Filter out categories that don't have an active stock group
            $categories = $categories->filter(function ($category) {
                return !is_null($category->stockGroup);
            });

            // Group by sg_id
            $grouped = $categories->groupBy('sg_id');

            $formattedData = [];

            foreach ($grouped as $groupId => $items) {
                $first = $items->first();
                $formattedData[] = [
                    'sg_id' => $groupId,
                    'group_name' => $first->stockGroup->group_name ?? 'N/A',
                    'category_list' => $items->pluck('category_name')->toArray(),
                    'sc_ids' => $items->pluck('sc_id')->toArray(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $formattedData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching stock categories: ' . $e->getMessage()
            ], 500);
        }
    }
    public function edit($id)
    {
        try {


            $category = Stock_category_Model::where('sc_id', $id)
                ->where('status', 0)
                ->first();

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stock category not found'
                ], 404);
            }

            $sgId = $category->sg_id;


            $categories = Stock_category_Model::where('sg_id', $sgId)
                ->where('status', 0)
                ->get(['sg_id', 'sc_id', 'category_name']);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'sg_id' => $sgId,
                    'categories' => $categories
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching stock category: ' . $e->getMessage()
            ], 500);
        }
    }


    //delete saparatelly
    // public function delete($id)
    // {
    //     try {

    //         $category = Stock_category_Model::where('sc_id', $id)
    //             ->first();

    //         if (!$category) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Category not found.'
    //             ]);
    //         }

    //         $category->delete();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Category deleted successfully.'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to delete category: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function delete($id)
    {
        try {
            $updated = Stock_category_Model::where('sc_id', $id)
                ->where('status', 0)
                ->update([
                    'status' => 1,
                    'updated_at' => now(),
                    'updated_by' => getUpdatedBy(),
                ]);


            if ($updated) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Category removed from the list.'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found or already removed.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }


    // public function destroy($sgId)
    // {
    //     try {


    //         // Check if any category exists under this group
    //         $categories = Stock_category_Model::where('sg_id', $sgId)
    //             ->get();

    //         if ($categories->isEmpty()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'No stock categories found under this group'
    //             ], 404);
    //         }

    //         // Delete all categories under this sg_id
    //         Stock_category_Model::where('sg_id', $sgId)
    //             ->delete();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'All stock categories under this group deleted successfully'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to delete stock categories: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function destroy($sgId)
    {
        try {
            // Check if any category exists under this group
            $categories = Stock_category_Model::where('sg_id', $sgId)
                ->where('status', 0)
                ->get();

            if ($categories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No stock categories found under this group'
                ], 404);
            }

            // Soft delete: Set status to 1 instead of delete
            Stock_category_Model::where('sg_id', $sgId)
                ->where('status', 0)
                ->update([
                    'status' => 1,
                    'updated_at' => now(),
                    'updated_by' => getUpdatedBy()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'All stock categories under this group marked as inactive'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}
