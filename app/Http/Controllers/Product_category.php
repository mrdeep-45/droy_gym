<?php

namespace App\Http\Controllers;

use App\Models\ProductCategoryModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
class Product_category extends Controller
{
    //
    public function index()
    {
        $page_title = 'Product Category';
        $page_name = 'Product Category';
        return view('company/master/product_category/product_category', compact('page_title', 'page_name'));
    }

   public function store(Request $request)
{
            DB::beginTransaction();
            try {
                $category_id = $request->input('category_id');
                $rules = [
                    'name.*' => 'required|string|max:255',
                ];
                $customAttributes = [
                    'name.*' => 'Product Category Name',
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
            
                if ($category_id) {
                $category = ProductCategoryModel::find($category_id);
                if (!$category) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Category not found.',
                    ], 404);
                }

                $catName = trim($validated['name'][0]);

                $exists = ProductCategoryModel::where('name', $catName)
                    ->where('category_id', '!=', $category_id)
                    ->where('status',0)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => "Category name '{$catName}' already exists.",
                    ], 422);
                }

                $category->update([
                    'name'       => $catName,
                    'updated_at' => now(),
                    'updated_by' => getUpdatedBy(),
                ]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Product category updated successfully.',
                ]);
            }


        foreach ($validated['name'] as $catName) {
            if (empty(trim($catName))) {
                continue;
            }
            $exists = ProductCategoryModel::where('name', $catName)->where('status',0)->exists();
             if ($exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Category name '{$catName}' already exists.",
                ], 422);
            }
            ProductCategoryModel::create([
                'name'       => $catName,
                'created_by' => getCreatedBy(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Product category created successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to save product category.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


public function list()
    {
        $product = ProductCategoryModel::select([
            'tbl_product_category.category_id',
            'tbl_product_category.name',
            'tbl_product_category.created_at',
        ])
            ->where("tbl_product_category.status", '0')
            ->orderBy("tbl_product_category.category_id", 'desc');

        return DataTables::of($product)
            ->addIndexColumn()
            ->editColumn('created_at', function ($row) {
                return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i:s');
            })
            ->addColumn('action', function ($product) {
                return '
                <div class="">
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $product->category_id . '">Edit</button>
                    <button 
                            class="btn btn-sm btn-danger delete-btn" 
                            data-id="' . $product->category_id . '" 
                            data-name="' . $product->name . '" 
                            data-module="product_category"
                            data-table="procatData"
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
        $category_id = $request->input("category_id");
        $product = ProductCategoryModel::where("category_id",$category_id)->where('status',0)->first();
        if ($product) {
           return response()->json(['success' => true, 'data' => $product]);
        } else {
            return response()->json(['success' => false, 'message' => 'Supplier not found']);
        }
    }
}
