<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planmodel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PlanController extends Controller
{
    //
    public function index()
    {
        $page_title = 'Plan ';
        $page_name = 'Plan';
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company.master.plan', compact('page_title', 'page_name'));
    }

    public function storeOrUpdate(Request $request)
    {
        try {
            $ids = $request->plan_id;
            $names = $request->plan_name;
            $durations = $request->duration;
            $prices = $request->price;
            $descriptions = $request->description;

            if (empty($names) || !is_array($names)) {
               // return response()->json(['status' => 'error', 'message' => 'At least one Plan is required.'], 422);
                 return response()->json([
                    'errors' => [
                        'plan_name.0' => ['At least one Plan is required.']
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
                    //return response()->json(['status' => 'error', 'message' => "Plan at position $index is required."], 422);
                     return response()->json([
                        'errors' => [
                            "plan_name.$index" => ["Plan at position $index is required."]
                        ]
                    ], 422);
                }

                $duration = $durations[$index] ?? null;
                $price = $prices[$index] ?? null;
                $description = $descriptions[$index] ?? null;

                $id = $ids[$index] ?? null;

                $exists = Planmodel::where('plan_id', $name)->where('status',0)
                    ->when($id, fn($q) => $q->where('plan_id', '!=', $id))
                    ->exists();

                if ($exists) {
                    //return response()->json(['status' => 'error', 'message' => "This status '$name' already exists."], 422);
                    return response()->json([
                        'errors' => [
                            "plan_name.$index" => ["This Plan '$name' already exists."]
                        ]
                    ], 422);
                }

                if ($id) {
                    $item = Planmodel::find($id);
                    if ($item) {
                        $item->update([
                            'plan_name' => $name,
                            'duration'    => $duration,
                            'price'       => $price,
                            'description' => $description,
                            'updated_by' => getUpdatedBy(),
                            'updated_at' => now()
                        ]);
                        $updated = true;
                    }
                } else {
                    Planmodel::create([
                        'plan_name' => $name,
                        'duration'    => $duration,
                        'price'       => $price,
                        'description' => $description,
                        'status' => 0,
                        'created_by' => getCreatedBy(),
                        'created_at' => now()
                    ]);
                    $created = true;
                }
            }
            DB::commit();
            if ($updated && $created) {
                $message = 'Plans updated and added successfully.';
            } elseif ($updated) {
                $message = 'Plan updated successfully.';
            } elseif ($created) {
                $message = 'New Plans added successfully.';
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

        $query = Planmodel::select(['plan_id', 'plan_name', 'duration','price','description', 'created_at'])
            ->where('status', 0)
            ->orderBy('created_at', 'desc');

        return DataTables::of($query)
            ->addColumn('action', function ($row) use ($canUpdate, $canDelete) {
                $buttons = '';

                if ($canUpdate) {
                    $buttons .= '<button class="btn btn-sm btn-primary edit-group" data-id="' . $row->plan_id . '">
                                    <i class="fas fa-edit"></i> Edit
                                </button>';
                }

                if ($canDelete) {
                    $buttons .= '<button class="btn btn-sm btn-danger delete-group" data-id="' . $row->plan_id . '">
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



            $group = Planmodel::where('plan_id', $id)
                ->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Plan not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching Plan: ' . $e->getMessage()
            ], 500);
        }
    }
public function destroy(Request $request)
    {
        $plan_id = $request->plan_id;

        try {
            DB::beginTransaction();

            $stockGroup = Planmodel::find($plan_id);
            if (!$stockGroup) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Plan not found.'
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
                'message' => 'Plan marked as inactive.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Plan: ' . $e->getMessage()
            ]);
        }
    }
}
