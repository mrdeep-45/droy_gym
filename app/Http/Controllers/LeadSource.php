<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Sourcemodel; // adjust path as per your structure
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LeadSource extends Controller
{
    //
    public function index()
    {
        $page_title = 'Lead Source';
        $page_name = 'Lead Source';

        return view('company/master/leadsource/leadsource', compact('page_title', 'page_name'));
    }
    public function getList()
    {
        try {
            $data = Sourcemodel::select(['source_id', 'source_name', 'created_at'])
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
            $ids = $request->input('source_id'); // could be array or single value or null
            $names = $request->input('source_name'); // array of names

            if (empty($names) || !is_array($names)) {
                //return response()->json(['status' => 'error', 'message' => 'At least one Source name is required.'], 422);
                   return response()->json([
                    'errors' => [
                        'source_name.0' => ['At least one Source name is required.']
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
                    //return response()->json(['status' => 'error', 'message' => "Source name at position $index is required."], 422);
                     return response()->json([
                        'errors' => [
                            "source_name.$index" => ["Source name at position $index is required."]
                        ]
                    ], 422);
                }

                $id = $ids[$index] ?? null;

                $exists = Sourcemodel::where('source_name', $name)->where('status',0)
                    ->when($id, fn($q) => $q->where('source_id', '!=', $id))
                    ->exists();

                if ($exists) {
                   // return response()->json(['status' => 'error', 'message' => "This Source '$name' already exists."], 422);
                   return response()->json([
                        'errors' => [
                            "source_name.$index" => ["This Source '$name' already exists."]
                        ]
                    ], 422);
                }

                if ($id) {
                    $item = Sourcemodel::find($id);
                    if ($item) {
                        $item->update([
                            'source_name' => $name,
                            'updated_by' => getUpdatedBy(),
                            'updated_at' => now()
                        ]);
                        $updated = true;
                    }
                } else {
                    Sourcemodel::create([
                        'source_name' => $name,
                        'status' => 0,
                        'created_by' => getCreatedBy(),
                        'created_at' => now()
                    ]);
                    $created = true;
                }
            }
              DB::commit();
            if ($updated && $created) {
                $message = 'Sources updated and added successfully.';
            } elseif ($updated) {
                $message = 'Sources updated successfully.';
            } elseif ($created) {
                $message = 'New Sources added successfully.';
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



            $group = Sourcemodel::where('source_id', $id)
                ->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sources not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching Sources: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        $source_id = $request->source_id;

        try {
            DB::beginTransaction();

            $stockGroup = Sourcemodel::find($source_id);
            if (!$stockGroup) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sources not found.'
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
                'message' => 'Sources marked as inactive.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update status: ' . $e->getMessage()
            ]);
        }
    }
}
