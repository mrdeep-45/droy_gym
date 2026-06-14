<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class Qty_set extends Controller
{
    //
     public function index()
    {
        $page_title = 'Qty Set';
        $page_name = 'Qty Set';
        $data = DB::table('tbl_qty_set')->first();
        return view('company/setting/qty_set', compact('page_title', 'page_name','data'));
    }

    public function store(Request $request){
    try {
            $rules = [
                'qty_set'  => 'required|numeric',
            ];

            $customAttributes = [
                'qty_set' => 'Qty',
            
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

        $validated = $validator->validated();
            $validated['updated_by'] = getCreatedBy(); 
            $validated['updated_at'] = now();

            DB::table('tbl_qty_set')->where('qty_id',$request->input('qty_id'))->update($validated);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quantity set successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to set quantity.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
