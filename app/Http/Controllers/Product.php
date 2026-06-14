<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitModel;
use App\Models\ProductModel;
use App\Models\HsnGstMapModel;
use App\Models\ProductCategoryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Product extends Controller
{
    //

    public function index()
    {
        $page_title = 'Product';
        $page_name = 'Product';
        $unit = UnitModel::active()->get();
        $category = ProductCategoryModel::active()->get();
        // $gst = DB::table("tbl_gst")->get();
        $hsn_data = HsnGstMapModel::get();
        return view('company/master/product/product', compact('page_title', 'page_name', 'unit', 'category', 'hsn_data'));
    }
    private function getGstOptions()
    {
        return DB::table('tbl_gst')->pluck('gst_no')->toArray();
    }

    public function store(Request $request)
    {
        DB::beginTransaction();


        try {
            $rules = [
                'category_id'   => 'required|exists:tbl_product_category,category_id',
                'prod_code'     => 'required|string|max:100|unique:tbl_product,prod_code',
                'prod_name'     => 'required|string|max:255',
                'hsn_code'      => 'required|string|max:50',
                'prod_desc'     => 'nullable|string',
                'unit_id'       => 'required|exists:tbl_unit,unit_id',
                'gst' => 'required|string|max:10',

                'temp_range'    => 'required|string|max:255',
            ];

            if (in_array($request->category_id, [1, 2])) {
                $rules['external_dimensions'] = 'required|string|max:255';
                $rules['gross_weight'] = 'required|string|max:255';
                $rules['validation_hrs'] = 'nullable|string|max:255';
                $rules['internal_dimensions'] = 'nullable|string|max:255';
                $rules['payload_dimensions'] = 'nullable|string|max:255';
                $rules['usable_capacity'] = 'nullable|string|max:255';
            }

            if ($request->category_id == 4) {
                $rules['dimensions'] = 'required|string|max:255';
                $rules['pcm_volume'] = 'required|string|max:255';
            }

            $customAttributes = [
                'category_id'     => 'Product Category',
                'prod_code'       => 'Model Code',
                'prod_name'       => 'Product Name',
                'unit_id'         => 'Unit',
                'hsn_code'        => 'HSN Code',
                'gst'             => 'GST',
                'temp_range'      => 'Temp Range',
                'dimensions'      => 'Dimensions',
                'gross_weight'    => 'Gross Weight',
                'pcm_volume'      => 'Volume',
                'external_dimensions' => 'External Dimension'
            ];

            $validator = Validator::make($request->all(), $rules, [], $customAttributes);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'field' => $firstField,
                   'message' => $firstMessage,
                ], 422);
            }



            $validated = $validator->validated();
            $validated['created_by'] = getCreatedBy();
            $validated['created_at'] = now();
            $hsnExists = HsnGstMapModel::where('hsn_no', $validated['hsn_code'])->where('gst_no', $validated['gst'])->exists();
            if (!$hsnExists) {
                HsnGstMapModel::create([
                    'hsn_no' => $validated['hsn_code'],
                    'gst_no' => $validated['gst'],
                    'created_by' => getCreatedBy(),
                    'created_at' => now(),
                ]);
            }


            ProductModel::create($validated);



            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function list()
    {
        $product = ProductModel::select([
            'tbl_product.product_id',
            'tbl_product.prod_code as code',
            'tbl_product.prod_name as name',
            'tbl_product.prod_desc as desc',
            'tbl_unit.unit as unit',
            'tbl_product.hsn_code',
            'tbl_product.gst',
        ])
            ->join('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_product.unit_id')
            ->where("tbl_product.status", '0')
            ->orderBy("tbl_product.product_id", 'desc');

        return DataTables::of($product)
            ->addIndexColumn()
            ->addColumn('action', function ($product) {
                return '
                <div class="">
                <a href="#" class="btn btn-sm btn-info view-btn" 
                        data-id="' . $product->product_id . '" 
                        data-bs-toggle="modal" 
                        data-bs-target="#productModal" 
                        title="Show Product"><i class="fe fe-eye"></i></a>
                    <button class="btn btn-sm btn-primary edit-btn d-none" data-id="' . $product->product_id . '"><i class="bx bx-edit"></i></button>
                    <button 
                            class="btn btn-sm btn-danger delete-btn" 
                            data-id="' . $product->product_id . '" 
                            data-name="' . $product->name . '" 
                            data-module="product"
                            data-table="productdata"
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteModal"
                        >
                            <i class="bx bx-trash"></i>
                        </button>
                </div>
            ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    public function getGstByHsn(Request $request)
    {
        $hsn = $request->query('hsn_code');
        $gstNo = HsnGstMapModel::where('hsn_no', $hsn)
            ->value('gst_no');

        return response()->json(['gst_no' => $gstNo]);
    }

    public function get_data(Request $request)
    {
        $id = $request->input("id");
        $product = ProductModel::select('tbl_product.*', 'tbl_product_category.name as category_name', 'tbl_unit.unit')
            ->join('tbl_product_category', 'tbl_product_category.category_id', '=', 'tbl_product.category_id')
            ->join('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_product.unit_id')
            ->where('tbl_product.product_id', $id)
            ->where('tbl_product.status', 0)
            ->first();

        return view('company/master/product/product_view_modal', compact('product'));
    }
}
