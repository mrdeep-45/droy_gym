<?php

namespace App\Http\Controllers;
use App\Models\BankModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;


class Bank extends Controller
{
    //
    public function index()
    {
        $page_title = 'Bank';
        $page_name = 'Bank';
        return view('company/master/bank/bank', compact('page_title', 'page_name'));
    }

 public function store(Request $request)
{
    DB::beginTransaction();
    try {
        $bank_id = $request->input('bank_id');

        $rules = [
            'bank_name.*' => 'required|string|max:255',
            'is_upi'  => 'nullable', // no need for integer validation, we'll cast it
        ];
        $customAttributes = [
            'bank_name.*' => 'Bank Name',
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

        // handle checkbox properly (if checked → 1, else → 0)
        $upiLinked = $request->has('is_upi') ? 1 : 0;

        if ($bank_id) {
            $bank = BankModel::find($bank_id);
            if (!$bank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank not found.',
                ], 404);
            }

            $catName = trim($validated['bank_name'][0]);

            $exists = BankModel::where('bank_name', $catName)
                ->where('bank_id', '!=', $bank_id)
                ->where('status', 0)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => "Bank Name '{$catName}' already exists.",
                ], 422);
            }

            $bank->update([
                'bank_name'  => $catName,
                'is_upi' => $upiLinked,
                'updated_at' => now(),
                'updated_by' => getUpdatedBy(),
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Bank updated successfully.',
            ]);
        }

        foreach ($validated['bank_name'] as $key => $name) {
            if (empty(trim($name))) {
                continue;
            }

            $exists = BankModel::where('bank_name', $name)
                ->where('status', 0)
                ->exists();

            if ($exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Bank Name '{$name}' already exists.",
                ], 422);
            }
            
        $upiLinked1 = isset($request->is_upi[0]) ? 1 : 0;
            BankModel::create([
                'bank_name'  => $name,
                'is_upi' => $upiLinked1,
                'created_by' => getCreatedBy(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Bank(s) created successfully.',
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to save bank.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


public function list()
{
    $bank = BankModel::select([
            'tbl_bank.bank_id',
            'tbl_bank.bank_name',
            'tbl_bank.is_upi',
            'tbl_bank.created_at',
        ])
        ->where("tbl_bank.status", '0')
        ->orderBy("tbl_bank.bank_id", 'desc');

    return DataTables::of($bank)
        ->addIndexColumn()
        ->editColumn('bank_name', function ($row) {
            if ($row->is_upi == 1) {
                return $row->bank_name . ' <span class="badge bg-success ms-2">UPI</span>';
            }
            return $row->bank_name;
        })
        ->editColumn('created_at', function ($row) {
            return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i:s');
        })
        ->addColumn('action', function ($bank) {
            return '
                <div class="">
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $bank->bank_id . '">Edit</button>
                    <button 
                        class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . $bank->bank_id . '" 
                        data-name="' . $bank->bank_name . '" 
                        data-module="bank"
                        data-table="bankData"
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal"
                    >
                        Delete
                    </button>
                </div>
            ';
        })
        ->rawColumns(['bank_name','action'])
        ->make(true);
}


 public function get_data(Request $request){
        $bank_id = $request->input("bank_id");
        $bank = BankModel::where("bank_id",$bank_id)->where('status',0)->first();
        if ($bank) {
           return response()->json(['success' => true, 'data' => $bank]);
        } else {
            return response()->json(['success' => false, 'message' => 'Bank not found']);
        }
    }

}
