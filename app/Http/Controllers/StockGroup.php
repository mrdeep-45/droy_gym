<?php

namespace App\Http\Controllers;

use App\Models\Attendancemodel;
use App\Models\ForgotOutRequestmodel;
use App\Models\RoleModel;
use App\Models\Stock_group_Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Stock_category_Model;
class StockGroup extends Controller
{
    public function index()
    {
        $page_title = 'Stock Group';
        $page_name = 'Stock Group';

        return view('company/master/stock/stock_group', compact('page_title', 'page_name'));
    }


    public function storeOrUpdate(Request $request)
    {
        try {
            $data = $request->all();
            $group_id = $data['group_id'] ?? null;
            $certificates = $data['certificate'] ?? [];

            $errors = [];

            if (empty($certificates)) {
                $errors['certificate.0'] = ['At least one category name is required.'];
            } else {
                foreach ($certificates as $index => $certName) {
                    $certName = trim($certName);

                    if ($certName === '') {
                        $errors["certificate.$index"] = ['Category name cannot be empty.'];
                    } else {
                        $query = Stock_group_Model::where('group_name', $certName);

                        if ($group_id) {
                            $query->where('sg_id', '!=', $group_id);
                        }

                        if ($query->exists()) {
                            $errors["certificate.$index"] = ['This group name already exists.'];
                        }
                    }
                }
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 422);
            }
            // Update existing group
            if ($group_id) {
                $group = Stock_group_Model::where('sg_id', $group_id)
                    ->first();

                if (!$group) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock group not found'
                    ], 404);
                }

                $group->update([
                    'group_name' => trim($certificates[0]),
                    'updated_by' => getUpdatedBy(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Stock group updated successfully!'
                ]);
            }

            // Create new groups
            foreach ($certificates as $certName) {
                Stock_group_Model::create([
                    'group_name' => trim($certName),
                    'status' => 0,
                    'created_by' => getCreatedBy(),
                    'created_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock categories added successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }



    public function getStockGroups()
    {
        try {



            $stockGroups = Stock_group_Model::select(['sg_id', 'group_name', 'status', 'created_at'])
               ->where('status', 0)
            ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $stockGroups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching stock groups: ' . $e->getMessage()
            ], 500);
        }
    }
    public function edit($id)
    {
        try {



            $group = Stock_group_Model::where('sg_id', $id)
                ->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stock group not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching stock group: ' . $e->getMessage()
            ], 500);
        }
    }

    // public function destroy($id)
    // {
    //     try {
    //         $group = Stock_group_Model::where('sg_id', $id)
    //             ->first();

    //         if (!$group) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Stock group not found'
    //             ], 404);
    //         }

    //         $group->delete();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Stock group deleted successfully'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Failed to delete stock group: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
 public function destroy(Request $request)
{
    $sg_id = $request->sg_id;   

    try {
         DB::beginTransaction();

        $stockGroup = Stock_group_Model::find($sg_id);
        if (!$stockGroup) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock group not found.'
            ]);
        }

        // Just update status to 1
        $stockGroup->update([
            'status' => 1,
            'updated_at'=>now(),
            'updated_by'=>getUpdatedBy()
        ]);
        Stock_category_Model::where('sg_id', $sg_id)
            ->update([
                'status' => 1,
                'updated_at' => now(),
                'updated_by' => getUpdatedBy()
            ]);
             DB::commit();
        return response()->json([
            'status' => 'success',
            'message' => 'Stock group marked as inactive.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update status: ' . $e->getMessage()
        ]);
    }
}



}
