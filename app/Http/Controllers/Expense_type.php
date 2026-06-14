<?php

namespace App\Http\Controllers;
use App\Models\ExpenseTypeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
class Expense_type extends Controller
{
    //

    public function index()
    {
        $page_title = 'Expense Type';
        $page_name = 'Expense Type';
        return view('company/master/expense_type/expense_type', compact('page_title', 'page_name'));
    }

     public function store(Request $request)
{
            DB::beginTransaction();
            try {
                $expense_type_id = $request->input('expense_type_id');
                $rules = [
                    'expense_type.*' => 'required|string|max:255',
                ];
                $customAttributes = [
                    'expense_type.*' => 'Expense Type',
                ];

                $validator = Validator::make($request->all(), $rules, [], $customAttributes);

                if ($validator->fails()) {
                    $errors = $validator->errors()->toArray();
                    $firstField = array_key_first($errors);
                    $firstMessage = $errors[$firstField][0];

                    return response()->json([
                        'success' => false,
                        'field'   => str_replace('.', '_', $firstField),
                        'message' => $firstMessage,
                    ], 422);
                }

                $validated = $validator->validated();
            
                if ($expense_type_id) {
                $category = ExpenseTypeModel::find($expense_type_id);
                if (!$category) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Expense Type not found.',
                    ], 404);
                }

                $catName = trim($validated['expense_type'][0]);

                $exists = ExpenseTypeModel::where('expense_type', $catName)
                    ->where('expense_type_id', '!=', $expense_type_id)
                    ->where('status',0)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => "Expense Type '{$catName}' already exists.",
                    ], 422);
                }

                $category->update([
                    'expense_type'       => $catName,
                    'updated_at' => now(),
                    'updated_by' => getUpdatedBy(),
                ]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Expense Type updated successfully.',
                ]);
            }


        foreach ($validated['expense_type'] as $catName) {
            if (empty(trim($catName))) {
                continue;
            }
            $exists = ExpenseTypeModel::where('expense_type', $catName)->where('status',0)->exists();
             if ($exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Expense Type '{$catName}' already exists.",
                ], 422);
            }
            ExpenseTypeModel::create([
                'expense_type' => $catName,
                'created_by' => getCreatedBy(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Expense Type created successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to save expense type.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


public function list()
    {
        $expensetype = ExpenseTypeModel::select([
            'tbl_expense_type.expense_type_id',
            'tbl_expense_type.expense_type',
            'tbl_expense_type.created_at',
        ])
            ->where("tbl_expense_type.status", '0')
            ->orderBy("tbl_expense_type.expense_type_id", 'desc');

        return DataTables::of($expensetype)
            ->addIndexColumn()
            ->editColumn('created_at', function ($row) {
                return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i:s');
            })
            ->addColumn('action', function ($expensetype) {
                return '
                <div class="">
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $expensetype->expense_type_id . '">Edit</button>
                    <button 
                            class="btn btn-sm btn-danger delete-btn" 
                            data-id="' . $expensetype->expense_type_id . '" 
                            data-name="' . $expensetype->expense_type . '" 
                            data-module="expense_type"
                            data-table="expensetypeData"
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

     public function get_data(Request $request){
        $expense_type_id = $request->input("expense_type_id");
        $expense = ExpenseTypeModel::where("expense_type_id",$expense_type_id)->where('status',0)->first();
        if ($expense) {
           return response()->json(['success' => true, 'data' => $expense]);
        } else {
            return response()->json(['success' => false, 'message' => 'Expense Type not found']);
        }
    }
}
