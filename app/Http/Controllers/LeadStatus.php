<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Lead_status_Model; // adjust path as per your structure
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class LeadStatus extends Controller
{
    //
    public function index()
    {
        $page_title = 'Lead Status';
        $page_name = 'Lead Status';

        return view('company/master/leadstatus/leadstatus', compact('page_title', 'page_name'));
    }
    public function getList()
    {
        try {
            $data = Lead_Status_Model::select(['status_id', 'status_name', 'created_at'])
                ->where('status', 0)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function storeOrUpdate(Request $request)
    {
        try {
            $ids = $request->input('status_id'); // could be array or single value or null
            $names = $request->input('status_name'); // array of names

            if (empty($names) || !is_array($names)) {
               // return response()->json(['status' => 'error', 'message' => 'At least one status name is required.'], 422);
                 return response()->json([
                    'errors' => [
                        'status_name.0' => ['At least one status name is required.']
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
                   // return response()->json(['status' => 'error', 'message' => "Status name at position $index is required."], 422);
                      return response()->json([
                        'errors' => [
                            "status_name.$index" => ["Status name at position $index is required."]
                        ]
                    ], 422);
                }

                $id = $ids[$index] ?? null;

                $exists = Lead_Status_Model::where('status_name', $name)
                    ->when($id, fn($q) => $q->where('status_id', '!=', $id))
                    ->exists();

                if ($exists) {
                    //return response()->json(['status' => 'error', 'message' => "This status '$name' already exists."], 422);
                    return response()->json([
                        'errors' => [
                            "status_name.$index" => ["This Status '$name' already exists."]
                        ]
                    ], 422);
                }

                if ($id) {
                    $item = Lead_Status_Model::find($id);
                    if ($item) {
                        $item->update([
                            'status_name' => $name,
                            'updated_by' => getUpdatedBy(),
                            'updated_at' => now()
                        ]);
                        $updated = true;
                    }
                } else {
                    Lead_Status_Model::create([
                        'status_name' => $name,
                        'status' => 0,
                        'created_by' => getCreatedBy(),
                        'created_at' => now()
                    ]);
                    $created = true;
                }
            }
            DB::commit();
            if ($updated && $created) {
                $message = 'Statuses updated and added successfully.';
            } elseif ($updated) {
                $message = 'Statuses updated successfully.';
            } elseif ($created) {
                $message = 'New statuses added successfully.';
            } else {
                $message = 'No changes were made.';
            }

            return response()->json(['status' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }



    public function edit($id)
    {
        try {



            $group = Lead_Status_Model::where('status_id', $id)
                ->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Status not found'
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

    public function destroy(Request $request)
    {
        $status_id = $request->status_id;

        try {
            DB::beginTransaction();

            $stockGroup = Lead_Status_Model::find($status_id);
            if (!$stockGroup) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Status not found.'
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
                'message' => 'Status marked as inactive.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update status: ' . $e->getMessage()
            ]);
        }
    }
}
