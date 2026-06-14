<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitModel; 
use App\Models\AltUnitModel; 
use App\Models\Stock_group_Model;                                                                                                                                    ;
use App\Models\Stock_category_Model;                                                                                                                                    ;
use App\Models\RawmaterialModel;                                                                                                                                    ;
use App\Models\Company_menu;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Raw_material extends Controller
{
    //
     public function index()
    {
        $page_title = 'Raw Material';
        $page_name = 'Raw Material';
        $unit = UnitModel::active()->get();
        $altunit = AltUnitModel::active()->get();
        $stock_group = Stock_group_Model::active()->get();
        $gst = DB::table("tbl_gst")->where('status',0)->get();
        return view('company/master/raw_material/raw_material', compact('page_title', 'page_name','unit','altunit','stock_group','gst'));
    }

    public function get_stock(Request $request){
        $sg_id = $request->input("sg_id");
        $categories = Stock_category_Model::where('sg_id', $sg_id)->get(['sc_id', 'category_name']);
        return response()->json($categories);
    }


public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
        'name' => 'required|string|max:1000',
        'description' => 'nullable|string|max:1000',
        'notes' => 'nullable|string|max:1000',
        'sg_id' => 'required|integer|exists:tbl_stock_group,sg_id',
        'sc_id' => 'required|integer|exists:tbl_stock_category,sc_id',
        'unit_id' => 'required|integer|exists:tbl_unit,unit_id',
        'alt_unit_id' => 'required|integer|exists:tbl_alternate_unit,alt_unit_id',
        'per_unit_rate' => 'required|numeric|min:0',
        'hsn_code' => 'required|string|max:10',
        'type_of_supply' => 'required|in:Goods,Services',
        'gst_id' => 'required|integer|exists:tbl_gst,gst_id',
        'inclusive_duties_taxes' => 'nullable|in:1,0',
    ];

    $customAttributes = [
        'name' => 'Name',
        'description' => 'Description',
        'notes' => 'Notes',
        'sg_id' => 'Stock Group',
        'sc_id' => 'Stock Category',
        'unit_id' => 'Unit',
        'alt_unit_id' => 'Alternate Unit',
        'per_unit_rate' => 'Per Unit Rate',
        'hsn_code' => 'HSN Code',
        'type_of_supply' => 'Type of Supply',
        'gst_id' => 'GST',
        'inclusive_duties_taxes' => 'Inclusive Duties & Taxes',
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

        $validated = $validator->validated();
        $validated['created_by'] = getCreatedBy();
        $validated['created_at'] = now();

        RawmaterialModel::create($validated);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Raw material created successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create raw material.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function list()
{
    $raw_material = RawmaterialModel::select([
            'tbl_raw_material.rm_id',
            'tbl_raw_material.name',
            'tbl_stock_group.group_name as stock_group',
            'tbl_stock_category.category_name as stock_category',
            'tbl_raw_material.hsn_code',
            'tbl_gst.gst_no as gst',
            'tbl_raw_material.type_of_supply',
        ])
        ->join('tbl_stock_group', 'tbl_stock_group.sg_id', '=', 'tbl_raw_material.sg_id')
        ->join('tbl_stock_category', 'tbl_stock_category.sc_id', '=', 'tbl_raw_material.sc_id')
        ->join('tbl_gst', 'tbl_gst.gst_id', '=', 'tbl_raw_material.gst_id')
        ->OrderBy('name')
        ->where("tbl_raw_material.status",'0');

    return DataTables::of($raw_material)
        ->addIndexColumn()
        ->addColumn('action', function ($material) {
            return '
                <div class="">
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $material->rm_id . '"><i class="bx bx-edit"></i></button>
                    <button 
                            class="btn btn-sm btn-danger delete-btn" 
                            data-id="'.$material->rm_id .'" 
                            data-name="'.$material->name.'" 
                            data-module="raw_material"
                            data-table="rawdata"
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

}
