<?php

namespace App\Http\Controllers;
use App\Models\CustomerModel;
use App\Models\VendorModel;
use App\Models\ProductionModel;
use App\Models\WorkOrderModel;
use App\Models\DispatchModel;
use App\Models\DispatchItemsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
class Dispatch extends Controller
{
    //
    public function index()
    {
        $page_title = 'Dispatch';
        $page_name = 'Dispatch';
        $customer = CustomerModel::where('status',0)->get();
        $transporter = VendorModel::where('status',0)->get();
        $production = DB::table('tbl_production')
                    ->join('tbl_work_order', 'tbl_production.wo_id', '=', 'tbl_work_order.wo_id')
                    ->where('tbl_production.status', 5)
                    ->select('tbl_production.*', 'tbl_work_order.work_order_no')
                    ->get();
        $dispatch_no = DispatchModel::generateDPNo();
        return view('company/dispatch/dispatch', compact('page_title', 'page_name','customer','transporter','dispatch_no','production'));
    }


    public function getProductionProducts($wo_id)
    {
       $productionItems = DB::table('tbl_production_items as pi')
                        ->join('tbl_product as p', 'pi.product_id', '=', 'p.product_id')
                        ->where('pi.wo_id', $wo_id)
                        ->whereNotNull('pi.accepted_qty')
                        ->where('pi.accepted_qty', '>', 0)
                        ->select(
                            'pi.product_id',
                            'p.prod_name',
                            DB::raw('SUM(pi.accepted_qty) as qty'),
                            'pi.unit_price'
                        )
                        ->groupBy('pi.product_id', 'p.prod_name', 'pi.unit_price')
                        ->get();
                  
        return response()->json($productionItems);
    }
   public function store(Request $request)
{
    DB::beginTransaction();

    try {
        
        $rules = [
            'wo_id'            => 'required|array',
            'wo_id.*'          => 'required|exists:tbl_production,wo_id',
            'customer_id'     => 'required|exists:tbl_customer,customer_id',
            'transporter_id'  => 'required|exists:tbl_vendor,vendor_id',
            'dispatch_no'     => 'required|string',
            'dispatch_date'   => 'required|date',
            'lr_no'           => 'nullable|string',
            'transport_mode'  => 'nullable|string',
            'billing_address'  => 'nullable|string',
            'dispatch_address' => 'nullable|string',
            'same_address'     => 'nullable|in:0,1',

            // product items
            'product_id'      => 'required|array',
            'product_id.*'    => 'required|exists:tbl_product,product_id',
            'qty'             => 'required|array',
            'qty.*'           => 'required|numeric|min:1',
            'unit_price'      => 'required|array',
            'unit_price.*'    => 'nullable|numeric|min:0',
        ];

        $customAttributes = [
            'production_id'  => 'Production No.',
            'customer_id'    => 'Customer',
            'transporter_id' => 'Transporter',
        ];

        $validator = Validator::make($request->all(), $rules, [], $customAttributes);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $firstField = array_key_first($errors);
            $firstMessage = $errors[$firstField][0];

            return response()->json([
                'success' => false,
                'field'   => $firstField,
                'message' => $firstMessage,
            ], 422);
        }

        $validated = $validator->validated();
        $woIds = $validated['wo_id']; // keep array for later use
        $validated['wo_id'] = implode(',', $validated['wo_id']); 
        // Format date
        $validated['dispatch_date'] = \Carbon\Carbon::parse($validated['dispatch_date'])->format('Y-m-d');
        $validated['dispatch_no']   = DispatchModel::generateDPNo(); 
        $validated['created_by']    = getCreatedBy();
        $validated['created_at']    = now(); 

       $validated['same_address'] = $request->has('same_address') ? 1 : 0;
        if ($validated['same_address'] == 1) {
            $validated['dispatch_address'] = $validated['billing_address'];
        }
        $dispatch = DispatchModel::create($validated);

      
        foreach ($validated['product_id'] as $index => $productId) {
            DispatchItemsModel::create([
                'dispatch_id'  => $dispatch->dispatch_id,
                'product_id'   => $productId,
                'qty'          => $validated['qty'][$index] ?? 0,
                'unit_price'   => $validated['unit_price'][$index] ?? 0,
                'status'       => 1,
                'created_by'   => getCreatedBy(),
                'created_at'   => now(),
            ]);
        }
       
   
         WorkOrderModel::whereIn('wo_id', $woIds)
                        ->update(['status' => 5]);
      

        DB::commit();

        return response()->json([
            'success'     => true,
            'message'     => 'Dispatch created successfully.',
            'dispatch_id' => $dispatch->dispatch_id,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to create dispatch.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function invoice_pdf($dispatch_id){

  $po = DispatchModel::join('tbl_customer', 'tbl_dispatch.customer_id', '=', 'tbl_customer.customer_id')
    ->join('tbl_vendor', 'tbl_dispatch.transporter_id', '=', 'tbl_vendor.vendor_id')
    ->leftJoin('mst_state as cust_state', 'tbl_customer.state_id', '=', 'cust_state.state_id')
    ->leftJoin('mst_state as vend_state', 'tbl_vendor.state_id', '=', 'vend_state.state_id')
    ->select(
        'tbl_dispatch.*',
        'tbl_customer.company_name as customer_name',
        'tbl_customer.address as customer_address',
        'tbl_customer.gst_no as customer_gstin',
        'tbl_customer.state_id as customer_state_id',
        'cust_state.state_name as customer_state_name',
        'cust_state.state_code as customer_state_code',

        'tbl_vendor.vendor_name as vendor_name',
        'tbl_vendor.address as vendor_address',
        'tbl_vendor.gst_no as vendor_gstin',
        'tbl_vendor.state_id as vendor_state_id',
        'vend_state.state_name as vendor_state_name',
        'vend_state.state_code as vendor_state_code'
    )
    ->where('tbl_dispatch.dispatch_id', $dispatch_id)
    ->first();

    $items = DispatchItemsModel::join('tbl_product', 'tbl_product.product_id', '=', 'tbl_dispatch_items.product_id')
        ->leftJoin('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_product.unit_id')
        ->where('tbl_dispatch_items.dispatch_id', $dispatch_id)
        ->select(
            'tbl_dispatch_items.*',
            'tbl_unit.unit as unit','tbl_product.prod_code','tbl_product.prod_name','tbl_product.hsn_code',
        )
        ->get();

    $pdf = Pdf::loadView('company/dispatch/invoice_pdf',compact('po','items')); 
    return $pdf->stream("Invoice_.pdf"); 
}
public function list()
{
    $dispatch = DB::table('tbl_dispatch')
        ->select([
            'tbl_dispatch.dispatch_id',
            'tbl_dispatch.dispatch_no',
            'tbl_dispatch.wo_id',
            'tbl_dispatch.dispatch_date',
            'tbl_dispatch.customer_id',
            'tbl_dispatch.transporter_id',
            'tbl_dispatch.status', // 🔹 include status
            'tbl_customer.company_name as customer_name',
            'tbl_vendor.vendor_name as transporter_name',
        ])
        ->leftJoin('tbl_customer', 'tbl_customer.customer_id', '=', 'tbl_dispatch.customer_id')
        ->leftJoin('tbl_vendor', 'tbl_vendor.vendor_id', '=', 'tbl_dispatch.transporter_id')
        ->orderBy('tbl_dispatch.dispatch_id', 'desc');

    return DataTables::of($dispatch)
        ->addIndexColumn()
        ->editColumn('dispatch_date', function ($row) {
            return \Carbon\Carbon::parse($row->dispatch_date)->format('d-m-Y');
        })
       ->addColumn('work_order_no', function ($row) {
            if (!$row->wo_id) return '';
            $woIds = explode(',', $row->wo_id);
            $workOrders = DB::table('tbl_work_order')
                ->whereIn('wo_id', $woIds)
                ->pluck('work_order_no')
                ->toArray();
            return implode(', ', $workOrders);
        })
        ->addColumn('action', function ($row) {
        if ($row->status == 0) {
            $statusBtn = '<button class="btn btn-sm btn-warning changeStatus" data-id="'.$row->dispatch_id.'">In Transit</button>';
        } else {
            $statusBtn = '<span class="badge bg-success">Dispatched</span>';
        }

    return '
        <div class="d-flex gap-1">
            '.$statusBtn.'
            <a href="' . route('invoicepdf', $row->dispatch_id) . '" class="btn btn-sm btn-success" target="_blank">
                <i class="fe fe-file"></i>
            </a>
        </div>
    ';
})
->rawColumns(['action'])// only "action" now has HTML
        ->make(true);
}
public function updateDispatchStatus(Request $request)
{
    $id = $request->id;

    DB::table('tbl_dispatch')
        ->where('dispatch_id', $id)
        ->update([
            'status' => 1, // change to Dispatched
            'updated_at' => now()
        ]);

    return response()->json(['success' => true, 'message' => 'Status updated successfully!']);
}


}
