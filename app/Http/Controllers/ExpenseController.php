<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExpenseModel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    //
     public function index()
    {
        $page_title = 'Expenses';
        $page_name  = 'Expense';

        return view('company.master.expense', compact('page_title', 'page_name'));
    }
    public function storeOrUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'title'        => 'required',
                'category'     => 'required',
                'amount'       => 'required|numeric|min:0.01',
                'expense_date' => 'required|date'
            ]);

            $expense_id = $request->expense_id;

            $data = [
                'title'        => $request->title,
                'category'     => $request->category,
                'amount'       => $request->amount,
                'expense_date' => $request->expense_date,
                'paid_to'      => $request->paid_to,
                'note'         => $request->note,
            ];

            if ($expense_id) {
                $expense = ExpenseModel::find($expense_id);
                if (!$expense) {
                    return response()->json(['status' => 'error', 'message' => 'Expense not found.'], 404);
                }
                $data['updated_by'] = getUpdatedBy();
                $data['updated_at'] = now();
                $expense->update($data);
                $message = 'Expense updated successfully.';
            } else {
                $data['status']     = 0;
                $data['created_by'] = getCreatedBy();
                $data['created_at'] = now();
                ExpenseModel::create($data);
                $message = 'Expense added successfully.';
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getList()
    {
        try {
            $query = ExpenseModel::select(['id', 'title', 'category', 'amount', 'expense_date', 'paid_to', 'note', 'created_at'])
                ->where('status', 0)
                ->orderBy('expense_date', 'desc');

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-primary edit-expense" data-id="' . $row->id . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-expense" data-id="' . $row->id . '">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    ';
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
            $expense = ExpenseModel::find($id);
            if (!$expense) {
                return response()->json(['status' => 'error', 'message' => 'Expense not found'], 404);
            }
            return response()->json(['status' => 'success', 'data' => $expense]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            $expense = ExpenseModel::find($request->expense_id);
            if (!$expense) {
                return response()->json(['status' => 'error', 'message' => 'Expense not found.']);
            }
            $expense->update([
                'status'     => 1,
                'updated_by' => getUpdatedBy(),
                'updated_at' => now()
            ]);
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Expense removed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
