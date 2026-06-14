<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Unit extends Controller
{
    //
     public function index()
    {
        $page_title = 'Unit';
        $page_name = 'unit';
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company/master/unit/unit', compact('page_title', 'page_name'));
    }

    public function list(Request $request)
{
    $query = UnitModel::select([
        'unit_id',
        'unit',
        'status',
        'created_at'
    ])->where('status', 0)
    ->orderByDesc('created_at'); // or use ->active() if you have a scope

    return DataTables::of($query)
        ->addIndexColumn()
        ->editColumn('status', function ($row) {
            return $row->status == 1 ? 'Inactive' : 'Active';
        })
        ->editColumn('created_at', function ($row) {
            return $row->created_at ? $row->created_at->format('d-m-Y h:i:s') : '-';
        })
        ->addColumn('action', function ($row) {
            return '
                <div class="btn-group">
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $row->unit_id . '">Edit</button>
                    <button 
                        class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . $row->unit_id . '" 
                        data-name="' . $row->unit . '" 
                        data-module="unit"
                        data-table="rawdata"
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal"
                        >
                        Delete
                    </button>
                </div>
            ';
        })
        ->rawColumns(['action'])
        ->make(true);
}
public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
            'unit' => 'required|string|max:255',
        ];

        $customAttributes = [
            'unit' => 'Unit Name',
        ];

        $validator = Validator::make($request->all(), $rules, [], $customAttributes);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $firstField = array_key_first($errors);
            $firstMessage = $errors[$firstField][0];

            return response()->json([
                'success' => false,
                'field' => $firstField,
                'message' => $firstMessage,
            ], 422);
        }

        // Check for duplicate unit name with status 0
        $exists = UnitModel::where('unit', $request->unit)
            ->where('status', 0)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'field' => 'unit',
                'message' => 'The unit name already exists.',
            ], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = auth()->id() ?? 0;

       // $validated['created_by'] = getCreatedBy(); // or auth()->id()
        $validated['created_at'] = now();
        $validated['status'] = 0;

        UnitModel::create($validated);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create unit.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function edit($id)
{
    try {
        $unit = UnitModel::where('unit_id', $id)->first();

        if (!$unit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $unit
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching unit data: ' . $e->getMessage()
        ], 500);
    }
}
public function update(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
            'unit_id' => 'required|exists:tbl_unit,unit_id',
            'unit'    => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $firstField = array_key_first($errors);
            $firstMessage = $errors[$firstField][0];

            return response()->json([
                'success' => false,
                'field'   => $firstField,
                'message' => $firstMessage,
            ], 422);
        }

        $exists = UnitModel::where('unit', $request->unit)
            ->where('status', 0)
            ->where('unit_id', '!=', $request->unit_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'field'   => 'unit',
                'message' => "This Unit '{$request->unit}' already exists.",
            ], 422);
        }

        $unit = UnitModel::findOrFail($request->unit_id);
        $unit->unit = $request->unit;
        $unit->updated_at = now();
        $unit->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to update Unit.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function destroy(Request $request)
{
    $unitId = $request->unit_id;

    try {
        DB::beginTransaction();

        $unit = UnitModel::find($unitId);

        if (!$unit) {
            return response()->json([
                'status' => 'error',
                'message' => ' Unit not found.'
            ]);
        }

        // Soft delete: update status to inactive (1)
        $unit->update([
            'status' => 1,
            'updated_at' => now(),
            'updated_by' => getUpdatedBy()
        ]);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => ' Unit Deleted Successfully.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update status: ' . $e->getMessage()
        ]);
    }
}
/*public function destroy(Request $request)
{
    $unitId = $request->unit_id;

    try {
        DB::beginTransaction();

        $unit = UnitModel::find($unitId);

        if (!$unit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit not found.'
            ]);
        }

        // Soft delete
        $unit->update([
            'status' => 1,
            'updated_at' => now(),
            'updated_by' => getUpdatedBy() // Optional: if you're using this helper
        ]);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Unit deleted successfully.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to delete unit: ' . $e->getMessage()
        ]);
    }
}
*/



}
