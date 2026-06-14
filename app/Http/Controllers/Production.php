<?php

namespace App\Http\Controllers;
use App\Models\WorkOrderModel;
use App\Models\ProductionModel;
use App\Models\ProductionItemsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
class Production extends Controller
{
    //
     public function index()
    {
        $page_title = 'Production';
        $page_name = 'Production';
        $woNo = WorkOrderModel::where('status',1)->get();
         $PRNo = ProductionModel::generatePRNo();
        return view('company/production/production', compact('page_title', 'page_name','woNo','PRNo'));
    }
    /*
  public function getWorkOrderMaterialIssues($wo_id)
{
    $materials = DB::table('tbl_material_issue')
        ->where('wo_id', $wo_id)
        ->get();

    $raw_materials = [];

    foreach ($materials as $material) {
        $materialItems = DB::table('tbl_material_issue_items as mii')
            ->join('tbl_raw_material as rm', 'rm.rm_id', '=', 'mii.rm_id')
            ->where('mii.material_id', $material->material_id)
            ->select('mii.rm_id', 'rm.name', 'mii.batch_no', 'mii.qty as qty_req', 'mii.issue_qty as qty_issued')
            ->get();

        foreach ($materialItems as $item) {
            $raw_materials[] = [
                 'rm_id' => $item->rm_id,
                'name' => $item->name,
                'batch_no' => $item->batch_no,
                'qty_req' => $item->qty_req,
                'qty_issued' => $item->qty_issued,
            ];
        }
    }

    return response()->json([
        'raw_materials' => $raw_materials
    ]);
}



public function store(Request $request)
{
    $request->validate([
        'wo_id' => 'required|exists:tbl_work_order,wo_id',
        'production_no' => 'required|string',
        'materials' => 'required|json',
    ]);

    try {
        DB::beginTransaction();

        $production = ProductionModel::create([
            'wo_id' => $request->wo_id,
            'production_no' => $request->production_no,
            'status' => 0,
            'created_by' =>  getCreatedBy(),
            'created_at' => now()
        ]);

        $materials = json_decode($request->materials, true);

        foreach ($materials as $material) {
            ProductionItemsModel::create([
                'production_id' => $production->production_id,
                'rm_id' => $material['rm_id'],
                'batch_no' => $material['batch_no'],
                'qty' => $material['qty'],
                'issue_qty' => $material['issue_qty'],
                'status' => 0,
                'created_by' => getCreatedBy(),
                'created_at' => now()
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'material_id' => $production->production_id
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to save data. ' . $e->getMessage()
        ], 500);
    }
}
    */
public function getWorkOrderProducts($wo_id)
{
    $wo = DB::table('tbl_work_order')->where('wo_id', $wo_id)->first();

    if (!$wo) {
        return response()->json(['products' => []]);
    }

    $products = DB::table('tbl_wo_raw_material as qi')
    ->join('tbl_product as p', 'p.product_id', '=', 'qi.product_id')
    ->where('qi.wo_id', $wo->wo_id)
    ->select(
        'qi.product_id',
        'p.prod_name',
        DB::raw('MAX(qi.product_qty) as qty'),
        DB::raw('SUM(qi.qty) as unit_price')
    )
    ->groupBy('qi.product_id', 'p.prod_name')
    ->get();

    return response()->json([
        'products' => $products
    ]);
}
public function store(Request $request)
{
    $request->validate([
        'wo_id' => 'required|exists:tbl_work_order,wo_id',
        'production_no' => 'required|string',
        'products' => 'required|json',  
    ]);

    try {
        DB::beginTransaction();

        $production = ProductionModel::create([
            'wo_id' => $request->wo_id,
            'production_no' => $request->production_no,
            'status' => 0,
            'created_by' => getCreatedBy(),
            'created_at' => now()
        ]);

        $products = json_decode($request->products, true);

        foreach ($products as $prod) {
            ProductionItemsModel::create([
                'production_id' => $production->production_id,
                'wo_id'         => $request->wo_id,
                'product_id'    => $prod['product_id'],
                'qty'           => $prod['qty'],
                'unit_price'    => $prod['unit_price'],
                'status'        => 0,
                'created_by'    => getCreatedBy(),
                'created_at'    => now()
            ]);
        }

        WorkOrderModel::where('wo_id', $request->wo_id)
            ->where('status', 1)  // only update if status is 1
            ->update(['status' => 4]); // production
        $work_orderno = WorkOrderModel::where('wo_id',$request->wo_id)->first();
        $description = 'Work Order '.$work_orderno->work_order_no.' against create Production '.$request->production_no;
        createproductionhistory($request->wo_id, $product_id = null,$description);

        DB::commit();

        return response()->json([
            'success' => true,
            'production_id' => $production->production_id
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to save data. ' . $e->getMessage()
        ], 500);
    }
}
public function list(Request $request)
{
    $data = DB::table('tbl_production as p')
        ->leftJoin('tbl_work_order as w', 'p.wo_id', '=', 'w.wo_id')
        ->select(
            'p.production_id',
            'w.wo_id',
            'w.work_order_no',
            'p.production_no',
            'p.created_at',
            'p.status',
            'p.remark','p.repair_status','p.notrepair_status'
        )
        ->orderBy('p.production_id', 'desc');

    return datatables()->of($data)
        ->addIndexColumn()
         ->editColumn('created_at', function($row) {
            return Carbon::parse($row->created_at)->format('d-m-Y');
        })
       ->editColumn('status', function($row) {
            if ($row->status == 1) {
                  return '<button class="btn btn-sm btn-primary changeStatus" 
                    data-id="'.$row->production_id.'" 
                    data-wo_id="'.$row->wo_id.'" 
                    data-status="1" 
                    data-remark="'.$row->remark.'">In Process</button>';
            }elseif ($row->status == 4   && $row->repair_status == '1' ){
                  return '<button class="btn btn-sm btn-primary changeStatus" 
                    data-id="'.$row->production_id.'" 
                    data-wo_id="'.$row->wo_id.'" 
                    data-status="1" 
                    data-remark="'.$row->remark.'">In Process </button> <br> <small> Reject - Repairable (Internal QC)</small>';
                   
            }elseif ($row->status == 6 && $row->repair_status == '1' ){
                  return '<button class="btn btn-sm btn-primary changeStatus" 
                    data-id="'.$row->production_id.'"
                    data-wo_id="'.$row->wo_id.'"  
                    data-status="1" 
                    data-remark="'.$row->remark.'">In Process </button> <br> <small> Reject - Repairable (Final QC)</small>';
                   
            }
            elseif ($row->status == 4   && $row->repair_status == '2' ){
                     return '<span class="badge bg-danger">Reject - Not Repairable (Internal QC)</span>';
            }
            elseif ($row->status == 6 && $row->repair_status == '2' ){
                     return '<span class="badge bg-danger">Reject - Not Repairable (Final QC)</span>';
            }
            elseif ($row->status == 0) {
                return '<button class="btn btn-sm btn-warning changeStatus" data-id="'.$row->production_id.'" data-wo_id="'.$row->wo_id.'" >Pending</button>';
            } elseif ($row->status == 2) {
                return '<span class="badge bg-success">Completed</span>';
            }
            elseif($row->status == 3  && $row->notrepair_status == 3){
                return '<span class="badge bg-danger">Internal QC Rejected</span>';
            }
            elseif($row->status == 3 ){
                return '<span class="badge bg-success">Internal QC Accepted</span>';
            }
            elseif($row->status == 5 && $row->notrepair_status == 4){ 
                return '<span class="badge bg-danger">Final QC Rejected</span>';
            }
            elseif($row->status == 5 ){ 
                return '<span class="badge bg-success">Final QC Accepted</span>';
            }

})
->rawColumns(['status'])
        ->make(true);
}


public function updateStatus(Request $request)
{
    try {
        $statusToUpdate = $request->status;

        // If requested status = 2, check tbl_production_items
        if ($request->status == 2) {
            $totalItems = DB::table('tbl_production_items')
                ->where('production_id', $request->id)
                ->whereColumn('qty', '!=', 'rejected_qty')
                ->count();

            $itemsWithStatus2 = DB::table('tbl_production_items')
                ->where('production_id', $request->id)
                ->whereColumn('qty', '!=', 'rejected_qty')
                ->where('status', 2)
                ->count();

            if ($totalItems > 0 && $totalItems == $itemsWithStatus2) {
                $statusToUpdate = 3;
            }
        }

        $updated = DB::table('tbl_production')
            ->where('production_id', $request->id)
            ->update([
                'status' => $statusToUpdate,
                'remark' => $request->remark,
                'updated_at' => now()
            ]);

        if ($updated) {
            $work_orderno = WorkOrderModel::where('wo_id',$request->wo_id)->first();
            if ($statusToUpdate == 1) {
                $description = 'Work Order '.$work_orderno->work_order_no.' in Production module is In Process';
            } elseif ($statusToUpdate == 2) {
                $description = 'Work Order '.$work_orderno->work_order_no.' in Production module is Completed';
            } else {
                $description = 'Work Order '.$work_orderno->work_order_no.' in Production module status updated';
            }

            createproductionhistory($request->wo_id, $product_id = null, $description);
            return response()->json([
                'success' => true, 
                'message' => 'Status updated successfully',
                'status' => $statusToUpdate
            ]);
        } else {
            return response()->json([
                'success' => false, 
                'message' => 'No record updated. Please check Production ID.'
            ], 400);
        }

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}






}
