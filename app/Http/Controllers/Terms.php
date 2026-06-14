<?php

namespace App\Http\Controllers;
use App\Models\TermsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Terms extends Controller
{
    //
     public function index()
    {
        $page_title = 'Terms';
        $page_name = 'Terms';
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company/master/terms/terms', compact('page_title', 'page_name'));
    }
    public function edit($id)
{
    try {
        $altunit = TermsModel::where('term_id', $id)->first();

        if (!$altunit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terms not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $altunit
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching Terms data: ' . $e->getMessage()
        ], 500);
    }
}
public function update(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
            'terms' => 'required|string|max:1000',
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

        $term = TermsModel::find(1); // always ID 1
        $term->terms = $request->terms;
       // $term->updated_at = now();
        $term->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Terms updated successfully.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update Terms.',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function show()
{
    try {
        $term = TermsModel::where('term_id', 1)->first(); // always ID 1

        if (!$term) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terms not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $term
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching Terms data: ' . $e->getMessage()
        ], 500);
    }
}
}
