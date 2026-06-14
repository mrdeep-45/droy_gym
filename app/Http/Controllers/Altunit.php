<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AltUnitModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Altunit extends Controller
{
    //
     public function index()
    {
        $page_title = 'Alternate Unit ';
        $page_name = 'Altername Unit';
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company/master/altunit/altunit', compact('page_title', 'page_name'));
    }
    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
        'unit' => 'required|string|max:1000',
       
    ];
//'unit' => 'required|string|max:1000',
    $customAttributes = [
        'name' => 'Alternative Unit Name',
        
    ];

    // $messages = [
    //     'name.regex' => 'The name may only contain letters, spaces, dots (.), apostrophes (\') or hyphens (-).',
    // ];

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

        // Step 2: Manually check for duplicate unit (with status = 0)
        $exists = AltUnitModel::where('unit', $request->unit)
            ->where('status', 0)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'field' => 'unit',
                'message' => 'The Alternative Unit name already exists.',
            ], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = getCreatedBy();
        $validated['created_at'] = now();
        $validated['status'] = 0;

        AltUnitModel::create($validated);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Alternative Unit created successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create Alternative Unit.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function list(Request $request)
{
    $query = AltUnitModel::select([
        'alt_unit_id',
        'unit',
        'status',
        'created_at'
    ])->where('status', 0)
    ->orderByDesc('created_at');
    

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
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $row->alt_unit_id . '">Edit</button>
                    <button 
                        class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . $row->alt_unit_id . '" 
                        data-name="' . $row->unit . '" 
                        data-module="altunit"
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
public function edit($id)
{
    try {
        $altunit = AltUnitModel::where('alt_unit_id', $id)->first();

        if (!$altunit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alternative Unit not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $altunit
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching Alternative Unit data: ' . $e->getMessage()
        ], 500);
    }
}
public function update(Request $request)
{
    DB::beginTransaction();
    try {
        $rules = [
            'alt_unit_id' => 'required|exists:tbl_alternate_unit,alt_unit_id',
            'unit' => 'required|string|max:1000',
        ];

        $validator = Validator::make($request->all(), $rules);

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
         $exists = AltUnitModel::where('unit', $request->unit)
            ->where('status', 0)
            ->where('alt_unit_id', '!=', $request->alt_unit_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'field'   => 'unit',
                'message' => "This Alternate Unit '{$request->unit}' already exists.",
            ], 422);
        }
        $altunit = AltUnitModel::find($request->alt_unit_id);
        
        $altunit->unit = $request->unit;
        $altunit->updated_at = now();
        $altunit->save();

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Alternative Unit updated successfully.']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Failed to update Alternative Unit.', 'error' => $e->getMessage()], 500);
    }
}
public function destroy(Request $request)
{
    $altunitId = $request->alt_unit_id;

    try {
        DB::beginTransaction();

        $altunit = AltUnitModel::find($altunitId);

        if (!$altunit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alternative Unit not found.'
            ]);
        }

        // Soft delete: update status to inactive (1)
        $altunit->update([
            'status' => 1,
            'updated_at' => now(),
            'updated_by' => getUpdatedBy()
        ]);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Alternative Unit Deleted Successfully.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update status: ' . $e->getMessage()
        ]);
    }
}

}
