<?php

namespace App\Http\Controllers;
use App\Models\ProductModel;
use App\Models\RawmaterialModel;
use App\Models\UnitModel;
use App\Models\AltUnitModel;
use App\Models\ProductRawMappingModel;
use App\Models\ProductRawItemsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Collection;
class Product_mapping extends Controller
{
    //
    public function index()
    {
        $page_title = 'Product Raw Mapping';
        $page_name = 'Product Raw Mapping';
        $product = ProductModel::active()->get();
        $raw_material = RawmaterialModel::join('tbl_gst', 'tbl_gst.gst_id', '=', 'tbl_raw_material.gst_id')
        ->select('tbl_raw_material.*', 'tbl_gst.gst_no')
        ->where('tbl_raw_material.status',0)
        ->get();
        $unit = UnitModel::active()->get();
        $altunit = AltUnitModel::active()->get();
        return view('company/master/product_mapping/product_mapping', compact('page_title', 'page_name', 'product', 'raw_material','unit','altunit'));
    }

    public function store(Request $request){
    DB::beginTransaction();

    try {
        $rules = [
            'product_id' => 'required|exists:tbl_product,product_id',
        ];

        $customAttributes = [
            'product_id' => 'Product',
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
        $materials = json_decode($request->materials, true);

        if (!is_array($materials) || count($materials) === 0) {
            return response()->json([
                'success' => false,
                'field' => 'materials', 
                'message' => 'Please add at least one material before submitting the form.',
            ], 422);
        }
        $validated['created_by'] = getCreatedBy();
        $validated['created_at'] = now();

        $po = ProductRawMappingModel::create($validated);
        $materials = json_decode($request->materials, true);

        if (is_array($materials) && count($materials)) {
            foreach ($materials as $item) {
                ProductRawItemsModel::create([
                    'product_raw_id'  => $po->product_raw_id,
                    'rm_id'       => $item['material_id'],
                    'unit_id'     => $item['unit_id'],
                    'gst_no'      => $item['gst_percent'],
                    'qty'         => $item['qty'],
                    'created_by'  => getCreatedBy(),
                    'created_at'  => now(),
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Product Raw Mapping created successfully.',
            'po_id' => $po->po_id,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to create product raw mapping.',
            'error' => $e->getMessage(),
        ], 500);
    }
    }

   public function list()
{
    $raws = ProductRawMappingModel::select([
            'tbl_product_raw_mapping.product_id',
            'tbl_product_raw_mapping.product_raw_id',
            'tbl_product.prod_code',
            'tbl_product.prod_name',
            'tbl_product_raw_items.gst_no',
            'tbl_product_raw_items.qty',
            'tbl_unit.unit',
            'tbl_raw_material.name as raw_material'
        ])
        ->join('tbl_product', 'tbl_product.product_id', '=', 'tbl_product_raw_mapping.product_id')
        ->join('tbl_product_raw_items', 'tbl_product_raw_items.product_raw_id', '=', 'tbl_product_raw_mapping.product_raw_id')
        ->join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_product_raw_items.rm_id')
        ->join('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_product_raw_items.unit_id')
        ->where("tbl_product_raw_mapping.status", '0')
        ->get()
        ->groupBy('product_id');

    $data = [];
    $i = 1;
    foreach ($raws as $group) {
    $first = $group->first();
    $materials = '';
    $units = '';
    $gsts = '';
    $qtys = '';
    $prodLines = $first->prod_code . ' - ' . $first->prod_name; 

    $count = 1;
    foreach ($group as $item) {
        $materials .= $count . '. ' . $item->raw_material . '<br>';
        $units     .= $item->unit . '<br>';
        $gsts      .= $item->gst_no . '<br>';
        $qtys      .= $item->qty . '<br>';
        $count++;
    }

    $data[] = [
        'DT_RowIndex' => $i++,
        'product'     => $prodLines,
        'raw_material'=> $materials,
        'unit'        => $units,
        'gst'         => $gsts,
        'qty'         => $qtys,
        'action' => '<button class="btn btn-sm btn-primary edit-btn">Edit</button> <button 
                            class="btn btn-sm btn-danger delete-btn" 
                            data-id="'.$item->product_raw_id .'" 
                            data-name="'.$prodLines.'" 
                            data-module="product_map"
                            data-table="productmapdata"
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteModal"
                        >
                            Delete
                        </button>'
    ];
}


    return DataTables::of($data)
        ->rawColumns(['product', 'raw_material', 'unit', 'gst', 'qty', 'action'])
        ->make(true);
}


}
