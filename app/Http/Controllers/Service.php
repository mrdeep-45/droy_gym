<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Servicemodel; // adjust path as per your structure
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Service extends Controller
{
    //
     public function index()
    {
        $page_title = 'Service';
        $page_name = 'Service';

        return view('company/master/service/service', compact('page_title', 'page_name'));
    }
      public function getList()
    {
        try {
            $data = Servicemodel::select(['service_id', 'service_name', 'created_at'])
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
        $ids = $request->input('service_id'); // could be array or single value or null
        $names = $request->input('service_name'); // array of names

        if (empty($names) || !is_array($names)) {
            //return response()->json(['status' => 'error', 'message' => 'At least one Service name is required.'], 422);
             return response()->json([
                    'errors' => [
                        'service_name.0' => ['At least one Service name is required.']
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
               // return response()->json(['status' => 'error', 'message' => "Service name at position $index is required."], 422);
                 return response()->json([
                        'errors' => [
                            "service_name.$index" => ["Service name at position $index is required."]
                        ]
                    ], 422);
            }

            $id = $ids[$index] ?? null;

            $exists = Servicemodel::where('service_name', $name)
                ->when($id, fn($q) => $q->where('service_id', '!=', $id))
                ->exists();

            if ($exists) {
                //return response()->json(['status' => 'error', 'message' => "This Service '$name' already exists."], 422);
                 return response()->json([
                        'errors' => [
                            "service_name.$index" => ["This Service '$name' already exists."]
                        ]
                    ], 422);
            }

            if ($id) {
                $item = Servicemodel::find($id);
                if ($item) {
                    $item->update([
                        'service_name' => $name,
                        'updated_by' => getUpdatedBy(),
                        'updated_at' => now()
                    ]);
                    $updated = true;
                }
            } else {
                Servicemodel::create([
                    'service_name' => $name,
                    'status' => 0,
                    'created_by' => getCreatedBy(),
                    'created_at' => now()
                ]);
                $created = true;
            }
        }

        if ($updated && $created) {
            $message = 'Services updated and added successfully.';
        } elseif ($updated) {
            $message = 'Services updated successfully.';
        } elseif ($created) {
            $message = 'New Services added successfully.';
        } else {
            $message = 'No changes were made.';
        }

        return response()->json(['status' => 'success', 'message' => $message]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}



public function edit($id)
    {
        try {



            $group = Servicemodel::where('service_id', $id)
                ->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Services not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching Services: ' . $e->getMessage()
            ], 500);
        }
    }

     public function destroy(Request $request)
{
    $service_id = $request->service_id;   

    try {
         DB::beginTransaction();

        $stockGroup = Servicemodel::find($service_id);
        if (!$stockGroup) {
            return response()->json([
                'status' => 'error',
                'message' => 'Services not found.'
            ]);
        }

        // Just update status to 1
        $stockGroup->update([
            'status' => 1,
            'updated_at'=>now(),
            'updated_by'=>getUpdatedBy()
        ]);
        
             DB::commit();
        return response()->json([
            'status' => 'success',
            'message' => 'Services marked as inactive.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update status: ' . $e->getMessage()
        ]);
    }
}
}
