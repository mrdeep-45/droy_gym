<?php

namespace App\Http\Controllers;

use App\Models\RawmaterialModel;
use Illuminate\Http\Request;
use App\Models\UnitModel;
use App\Models\AltUnitModel;
use App\Models\WorkOrderModel;
use App\Models\WorkOrderPCMModel;
use App\Models\WorkOrderTermsModel;
use App\Models\WoMaterialModel;
use App\Models\InventoryModel;
use App\Models\Staffmodel;
use App\Models\ProductionHistoryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class Work_order extends Controller
{
    //

    public function index()
    {
        $page_title = 'Work Order';
        $page_name = 'Work Order';
        $userType = session('login_type'); 
        $staffId  = session('staff_id');
        $unit = UnitModel::active()->get();
        $altunit = AltUnitModel::active()->get();
       $quotation = DB::table('tbl_quotation')
            ->Join('tbl_customer', 'tbl_quotation.lead_id', '=', 'tbl_customer.lead_id')
            ->whereNotIn('tbl_quotation.quotation_id', function ($query) {
                $query->select('quotation_id')
                    ->from('tbl_work_order');
            })
            ->select('tbl_quotation.*', 'tbl_customer.customer_id')
            ->get();

        if( $userType == "Staff"){
            $staff = Staffmodel::where("staff_id","!=",$staffId)->get();
        }else{
            $staff = Staffmodel::get();
        }
        $woNo = WorkOrderModel::generateWoNo();
        $permission = getpermission(request());
        return view('company/work_order/work_order', compact('page_title', 'page_name', 'unit', 'altunit', 'quotation', 'woNo','staff','permission'));
    }

   public function getQuotationProducts($id)
{
    $quotationItems = DB::table('tbl_quotation_item as qi')
        ->join('tbl_product as p', 'p.product_id', '=', 'qi.product_id')
        ->Join('tbl_unit', 'tbl_unit.unit_id', '=', 'p.unit_id')
        ->where('qi.quotation_id', $id)
        ->select('p.product_id as product_id', 'p.prod_name', 'qi.qty','tbl_unit.unit','tbl_unit.unit_id')
        ->get();

    $result = [];

    foreach ($quotationItems as $item) {
        $productRawIds = DB::table('tbl_product_raw_mapping')
            ->where('product_id', $item->product_id)
            ->pluck('product_raw_id');

        $rawMaterialItems = DB::table('tbl_product_raw_items')
            ->whereIn('product_raw_id', $productRawIds)
            ->select('rm_id', 'qty')
            ->get();

        $rawMaterialIds = $rawMaterialItems->pluck('rm_id');

        $rawMaterialsInfo = DB::table('tbl_raw_material')
            ->whereIn('rm_id', $rawMaterialIds)
            ->select('rm_id', 'name')
            ->get()
            ->keyBy('rm_id');

            $unmappedRawMaterials = DB::table('tbl_raw_material as rm')
            ->leftJoin('tbl_po_inventory as pi', 'pi.rm_id', '=', 'rm.rm_id')
            ->whereNotIn('rm.rm_id', $rawMaterialIds)
            ->where('rm.status', 0)
            ->select(
                'rm.rm_id',
                'rm.name',
                DB::raw('COALESCE(pi.avl_qty, 0) as avl_qty')
            )
            ->get()
            ->map(function ($rm) {
            
                $reservedData = DB::table("tbl_wo_raw_material as worm")
                    ->join('tbl_work_order as wo', 'wo.wo_id', '=', 'worm.wo_id')
                    ->where('worm.rm_id', $rm->rm_id)
                    ->where('worm.reserved_qty', '>', 0)
                    ->select('worm.reserved_qty', 'wo.work_order_no')
                    ->get();

                $totalReservedQty = $reservedData->sum('reserved_qty');
                $remainingQty = $rm->avl_qty;
                if ($remainingQty < 0) $remainingQty = 0;

                return (object) [
                    'id' => $rm->rm_id,
                    'name' => $rm->name,
                    'avl_qty' => $rm->avl_qty,
                    'reserved_qty_total' => $totalReservedQty,
                    'remaining_qty' => $remainingQty
                ];
            });

            $rawMaterials = [];
            foreach ($rawMaterialItems as $rmItem) {
                $rmId = $rmItem->rm_id;
                $baseQty = $rmItem->qty;
                $totalQty = $baseQty * $item->qty;

                $rawavlqty = DB::table("tbl_po_inventory")
                    ->where('rm_id', $rmId)
                    ->select('avl_qty')
                    ->first();

                $reservedData = DB::table("tbl_wo_raw_material as worm")
                    ->join('tbl_work_order as wo', 'wo.wo_id', '=', 'worm.wo_id')
                    ->where('worm.rm_id', $rmId)
                    ->where('worm.reserved_qty', '>', 0)
                    ->select('worm.reserved_qty', 'wo.work_order_no')
                    ->get();

                $totalReservedQty = $reservedData->sum('reserved_qty');

                $remainingQty = $rawavlqty->avl_qty ?? 0 ;
                if ($remainingQty < 0) $remainingQty = 0;

                $reservedDetails = $reservedData->map(function ($row) {
                    return [
                        'qty' => $row->reserved_qty,
                        'wo_no' => $row->work_order_no
                    ];
                })->toArray();

                $rawMaterials[] = [
                    'id' => $rmId,
                    'name' => $rawMaterialsInfo[$rmId]->name ?? '',
                    'base_qty' => $baseQty,
                    'total_qty' => $totalQty,
                    'avl_qty' => $rawavlqty->avl_qty ?? '0',
                    'reserved_qty_total' => $totalReservedQty,
                    'reserved_details' => $reservedDetails,
                    'remaining_qty' => $remainingQty
                ];
            }

        $result[] = [
            'product_id' => $item->product_id,
            'product_name' => $item->prod_name,
            'unit' => $item->unit,
            'unit_id' => $item->unit_id,
            'qty' => $item->qty,
            'raw_materials' => $rawMaterials,
            'unmapped_raw_materials' => $unmappedRawMaterials
        ];
    }

    return response()->json($result);
}

    public function getWorkOrders($customer_id)
    {
        $customer = DB::table('tbl_customer')
        ->where('customer_id', $customer_id)
        ->select('billing_address')
        ->first();

        $workOrders = DB::table('tbl_work_order as w')
            ->join('tbl_production as p', 'p.wo_id', '=', 'w.wo_id')
            ->where('w.customer_id', $customer_id)
            ->select('p.production_id', 'w.work_order_no','p.wo_id')
            ->get();

        return response()->json([
            'billing_address' => $customer->billing_address ?? '',
            'work_orders' => $workOrders
        ]);
    }

    public function checkDueDates(Request $request)
    {
        $productionIds = $request->woIds;

        $dueDates = DB::table('tbl_work_order as w')
            ->join('tbl_production as p', 'p.wo_id', '=', 'w.wo_id')
            ->whereIn('p.wo_id', $productionIds)
            ->pluck('w.delivery_date')
            ->unique();
           

        if ($dueDates->count() > 1) {
            return response()->json(['status' => 'error', 'message' => 'Due dates are not same']);
        }

        return response()->json(['status' => 'success', 'due_date' => $dueDates->first()]);
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $rules1 = [
                'quotation_id' => 'required|exists:tbl_quotation,quotation_id',
                'customer_id' => 'required|exists:tbl_customer,customer_id',
                'wo_no' => 'required|string',
                'date' => 'required|date',
                'staff_id' => 'required|exists:mst_staff,staff_id',
                'delivery_date' => 'required|date',
                'rm_id' => 'required|array',
                'rm_id.*' => 'required|exists:tbl_raw_material,rm_id',
                'product_id' => 'required|array',
                'product_id.*' => 'required|exists:tbl_product,product_id',
                'product_qty' => 'required|array',
                'qty' => 'required|array',
                'avl_qty' => 'required|array',
                'remaining_qty' => 'required|array',
            ];

             foreach ($request->input('base_qty', []) as $i => $val) {
                    if (($request->input("is_dynamic.$i") ?? 0) == 1) {
                        $rules1["base_qty.$i"] = 'required|numeric|min:1';
                    } else {
                        $rules1["base_qty.$i"] = 'nullable|numeric|min:0';
                    }
                }
            $customAttributes = [
                'quotation_id' => 'Quotation No.',
                'wo_no' => 'Work Order No.',
                'date' => 'Date',
                'staff_id' => "Approval Name",
                'delivery_date' => "Delivery Date",
                'rm_id' => 'Material',
                'base_qty.*' => 'Base Qty',
            ];

            $validator1 = Validator::make($request->all(), $rules1, [], $customAttributes);

            if ($validator1->fails()) {
                $errors = $validator1->errors()->toArray();
                $firstField = array_key_first($errors);
                $firstMessage = $errors[$firstField][0];

                return response()->json([
                    'success' => false,
                    'field' => $firstField,
                    'message' => $firstMessage,
                ], 422);
            }

            $validated1 = $validator1->validated();
            $validated1['date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated1['date'])->format('Y-m-d');
            $validated1['delivery_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated1['delivery_date'])->format('Y-m-d');
            $validated1['work_order_no'] = WorkOrderModel::generateWoNo();
            $validated1['created_by'] = getCreatedBy();
            $validated1['created_at'] = now();

            $workOrder = WorkOrderModel::create($validated1);

            $rules2 = [
                'packaging_requirements' => 'nullable|string',
                'additional_info' => 'nullable|string',
                'temp_range' => 'nullable|string',
                'conditioning_requirement' => 'nullable|string',
                'insulation_type' => 'nullable|string',
                'thickness' => 'nullable|string',
                'shipper_od' => 'nullable|string',
                'inner_dimension' => 'nullable|string',
                'product_space_dimension' => 'nullable|string',
                'product_space' => 'nullable|string',
                'lid_type' => 'nullable|string',
                'lid_detail' => 'nullable|string',
                'separator_card' => 'nullable|string',
                'separator_card_thickness' => 'nullable|string',
                'separator_card_dimension' => 'nullable|string',
                'belt_type' => 'nullable|string',
                'belt_detail' => 'nullable|string',
                'pallet_type' => 'nullable|string',
                'gross_weight' => 'nullable|string',
            ];

            $validator2 = Validator::make($request->all(), $rules2);
            if ($validator2->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator2->errors()->first(),
                ], 422);
            }

            $validated2 = $validator2->validated();
            $validated2['wo_id'] = $workOrder->wo_id;
            $validated2['created_by'] = getCreatedBy();
            $validated2['created_at'] = now();
            WorkOrderTermsModel::create($validated2);

            $rules3 = [
                '3layer_sleeve' => 'nullable|string',
                '3layer_sleeve_size' => 'nullable|string',
                '3layer_gel_pads' => 'nullable|string',
                '3layer_gelpad_details' => 'nullable|string',
                '3layer_sleeve_gelpad' => 'nullable|string',
                '2layer_sleeve' => 'nullable|string',
                '2layer_sleeve_size' => 'nullable|string',
                '2layer_gel_pads' => 'nullable|string',
                '2layer_gelpad_details' => 'nullable|string',
                '2layer_sleeve_gelpad' => 'nullable|string',
                '1separator_card_sleeve' => 'nullable|string',
                '1separator_card_sleeve_size' => 'nullable|string',
                '1separator_card_gel_pads' => 'nullable|string',
                '1separator_card_gelpad_details' => 'nullable|string',
                '1separator_card_sleeve_gelpad' => 'nullable|string',
                '1layer_sleeve' => 'nullable|string',
                '1layer_sleeve_size' => 'nullable|string',
                '1layer_gel_pads' => 'nullable|string',
                '1layer_gelpad_details' => 'nullable|string',
                '1layer_sleeve_gelpad' => 'nullable|string',
                '2separator_card_sleeve' => 'nullable|string',
                '2separator_card_sleeve_size' => 'nullable|string',
                '2separator_card_gel_pads' => 'nullable|string',
                '2separator_card_gelpad_details' => 'nullable|string',
                '2separator_card_sleeve_gelpad' => 'nullable|string',
                'long_side_sleeve' => 'nullable|string',
                'long_side_sleeve_size' => 'nullable|string',
                'long_side_gel_pads' => 'nullable|string',
                'long_side_gelpad_details' => 'nullable|string',
                'long_side_sleeve_gelpad' => 'nullable|string',
                'short_side_sleeve' => 'nullable|string',
                'short_side_sleeve_size' => 'nullable|string',
                'short_side_gel_pads' => 'nullable|string',
                'short_side_gelpad_details' => 'nullable|string',
                'short_side_sleeve_gelpad' => 'nullable|string',
                '3separator_card_sleeve' => 'nullable|string',
                '3separator_card_sleeve_size' => 'nullable|string',
                '3separator_card_gel_pads' => 'nullable|string',
                '3separator_card_gelpad_details' => 'nullable|string',
                '3separator_card_sleeve_gelpad' => 'nullable|string',
                'bottom_1layer_sleeve' => 'nullable|string',
                'bottom_1layer_sleeve_size' => 'nullable|string',
                'bottom_1layer_gel_pads' => 'nullable|string',
                'bottom_1layer_gelpad_details' => 'nullable|string',
                'bottom_1layer_sleeve_gelpad' => 'nullable|string',
                'bottom_2layer_sleeve' => 'nullable|string',
                'bottom_2layer_sleeve_size' => 'nullable|string',
                'bottom_2layer_gel_pads' => 'nullable|string',
                'bottom_2layer_gelpad_details' => 'nullable|string',
                'bottom_2layer_sleeve_gelpad' => 'nullable|string',
            ];

            $validator3 = Validator::make($request->all(), $rules3);
            if ($validator3->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator3->errors()->first(),
                ], 422);
            }

            $validated3 = $validator3->validated();
            $validated3['wo_id'] = $workOrder->wo_id;
            $validated3['created_by'] = getCreatedBy();
            $validated3['created_at'] = now();
            WorkOrderPCMModel::create($validated3);
            $rmStock = InventoryModel::pluck('avl_qty', 'rm_id')->toArray();
            $rmReserved = WoMaterialModel::selectRaw('rm_id, SUM(reserved_qty) as total_reserved')
                ->groupBy('rm_id')
                ->pluck('total_reserved', 'rm_id')
                ->toArray();
               foreach ($validated1['rm_id'] as $index => $rmId) {
                $qty = $request->qty[$index] ?? 0;
                $product_id = $request->product_id[$index];
                $product_qty = $request->product_qty[$index];
                $availableStock = $rmStock[$rmId] ?? 0;
                $alreadyReserved = 0;
                $remainingAvailable = max(0, $availableStock - $alreadyReserved );
                $reserved_qty = min($qty, $remainingAvailable);
                $unreserved_qty = max(0, $qty - $reserved_qty);

                WoMaterialModel::create([
                    'wo_id'          => $workOrder->wo_id,
                    'product_id'     => $product_id,
                    'product_qty'    => $product_qty,
                    'rm_id'          => $rmId,
                    'qty_req'        => $qty,
                    'qty'            => $qty,
                    'reserved_qty'   => $reserved_qty,
                    'unreserved_qty' => $unreserved_qty,
                    'status'         => 0,
                    'created_by'     => getCreatedBy(),
                    'created_at'     => now(),
                ]);

                 if ($reserved_qty > 0) {
                    InventoryModel::where('rm_id', $rmId)
                        ->decrement('avl_qty', $reserved_qty); 
                }
                $rmReserved[$rmId] = $alreadyReserved + $reserved_qty;
            }
           if ($request->production_id && $request->old_wo_id) {
             $previousWO = DB::table('tbl_work_order')
            ->where('wo_id', $request->old_wo_id)
            ->first();
                $newWONo = $validated1['work_order_no'];
                $oldWONo =  $previousWO->work_order_no;
                $message = "Work order {$newWONo} has been generated from rejected quantity of work order {$oldWONo}.";
                createproductionhistory($workOrder->wo_id, null, $message);
                DB::table('tbl_production')
                ->where('production_id', $request->production_id)
                ->update([
                    'notrepair_status' => 5, 
                    'updated_at' => now()
                ]);

            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Work Order created successfully.',
                'wo_id' => $workOrder->wo_id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create work order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function list()
    {
        $userType = session('login_type'); 
        $staffId  = session('staff_id');
        $work_order = WorkOrderModel::select([
            'tbl_work_order.wo_id',
            'tbl_work_order.work_order_no',
            'tbl_work_order.date',
            'tbl_work_order.status',
            'tbl_quotation.quotation_no',
            'mst_staff.staff_name',
            'tbl_work_order.reject_remark',
            'tbl_work_order.delivery_date as due_date'
        ])
            ->join('tbl_quotation', 'tbl_quotation.quotation_id', '=', 'tbl_work_order.quotation_id')
            ->leftjoin('mst_staff', 'mst_staff.staff_id', '=', 'tbl_work_order.staff_id')
            ->orderBy('tbl_work_order.wo_id', 'desc');
            if ($userType === 'Staff') {
                $work_order->where('tbl_work_order.staff_id', $staffId);
            }
        return DataTables::of($work_order)
            ->addIndexColumn()

            ->editColumn('date', function ($row)  {
                return \Carbon\Carbon::parse($row->date)->format('d-m-Y');
            })
            ->editColumn('due_date', function ($row) {
                return \Carbon\Carbon::parse($row->due_date)->format('d-m-Y');
            })
            ->addColumn('days_left', function ($row) {
                $due = \Carbon\Carbon::parse($row->due_date);
                $today = \Carbon\Carbon::today();
                $diff = $today->diffInDays($due, false); 
                $due_date = \Carbon\Carbon::parse($row->due_date)->format('d-m-Y').'<br>';
               
                  if ($due->isToday()) {
                         return  $due_date .'<span class="badge bg-warning">Due today</span>';
                    }

                    if ($due->greaterThan($today)) {
                        $diff = $today->diffInDays($due); // positive
                        return  $due_date .'<span class="badge bg-success">' . $diff . ' day' . ($diff > 1 ? 's' : '') . ' left</span>';
                    }

                    // overdue
                    $diff = $today->diffInDays($due); // positive number of days overdue
                    return  $due_date .'<span class="badge bg-danger">Overdue by ' . $diff . ' day' . ($diff > 1 ? 's' : '') . '</span>';
            })

            ->addColumn('wo_status_label', function ($row) use ($userType) {

                switch ($row->status) {
                    case 0:
                        return '<span class="badge bg-warning">Pending</span>';
                    case 1:
                        return '<span class="badge bg-success">Approve</span>';
                    case 2:
                             return '<span class="badge bg-danger reject-remark" 
                                        style="cursor:pointer;" 
                                        data-remark="' . e($row->reject_remark) . '">
                                        Reject
                                    </span>';
                        
                    case 3:
                        return '<span class="badge bg-primary">Material Issue</span>';
                    case 4:
                        return '<span class="badge bg-info">Production</span>';
                    case 5:
                        return '<span class="badge bg-dark">Dispatch</span>';
                    default:
                        return '<span class="badge bg-secondary">Cancelled</span>';
                }
            })

            ->addColumn('action', function ($work_order) use ($userType,$staffId) {
               
            if ($userType === 'Staff' ) {
                    $buttons = '<div class="">';
                    if ($work_order->status == 0) {
                        
                        $buttons .= '
                            <button class="btn btn-sm btn-success approve-btn" data-id="' . $work_order->wo_id . '">
                                <i class="fe fe-check"></i> Approve
                            </button>
                            <button class="btn btn-sm btn-danger reject-btn" data-id="' . $work_order->wo_id . '">
                                <i class="fe fe-x"></i> Reject
                            </button>
                        ';
                    } elseif ($work_order->status == 1) {
                        $buttons .= '
                            <button class="btn btn-sm btn-danger reject-btn" data-id="' . $work_order->wo_id . '">
                                <i class="fe fe-x"></i> Reject
                            </button>
                        ';
                    } elseif ($work_order->status == 2) {
                        $buttons .= '
                            <button class="btn btn-sm btn-success approve-btn" data-id="' . $work_order->wo_id . '">
                                <i class="fe fe-check"></i> Approve
                            </button>
                        ';
                    }

                    $buttons .= '</div>';
                    return $buttons;
            } else {
                return '
                    <div class="">
                        <a href="#" class="btn btn-sm btn-info view-btn" 
                            data-id="' . $work_order->wo_id . '" 
                            data-bs-toggle="modal" 
                            data-bs-target="#workorderModal" 
                            title="Show Work Order"><i class="fe fe-eye"></i></a>
                        <button class="btn btn-sm btn-primary edit-btn" data-id="' . $work_order->wo_id . '"><i class="bx bx-edit"></i></button>
                        <button 
                            class="btn btn-sm btn-danger delete-btn" 
                            data-id="' . $work_order->wo_id . '" 
                            data-name="' . $work_order->work_order_no . '" 
                            data-module="purchase_order"
                            data-table="podata"
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteModal"
                        >
                          <i class="bx bx-trash"></i>
                        </button>
                        <a href="' . route('wopdf', $work_order->wo_id) . '" class="btn btn-sm btn-success" target="_blank"><i class="fe fe-file"></i></a>
                        <a href="' . route('wohistory', $work_order->wo_id) . '" class="btn btn-sm btn-warning"  title="Work Order History"><i class="fe fe-file-text"></i></a>
                    </div>
                ';
            }
            })

            ->rawColumns(['wo_status_label', 'action','days_left'])
            ->make(true);
    }

    public function wo_pdf($wo_id)
    {
        $wo = WorkOrderModel::join('tbl_quotation', 'tbl_work_order.quotation_id', '=', 'tbl_quotation.quotation_id')
            ->select('tbl_work_order.*', 'tbl_quotation.quotation_no as quotation_no', 'tbl_quotation.quotation_date as quotation_date', 'tbl_work_order.date as wo_date')
            ->where('tbl_work_order.wo_id', $wo_id)
            ->first();
       $items = WoMaterialModel::join('tbl_product', 'tbl_product.product_id', '=', 'tbl_wo_raw_material.product_id')
            ->leftJoin('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_product.unit_id')
            ->where('tbl_wo_raw_material.wo_id', $wo_id)
            ->select(
                'tbl_product.product_id',
                'tbl_product.hsn_code',
                'tbl_product.prod_code',
                'tbl_product.prod_name',
                'tbl_unit.unit',
                'tbl_wo_raw_material.product_qty as qty'
            )
            ->distinct('tbl_wo_raw_material.product_id') 
            ->get();


        $wo_pcm = WorkOrderPCMModel::selectRaw("
                `3layer_sleeve` as third_layer_sleeve,
                `3layer_sleeve_size` as thirdlayer_sleeve_size,
                `3layer_gel_pads` as thirdlayer_gel_pads,
                `3layer_gelpad_details` as thirdlayer_gelpad_details,
                `3layer_sleeve_gelpad` as thirdlayer_sleeve_gelpad,

                `2layer_sleeve` as seclayer_sleeve,
                `2layer_sleeve_size` as seclayer_sleeve_size,
                `2layer_gel_pads` as seclayer_gel_pads,
                `2layer_gelpad_details` as seclayer_gelpad_details,
                `2layer_sleeve_gelpad` as seclayer_sleeve_gelpad,

                `1separator_card_sleeve` as firstseparator_card_sleeve,
                `1separator_card_sleeve_size` as firstseparator_card_sleeve_size,
                `1separator_card_gel_pads` as firstseparator_card_gel_pads,
                `1separator_card_gelpad_details` as firstseparator_card_gelpad_details,
                `1separator_card_sleeve_gelpad` as firstseparator_card_sleeve_gelpad,

                `1layer_sleeve` as firstlayer_sleeve,
                `1layer_sleeve_size` as firstlayer_sleeve_size,
                `1layer_gel_pads` as firstlayer_gel_pads,
                `1layer_gelpad_details` as firstlayer_gelpad_details,
                `1layer_sleeve_gelpad` as firstlayer_sleeve_gelpad,

                `2separator_card_sleeve` as secseparator_card_sleeve,
                `2separator_card_sleeve_size` as secseparator_card_sleeve_size,
                `2separator_card_gel_pads` as secseparator_card_gel_pads,
                `2separator_card_gelpad_details` as secseparator_card_gelpad_details,
                `2separator_card_sleeve_gelpad` as secseparator_card_sleeve_gelpad,

                `long_side_sleeve`,
                `long_side_sleeve_size`,
                `long_side_gel_pads`,
                `long_side_gelpad_details`,
                `long_side_sleeve_gelpad`,

                `short_side_sleeve`,
                `short_side_sleeve_size`,
                `short_side_gel_pads`,
                `short_side_gelpad_details`,
                `short_side_sleeve_gelpad`,

                `3separator_card_sleeve` as thirdseparator_card_sleeve,
                `3separator_card_sleeve_size` as thirdseparator_card_sleeve_size,
                `3separator_card_gel_pads` as thirdseparator_card_gel_pads,
                `3separator_card_gelpad_details` as thirdseparator_card_gelpad_details,
                `3separator_card_sleeve_gelpad` as thirdseparator_card_sleeve_gelpad,

                `bottom_1layer_sleeve` as bottom_firstlayer_sleeve,
                `bottom_1layer_sleeve_size` as bottom_firstlayer_sleeve_size,
                `bottom_1layer_gel_pads` as bottom_firstlayer_gel_pads,
                `bottom_1layer_gelpad_details` as bottom_firstlayer_gelpad_details,
                `bottom_1layer_sleeve_gelpad` as bottom_firstlayer_sleeve_gelpad,

                `bottom_2layer_sleeve` as bottom_seclayer_sleeve,
                `bottom_2layer_sleeve_size` as bottom_seclayer_sleeve_size,
                `bottom_2layer_gel_pads` as bottom_seclayer_gel_pads,
                `bottom_2layer_gelpad_details` as bottom_seclayer_gelpad_details,
                `bottom_2layer_sleeve_gelpad` as bottom_seclayer_sleeve_gelpad
            ")->where('wo_id', $wo_id)->first();


        $wo_terms = WorkOrderTermsModel::where('wo_id', $wo_id)->first();
        $wo_material = WoMaterialModel::where('wo_id', $wo_id)
        ->join('tbl_raw_material as rm', 'rm.rm_id', '=', 'tbl_wo_raw_material.rm_id')
        ->select('rm.rm_id', 'rm.name as name', DB::raw('SUM(tbl_wo_raw_material.qty_req) as qty_req'))
        ->groupBy('rm.rm_id', 'rm.name')
        ->get();

        $quotationItems = DB::table('tbl_quotation_item as qi')
            ->join('tbl_product as p', 'p.product_id', '=', 'qi.product_id')
            ->where('qi.quotation_id',  $wo->quotation_id)
            ->select('p.product_id as product_id', 'p.prod_name', 'qi.qty')
            ->get();

        $result = [];

        foreach ($quotationItems as $item) {
            $productRawIds = DB::table('tbl_product_raw_mapping')
                ->where('product_id', $item->product_id)
                ->pluck('product_raw_id');

            $rawMaterialItems = DB::table('tbl_product_raw_items')
                ->whereIn('product_raw_id', $productRawIds)
                ->select('rm_id', 'qty')
                ->get();

            $rawMaterialIds = $rawMaterialItems->pluck('rm_id');
            $rawMaterialsInfo = DB::table('tbl_raw_material')
                ->whereIn('rm_id', $rawMaterialIds)
                ->select('rm_id', 'name')
                ->get()
                ->keyBy('rm_id');

            $rawMaterials = [];

            foreach ($rawMaterialItems as $rmItem) {
                $rmId = $rmItem->rm_id;
                $baseQty = $rmItem->qty;
                $totalQty = $baseQty * $item->qty;

                $rawMaterials[] = [
                    'id' => $rmId,
                    'name' => $rawMaterialsInfo[$rmId]->name ?? '',
                    'base_qty' => $baseQty,
                    'total_qty' => $totalQty,
                ];
            }

            $result[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->prod_name,
                'qty' => $item->qty,
                'raw_materials' => $rawMaterials,
            ];

            $pdf = Pdf::loadView('company/work_order/wo_pdf', compact('wo', 'items', 'wo_terms', 'wo_pcm', 'result', 'wo_material'));
            return $pdf->stream("WO_$wo_id.pdf");
        }
    }


    public function get_data(Request $request){
        $wo_id = $request->input("id");
        $wo = WorkOrderModel::join('tbl_quotation', 'tbl_work_order.quotation_id', '=', 'tbl_quotation.quotation_id')
            ->select('tbl_work_order.*', 'tbl_quotation.quotation_no as quotation_no', 'tbl_quotation.quotation_date as quotation_date', 'tbl_work_order.date as wo_date','tbl_work_order.quotation_id')
            ->where('tbl_work_order.wo_id', $wo_id)
            ->first();
      

          $wo_pcm = WorkOrderPCMModel::selectRaw("
            `3layer_sleeve` as third_layer_sleeve,
            `3layer_sleeve_size` as thirdlayer_sleeve_size,
            `3layer_gel_pads` as thirdlayer_gel_pads,
            `3layer_gelpad_details` as thirdlayer_gelpad_details,
            `3layer_sleeve_gelpad` as thirdlayer_sleeve_gelpad,

            `2layer_sleeve` as seclayer_sleeve,
            `2layer_sleeve_size` as seclayer_sleeve_size,
            `2layer_gel_pads` as seclayer_gel_pads,
            `2layer_gelpad_details` as seclayer_gelpad_details,
            `2layer_sleeve_gelpad` as seclayer_sleeve_gelpad,

            `1separator_card_sleeve` as firstseparator_card_sleeve,
            `1separator_card_sleeve_size` as firstseparator_card_sleeve_size,
            `1separator_card_gel_pads` as firstseparator_card_gel_pads,
            `1separator_card_gelpad_details` as firstseparator_card_gelpad_details,
            `1separator_card_sleeve_gelpad` as firstseparator_card_sleeve_gelpad,

            `1layer_sleeve` as firstlayer_sleeve,
            `1layer_sleeve_size` as firstlayer_sleeve_size,
            `1layer_gel_pads` as firstlayer_gel_pads,
            `1layer_gelpad_details` as firstlayer_gelpad_details,
            `1layer_sleeve_gelpad` as firstlayer_sleeve_gelpad,

            `2separator_card_sleeve` as secseparator_card_sleeve,
            `2separator_card_sleeve_size` as secseparator_card_sleeve_size,
            `2separator_card_gel_pads` as secseparator_card_gel_pads,
            `2separator_card_gelpad_details` as secseparator_card_gelpad_details,
            `2separator_card_sleeve_gelpad` as secseparator_card_sleeve_gelpad,

            `long_side_sleeve`,
            `long_side_sleeve_size`,
            `long_side_gel_pads`,
            `long_side_gelpad_details`,
            `long_side_sleeve_gelpad`,

            `short_side_sleeve`,
            `short_side_sleeve_size`,
            `short_side_gel_pads`,
            `short_side_gelpad_details`,
            `short_side_sleeve_gelpad`,

            `3separator_card_sleeve` as thirdseparator_card_sleeve,
            `3separator_card_sleeve_size` as thirdseparator_card_sleeve_size,
            `3separator_card_gel_pads` as thirdseparator_card_gel_pads,
            `3separator_card_gelpad_details` as thirdseparator_card_gelpad_details,
            `3separator_card_sleeve_gelpad` as thirdseparator_card_sleeve_gelpad,

            `bottom_1layer_sleeve` as bottom_firstlayer_sleeve,
            `bottom_1layer_sleeve_size` as bottom_firstlayer_sleeve_size,
            `bottom_1layer_gel_pads` as bottom_firstlayer_gel_pads,
            `bottom_1layer_gelpad_details` as bottom_firstlayer_gelpad_details,
            `bottom_1layer_sleeve_gelpad` as bottom_firstlayer_sleeve_gelpad,

            `bottom_2layer_sleeve` as bottom_seclayer_sleeve,
            `bottom_2layer_sleeve_size` as bottom_seclayer_sleeve_size,
            `bottom_2layer_gel_pads` as bottom_seclayer_gel_pads,
            `bottom_2layer_gelpad_details` as bottom_seclayer_gelpad_details,
            `bottom_2layer_sleeve_gelpad` as bottom_seclayer_sleeve_gelpad
        ")
        ->where('wo_id', $wo_id)
        ->first();


        $wo_terms = WorkOrderTermsModel::where('wo_id', $wo_id)->first();
        $wo_material = WoMaterialModel::where('wo_id', $wo_id)
        ->join('tbl_raw_material as rm', 'rm.rm_id', '=', 'tbl_wo_raw_material.rm_id')
        ->select('rm.rm_id', 'rm.name as name', DB::raw('SUM(tbl_wo_raw_material.qty_req) as qty_req'))
        ->groupBy('rm.rm_id', 'rm.name')
        ->get();
         $quotationItems = DB::table('tbl_quotation_item as qi')
            ->join('tbl_product as p', 'p.product_id', '=', 'qi.product_id')
            ->Join('tbl_unit', 'tbl_unit.unit_id', '=', 'p.unit_id')
            ->where('qi.quotation_id', $wo->quotation_id)
            ->select('p.product_id as product_id', 'p.prod_name', 'qi.qty','tbl_unit.unit')
            ->get();

        $result = [];

        // foreach ($quotationItems as $item) {
        //     $productRawIds = DB::table('tbl_product_raw_mapping')
        //         ->where('product_id', $item->product_id)
        //         ->pluck('product_raw_id');

        //     $rawMaterialItems = DB::table('tbl_product_raw_items')
        //         ->whereIn('product_raw_id', $productRawIds)
        //         ->select('rm_id', 'qty')
        //         ->get();

        //     $rawMaterialIds = $rawMaterialItems->pluck('rm_id');
        //     $rawMaterialsInfo = DB::table('tbl_raw_material')
        //         ->whereIn('rm_id', $rawMaterialIds)
        //         ->select('rm_id', 'name')
        //         ->get()
        //         ->keyBy('rm_id');
        //     $rawMaterials = [];
        //     foreach ($rawMaterialItems as $rmItem) {
        //         $rmId = $rmItem->rm_id;
        //         $baseQty = $rmItem->qty;
        //         $totalQty = $baseQty * $item->qty;
        //         $rawavlqty = DB::table("tbl_po_inventory")
        //             ->where('rm_id', $rmId)
        //             ->select('avl_qty')
        //             ->first();
        //         $rawMaterials[] = [
        //             'id' => $rmId,
        //             'name' => $rawMaterialsInfo[$rmId]->name ?? '',
        //             'base_qty' => $baseQty,
        //             'total_qty' => $totalQty,
        //             'avl_qty' =>  $rawavlqty->avl_qty ?? '0'
        //         ];
        //     }
        //     $result[] = [
        //         'product_id' => $item->product_id,
        //         'product_name' => $item->prod_name,
        //         'qty' => $item->qty,
        //         'unit' => $item->unit,
        //         'raw_materials' => $rawMaterials,
        //     ];
        // }


        foreach ($quotationItems as $item) {
    // Get raw materials for this product from wo_raw_material table
    $rawMaterials = DB::table('tbl_wo_raw_material as wom')
        ->join('tbl_raw_material as rm', 'rm.rm_id', '=', 'wom.rm_id')
        ->where('wom.wo_id', $wo_id)
        ->where('wom.product_id', $item->product_id)
        ->select(
            'rm.rm_id',
            'rm.name',
            'wom.qty_req as total_qty',
            'wom.qty as base_qty',
           
        )
        ->get();
      //  dd( $rawMaterials);

    $result[] = [
        'product_id' => $item->product_id,
        'product_name' => $item->prod_name,
        'qty' => $item->qty,
        'unit' => $item->unit,
        'raw_materials' => $rawMaterials,
    ];
 
}
        return view('company/work_order/work_order_view_modal', compact('wo','wo_pcm','wo_terms','result','wo_material'));
    }

    public function work_order_approve(Request $request){
        $id = $request->input('id');
     
        $query = WorkOrderModel::where('wo_id',$id)->update(['status'=> 1]);

        if($query){
             return response()->json(['success' => true, 'message' => 'Work Order approved successfully.']);
        }else{
              return response()->json(['success' => false, 'message' => 'Work Order not found.']);
        }
    }

    public function work_order_reject(Request $request){
        $id = $request->input('id');
        $remark = $request->input('remark');
     
        $query = WorkOrderModel::where('wo_id',$id)->update(['status'=> 2,'reject_remark' => $remark]);

        if($query){
             return response()->json(['success' => true, 'message' => 'Work Order reject successfully.']);
        }else{
              return response()->json(['success' => false, 'message' => 'Work Order not found.']);
        }
    }

    public function rejected_work_order(Request $request,$wo_id,$production_id){
        $work_order = WorkOrderModel::where('tbl_work_order.wo_id', $wo_id)
            ->leftJoin('tbl_quotation as q', 'q.quotation_id', '=', 'tbl_work_order.quotation_id')
            ->leftJoin('mst_staff as s', 's.staff_id', '=', 'tbl_work_order.staff_id') // adjust column if different
            ->select(
                'tbl_work_order.*',
                'q.quotation_no',
                's.staff_name as staff_name'
            )
            ->first();

       $wo_pcm = WorkOrderPCMModel::selectRaw("
                `3layer_sleeve` as third_layer_sleeve,
                `3layer_sleeve_size` as thirdlayer_sleeve_size,
                `3layer_gel_pads` as thirdlayer_gel_pads,
                `3layer_gelpad_details` as thirdlayer_gelpad_details,
                `3layer_sleeve_gelpad` as thirdlayer_sleeve_gelpad,

                `2layer_sleeve` as seclayer_sleeve,
                `2layer_sleeve_size` as seclayer_sleeve_size,
                `2layer_gel_pads` as seclayer_gel_pads,
                `2layer_gelpad_details` as seclayer_gelpad_details,
                `2layer_sleeve_gelpad` as seclayer_sleeve_gelpad,

                `1separator_card_sleeve` as firstseparator_card_sleeve,
                `1separator_card_sleeve_size` as firstseparator_card_sleeve_size,
                `1separator_card_gel_pads` as firstseparator_card_gel_pads,
                `1separator_card_gelpad_details` as firstseparator_card_gelpad_details,
                `1separator_card_sleeve_gelpad` as firstseparator_card_sleeve_gelpad,

                `1layer_sleeve` as firstlayer_sleeve,
                `1layer_sleeve_size` as firstlayer_sleeve_size,
                `1layer_gel_pads` as firstlayer_gel_pads,
                `1layer_gelpad_details` as firstlayer_gelpad_details,
                `1layer_sleeve_gelpad` as firstlayer_sleeve_gelpad,

                `2separator_card_sleeve` as secseparator_card_sleeve,
                `2separator_card_sleeve_size` as secseparator_card_sleeve_size,
                `2separator_card_gel_pads` as secseparator_card_gel_pads,
                `2separator_card_gelpad_details` as secseparator_card_gelpad_details,
                `2separator_card_sleeve_gelpad` as secseparator_card_sleeve_gelpad,

                `long_side_sleeve`,
                `long_side_sleeve_size`,
                `long_side_gel_pads`,
                `long_side_gelpad_details`,
                `long_side_sleeve_gelpad`,

                `short_side_sleeve`,
                `short_side_sleeve_size`,
                `short_side_gel_pads`,
                `short_side_gelpad_details`,
                `short_side_sleeve_gelpad`,

                `3separator_card_sleeve` as thirdseparator_card_sleeve,
                `3separator_card_sleeve_size` as thirdseparator_card_sleeve_size,
                `3separator_card_gel_pads` as thirdseparator_card_gel_pads,
                `3separator_card_gelpad_details` as thirdseparator_card_gelpad_details,
                `3separator_card_sleeve_gelpad` as thirdseparator_card_sleeve_gelpad,

                `bottom_1layer_sleeve` as bottom_firstlayer_sleeve,
                `bottom_1layer_sleeve_size` as bottom_firstlayer_sleeve_size,
                `bottom_1layer_gel_pads` as bottom_firstlayer_gel_pads,
                `bottom_1layer_gelpad_details` as bottom_firstlayer_gelpad_details,
                `bottom_1layer_sleeve_gelpad` as bottom_firstlayer_sleeve_gelpad,

                `bottom_2layer_sleeve` as bottom_seclayer_sleeve,
                `bottom_2layer_sleeve_size` as bottom_seclayer_sleeve_size,
                `bottom_2layer_gel_pads` as bottom_seclayer_gel_pads,
                `bottom_2layer_gelpad_details` as bottom_seclayer_gelpad_details,
                `bottom_2layer_sleeve_gelpad` as bottom_seclayer_sleeve_gelpad
            ")
            ->where('wo_id', $wo_id)
            ->first();
        $wo_terms = WorkOrderTermsModel::where('wo_id',$wo_id)->first();
        $wo_material = WoMaterialModel::where('tbl_wo_raw_material.wo_id', $wo_id)
                ->join('tbl_raw_material as rm', 'rm.rm_id', '=', 'tbl_wo_raw_material.rm_id')
                ->join('tbl_production_items as pi', 'pi.product_id', '=', 'tbl_wo_raw_material.product_id')
                ->where('pi.production_id', $production_id)  // filter by production_id
                ->where('pi.rejected_qty', '>', 0)          // only where rejected_qty > 0
                ->select(
                    'rm.rm_id', 
                    'rm.name as name', 
                    DB::raw('SUM(tbl_wo_raw_material.qty_req) as qty_req')
                )
                ->groupBy('rm.rm_id', 'rm.name')
                ->get();

        $page_title = 'Work Order';
        $page_name = 'Work Order';
        $woNo = WorkOrderModel::generateWoNo();
        $userType = session('login_type'); 
        $staffId  = session('staff_id');
        if( $userType == "Staff"){
            $staff = Staffmodel::where("staff_id","!=",$staffId)->get();
        }else{
            $staff = Staffmodel::get();
        }

        return view('company/work_order/new_work_order', compact('page_title', 'page_name','staff','woNo','work_order','wo_pcm','wo_terms','wo_material','production_id','wo_id'));

    }

//    public function getQuotationProductsRejected($id, $production_id)
// {
//     $quotationItems = DB::table('tbl_production_items as qi')
//         ->join('tbl_product as p', 'p.product_id', '=', 'qi.product_id')
//         ->Join('tbl_unit', 'tbl_unit.unit_id', '=', 'p.unit_id')
//         ->where('qi.production_id', $production_id)
//         ->where('qi.rejected_qty', '>', 0)
//         ->select(
//             'p.product_id as product_id',
//             'p.prod_name',
//             'qi.rejected_qty',
//             'tbl_unit.unit',
//             'tbl_unit.unit_id'
//         )
//         ->get();

//     $result = [];

//     foreach ($quotationItems as $item) {
//         $productRawIds = DB::table('tbl_product_raw_mapping')
//             ->where('product_id', $item->product_id)
//             ->pluck('product_raw_id');

//         $rawMaterialItems = DB::table('tbl_product_raw_items')
//             ->whereIn('product_raw_id', $productRawIds)
//             ->select('rm_id', 'qty')
//             ->get();

//         $rawMaterialIds = $rawMaterialItems->pluck('rm_id');

//         $rawMaterialsInfo = DB::table('tbl_raw_material')
//             ->whereIn('rm_id', $rawMaterialIds)
//             ->select('rm_id', 'name')
//             ->get()
//             ->keyBy('rm_id');

//         // unmapped raw materials (not part of mapping)
//         $unmappedRawMaterials = DB::table('tbl_raw_material as rm')
//             ->leftJoin('tbl_po_inventory as pi', 'pi.rm_id', '=', 'rm.rm_id')
//             ->whereNotIn('rm.rm_id', $rawMaterialIds)
//             ->where('rm.status', 0)
//             ->select(
//                 'rm.rm_id',
//                 'rm.name',
//                 DB::raw('COALESCE(pi.avl_qty, 0) as avl_qty')
//             )
//             ->get()
//             ->map(function ($rm) {
//                 $reservedData = DB::table("tbl_wo_raw_material as worm")
//                     ->join('tbl_work_order as wo', 'wo.wo_id', '=', 'worm.wo_id')
//                     ->where('worm.rm_id', $rm->rm_id)
//                     ->where('worm.reserved_qty', '>', 0)
//                     ->select('worm.reserved_qty', 'wo.work_order_no')
//                     ->get();

//                 $totalReservedQty = $reservedData->sum('reserved_qty');
//                 $remainingQty = max(0, $rm->avl_qty);

//                 return (object) [
//                     'id' => $rm->rm_id,
//                     'name' => $rm->name,
//                     'avl_qty' => $rm->avl_qty,
//                     'reserved_qty_total' => $totalReservedQty,
//                     'remaining_qty' => $remainingQty
//                 ];
//             });

//         // raw materials linked with product
//         $rawMaterials = [];
//         foreach ($rawMaterialItems as $rmItem) {
//             $rmId = $rmItem->rm_id;
//             $baseQty = $rmItem->qty;

//             // ⚠️ use rejected_qty instead of missing item->qty
//             $totalQty = $baseQty * $item->rejected_qty;

//             $rawavlqty = DB::table("tbl_po_inventory")
//                 ->where('rm_id', $rmId)
//                 ->select('avl_qty')
//                 ->first();

//             $reservedData = DB::table("tbl_wo_raw_material as worm")
//                 ->join('tbl_work_order as wo', 'wo.wo_id', '=', 'worm.wo_id')
//                 ->where('worm.rm_id', $rmId)
//                 ->where('worm.reserved_qty', '>', 0)
//                 ->select('worm.reserved_qty', 'wo.work_order_no')
//                 ->get();

//             $totalReservedQty = $reservedData->sum('reserved_qty');
//             $remainingQty = max(0, $rawavlqty->avl_qty ?? 0);

//             $reservedDetails = $reservedData->map(function ($row) {
//                 return [
//                     'qty' => $row->reserved_qty,
//                     'wo_no' => $row->work_order_no
//                 ];
//             })->toArray();

//             $rawMaterials[] = [
//                 'id' => $rmId,
//                 'name' => $rawMaterialsInfo[$rmId]->name ?? '',
//                 'base_qty' => $baseQty,
//                 'total_qty' => $totalQty,
//                 'avl_qty' => $rawavlqty->avl_qty ?? 0,
//                 'reserved_qty_total' => $totalReservedQty,
//                 'reserved_details' => $reservedDetails,
//                 'remaining_qty' => $remainingQty
//             ];
//         }

//         $result[] = [
//             'product_id' => $item->product_id,
//             'product_name' => $item->prod_name,
//             'qty' => $item->rejected_qty,
//             'unit' => $item->unit,
//             'unit_id' => $item->unit_id,
//             'raw_materials' => $rawMaterials,
//             'unmapped_raw_materials' => $unmappedRawMaterials
//         ];
//     }

//     return response()->json($result);
// }


public function getQuotationProductsRejected($id, $production_id)
{
    $quotationItems = DB::table('tbl_production_items as qi')
        ->join('tbl_product as p', 'p.product_id', '=', 'qi.product_id')
        ->join('tbl_unit', 'tbl_unit.unit_id', '=', 'p.unit_id')
        ->where('qi.production_id', $production_id)
        ->where('qi.rejected_qty', '>', 0)
        ->select(
            'p.product_id as product_id',
            'p.prod_name',
            'qi.rejected_qty',
            'tbl_unit.unit',
            'tbl_unit.unit_id'
        )
        ->get();

    $result = [];

    // 🔹 Fetch work order ID linked to this production (assuming relationship exists)
    $wo_id = DB::table('tbl_work_order')
        ->where('quotation_id', $id)
        ->value('wo_id');

    foreach ($quotationItems as $item) {

        // ✅ NEW: Fetch raw materials directly from tbl_wo_raw_material
        $rawMaterials = DB::table('tbl_wo_raw_material as wom')
            ->join('tbl_raw_material as rm', 'rm.rm_id', '=', 'wom.rm_id')
            ->where('wom.wo_id', $wo_id)
            ->where('wom.product_id', $item->product_id)
            ->select(
                'rm.rm_id',
                'rm.name',
                'wom.qty_req as total_qty',
                'wom.qty as base_qty'
            )
            ->get();
// dd($rawMaterials);
        // ✅ Optional: You can still compute available/reserved quantities if needed
        $rawMaterials = $rawMaterials->map(function ($rm) {
            $avlQty = DB::table('tbl_po_inventory')
                ->where('rm_id', $rm->rm_id)
                ->value('avl_qty') ?? 0;

            $reservedData = DB::table('tbl_wo_raw_material as worm')
                ->join('tbl_work_order as wo', 'wo.wo_id', '=', 'worm.wo_id')
                ->where('worm.rm_id', $rm->rm_id)
                ->where('worm.reserved_qty', '>', 0)
                ->select('worm.reserved_qty', 'wo.work_order_no')
                ->get();

            $totalReservedQty = $reservedData->sum('reserved_qty');
            $remainingQty = max(0, $avlQty);

            $rm->avl_qty = $avlQty;
            $rm->reserved_qty_total = $totalReservedQty;
            $rm->remaining_qty = $remainingQty;
            $rm->reserved_details = $reservedData->map(function ($r) {
                return [
                    'qty' => $r->reserved_qty,
                    'wo_no' => $r->work_order_no
                ];
            })->toArray();

            return $rm;
        });

        // ✅ Optional: Handle unmapped raw materials (if still needed)
        $unmappedRawMaterials = DB::table('tbl_raw_material as rm')
            ->leftJoin('tbl_po_inventory as pi', 'pi.rm_id', '=', 'rm.rm_id')
            ->whereNotIn('rm.rm_id', $rawMaterials->pluck('rm_id'))
            ->where('rm.status', 0)
            ->select(
                'rm.rm_id',
                'rm.name',
                DB::raw('COALESCE(pi.avl_qty, 0) as avl_qty')
            )
            ->get();

        $result[] = [
            'product_id' => $item->product_id,
            'product_name' => $item->prod_name,
            'qty' => $item->rejected_qty,
            'unit' => $item->unit,
            'unit_id' => $item->unit_id,
            'raw_materials' => $rawMaterials,
            'unmapped_raw_materials' => $unmappedRawMaterials
        ];
    }

    return response()->json($result);
}


public function wo_history($wo_id){
    $wo_history = ProductionHistoryModel::where('wo_id',$wo_id)->get();
    $page_title = 'Work Order';
    $page_name = 'Work Order';
    return view('company/work_order/work_order_history', compact('page_title', 'page_name', 'wo_history','wo_id'));
}

public function wo_history_data($wo_id){
     $wo_history = ProductionHistoryModel::where('wo_id', $wo_id)
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function ($history) {
            return [
                'description' => $history->description,
                'created_at'  => $history->created_at,
                'created_by'  => get_createdby_name($history->created_by), // <-- your helper
            ];
        });

    return response()->json($wo_history);
}


}
