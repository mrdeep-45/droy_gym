<?php

namespace App\Http\Controllers;
use App\Models\ProductionModel;
use App\Models\ProductionItemsModel;
use App\Models\WorkOrderModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
class Final_qc extends Controller
{
    //
      public function index()
    {
        $page_title = 'Final QC';
        $page_name = 'Final QC';
        return view('company/final_qc/final_qc', compact('page_title', 'page_name'));
    }

     public function list(Request $request)
    {
        $data = DB::table('tbl_production as p')
                ->leftJoin('tbl_work_order as w', 'p.wo_id', '=', 'w.wo_id')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(2))
                        ->from('tbl_production_items as pi')
                        ->whereRaw('pi.production_id = p.production_id')
                        ->whereIn('pi.status', [2,1]); 
                })
                ->select(
                    'p.production_id','w.wo_id',
                    'w.work_order_no',
                    'p.production_no',
                    'p.created_at',
                    'p.status',
                    'p.remark',
                    'p.repair_status',
                    'p.notrepair_status'
                )
                ->whereIn('p.status', [3,6])
                ->where(function ($q) {
                    $q->where('p.status', '!=', 3) 
                    ->orWhereNull('p.notrepair_status') 
                    ->orWhere('p.notrepair_status', '!=', 3); 
                })
                ->orderBy('p.production_id', 'desc')
                ->get();


        return datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('created_at', function($row) {
                return Carbon::parse($row->created_at)->format('d-m-Y');
            })
        ->editColumn('status', function($row) {
                 $buttons = '<div class="">';
                    if ($row->status == 3) {
                        
                       $buttons .= '
                            <a href="#" class="btn btn-sm btn-info view-btn" 
                            data-id="' . $row->production_id . '" 
                            data-wo_id="' . $row->wo_id . '" 
                            data-bs-toggle="modal" 
                            data-bs-target="#finalModal" 
                            title="Show Work Order"><i class="fe fe-eye"></i></a>
                        ';
                    } elseif ($row->status == 5) {
                        $buttons .= '
                           <span class="badge bg-success">Accepted</span>
                        ';
                    } elseif ($row->status == 6) {
                        
                        if($row->repair_status == 1){
                            $buttons .= '<span class="badge bg-primary">Repairable</span>';
                        }else if($row->repair_status == 2){
                            $buttons .= '<span class="badge bg-danger">Not Repairable</span>';
                        }

                    }

                    $buttons .= '</div>';
                    return $buttons;
    })
    ->rawColumns(['status'])
            ->make(true);
    }

    public function accept(Request $request){
        $id = $request->input('id');
     
        $query = ProductionModel::where('production_id',$id)->update(['status'=> 5]);

        if($query){
             return response()->json(['success' => true, 'message' => 'Final Order approved successfully.']);
        }else{
              return response()->json(['success' => false, 'message' => 'Final Order not found.']);
        }
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tbl_production,production_id',
            'remark' => 'required|string',
            'condition' => 'required|in:1,2',
        ]);

        try {
            DB::table('tbl_production')
                ->where('production_id', $request->id)
                ->update([
                    'status' => 6, 
                    'reject_remark' => $request->remark,
                    'repair_status' => $request->condition,
                    'updated_at' => now()
                ]);

            return response()->json(['success' => true, 'message' => 'Final Order rejected successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function get_data(Request $request)
    {
        $production_id = $request->input("id");
        $wo_id = $request->input("wo_id");
        $data = DB::table('tbl_production_items')->where('tbl_production_items.production_id', $production_id)
            ->join('tbl_product', 'tbl_product.product_id', '=', 'tbl_production_items.product_id')
            ->select(
                'tbl_production_items.*',
                'tbl_product.prod_name'
            )
            ->get();

        return view('company/final_qc/final_order_view_modal',compact('data','wo_id'));
    }


// public function store(Request $request)
// {
//     $wo_id = $request->input('wo_id');
//     foreach ($request->items as $item) {

//         $pi = ProductionItemsModel::select('tbl_production_items.*', 'tbl_product.prod_code')
//             ->join('tbl_product', 'tbl_product.product_id', '=', 'tbl_production_items.product_id')
//             ->where('tbl_production_items.production_id', $item['production_id'])
//             ->where('tbl_production_items.product_id', $item['product_id'])
//             ->first();

//         if (!$pi) {
//             return response()->json([
//                 'status'  => false,
//                 'message' => "Product {$item['product_id']} not found for this production."
//             ], 422);
//         }

//         $totalQty = (int) $pi->qty;

//         $acceptedInput   = (int) $item['accepted_qty'];
//         $repairableInput = (int) $item['repairable_qty'];
//         $rejectedInput   = (int) $item['rejected_qty'];

//         // Stage 1: Final QC (status 1)
//         if ($pi->status == 1) {
//             if (($acceptedInput + $repairableInput + $rejectedInput) > $totalQty) {
//                 return response()->json([
//                     'status'  => false,
//                     'message' => "Sum of quantities cannot exceed total quantity ({$totalQty}) for product {$item['product_id']}."
//                 ], 422);
//             }

//             $existingRejected   = (int) $pi->rejected_qty;
//             $pi->accepted_qty   = $acceptedInput;
//             $pi->repairable_qty = $repairableInput;
//             $pi->rejected_qty   = $existingRejected + $rejectedInput;

//         } 
//         // Stage 2: Final QC (status 2)
//         elseif ($pi->status == 2) {
//             $existingAccepted   = (int) $pi->accepted_qty;
//             $existingRepairable = (int) $pi->repairable_qty;
//             $existingRejected   = (int) $pi->rejected_qty;

//             if ($acceptedInput > $existingRepairable) {
//                 return response()->json([
//                     'status'  => false,
//                     'message' => "You can only accept up to {$existingRepairable} from repairable items."
//                 ], 422);
//             }

//             $pi->accepted_qty   = $existingAccepted + $acceptedInput;
//             $pi->repairable_qty = $existingRepairable - $acceptedInput - $rejectedInput; 
//             $pi->rejected_qty   = $existingRejected + $rejectedInput;
//         }

//         // Mark item as Final QC done
//         $pi->status = 2;
//         $pi->save();

//         $totals = ProductionItemsModel::where('production_id', $item['production_id'])
//             ->selectRaw('SUM(qty) as total_qty, 
//                          SUM(accepted_qty) as total_accepted, 
//                          SUM(COALESCE(repairable_qty,0)) as total_repairable, 
//                          SUM(COALESCE(rejected_qty,0)) as total_rejected')
//             ->first();

//         $totalQty   = $totals->total_qty;
//         $accepted   = $totals->total_accepted;
//         $repairable = $totals->total_repairable;
//         $rejected   = $totals->total_rejected;

//         // Determine production status
//         $status = null;
//         $repairStatus = null;
//         $notrepairStatus = null;
//         $work_orderno = WorkOrderModel::where('wo_id',$request->wo_id)->first();
//         // Fully rejected case (special)
//         if ($rejected == $totalQty && $accepted == 0 && $repairable == 0) {
//             $status = 5; // fully QC done
//             $notrepairStatus = 4; // mark as fully rejected
//              $description = "Work Order {$work_orderno->work_order_no} is Fully Rejected ({$rejected}) during Final QC";
//         } 
//         // Fully accepted
//         elseif ($accepted == $totalQty && $repairable == 0 && $rejected == 0) {
//             $status = 5;
//             $description = "Work Order {$work_orderno->work_order_no} is Fully Accepted in Final QC";
//         }
//         // Accepted + Repairable
//         elseif ($accepted > 0 && $repairable > 0 && $rejected == 0) {
//             $status = 6;
//             $repairStatus = 1;
//            $description = "Work Order {$work_orderno->work_order_no} is Partially Accepted. Product {$pi->prod_code} has {$repairable} items marked as Repairable during Final QC.";
//         }
//         // Accepted + Rejected
//         elseif ($accepted > 0 && $repairable == 0 && $rejected > 0) {
//             $status = 5;
//             $notrepairStatus = 2;
//           $description = "Work Order {$work_orderno->work_order_no} is Partially Accepted. Product {$pi->prod_code} has {$rejected} items Rejected during Final QC.";
//         }
//         // Accepted + Repairable + Rejected
//         elseif ($accepted > 0 && $repairable > 0 && $rejected > 0) {
//             $status = 6;
//             $repairStatus = 1;
//             $notrepairStatus = 2;
//            $description = "Work Order {$work_orderno->work_order_no} is Accepted. Product {$pi->prod_code} has {$repairable} items Repairable and {$rejected} items Rejected during Final QC.";
//         }
//         // Fully Repairable
//         elseif ($accepted == 0 && $repairable == $totalQty && $rejected == 0) {
//             $status = 6;
//             $repairStatus = 1;
//           $description = "Work Order {$work_orderno->work_order_no} is Fully Repairable ({$repairable}) during Final QC.";
//         }
//         // Repairable + Rejected
//         elseif ($accepted == 0 && $repairable > 0 && $rejected > 0) {
//             $status = 6;
//             $repairStatus = 1;
//             $notrepairStatus = 2;
//              $description = "Work Order {$work_orderno->work_order_no} has Product {$pi->prod_code} with {$repairable} items Repairable and {$rejected} items Rejected during Final QC.";
//         }

//         // Update production table once
//         DB::table('tbl_production')
//             ->where('production_id', $item['production_id'])
//             ->update([
//                 'status'           => $status,
//                 'repair_status'    => $repairStatus,
//                 'notrepair_status' => $notrepairStatus,
//             ]);
//             if ($description) {
//                 createproductionhistory($wo_id, null, $description);
//             }
//     }

//     return response()->json([
//         'status'  => true,
//         'message' => 'Final QC quantities updated successfully!',
//     ]);
// }



public function store(Request $request)
{
    $wo_id = $request->input('wo_id');
    $wo = WorkOrderModel::find($wo_id);
    $allDescriptions = [];
    foreach ($request->items as $item) {

        $pi = ProductionItemsModel::select('tbl_production_items.*', 'tbl_product.prod_code')
            ->join('tbl_product', 'tbl_product.product_id', '=', 'tbl_production_items.product_id')
            ->where('tbl_production_items.production_id', $item['production_id'])
            ->where('tbl_production_items.product_id', $item['product_id'])
            ->first();

        if (!$pi) {
            return response()->json([
                'status'  => false,
                'message' => "Product {$item['product_id']} not found for this production."
            ], 422);
        }

        $totalQty = (int) $pi->qty;

        $acceptedInput   = (int) $item['accepted_qty'];
        $repairableInput = (int) $item['repairable_qty'];
        $rejectedInput   = (int) $item['rejected_qty'];

        // Stage 1: Final QC (status 1)
        if ($pi->status == 1) {
            if (($acceptedInput + $repairableInput + $rejectedInput) > $totalQty) {
                return response()->json([
                    'status'  => false,
                    'message' => "Sum of quantities cannot exceed total quantity ({$totalQty}) for product {$item['product_id']}."
                ], 422);
            }

            $existingRejected   = (int) $pi->rejected_qty;
            $pi->accepted_qty   = $acceptedInput;
            $pi->repairable_qty = $repairableInput;
            $pi->rejected_qty   = $existingRejected + $rejectedInput;

        } 
        // Stage 2: Final QC (status 2)
        elseif ($pi->status == 2) {
            $existingAccepted   = (int) $pi->accepted_qty;
            $existingRepairable = (int) $pi->repairable_qty;
            $existingRejected   = (int) $pi->rejected_qty;

            if ($acceptedInput > $existingRepairable) {
                return response()->json([
                    'status'  => false,
                    'message' => "You can only accept up to {$existingRepairable} from repairable items."
                ], 422);
            }

            $pi->accepted_qty   = $existingAccepted + $acceptedInput;
            $pi->repairable_qty = $existingRepairable - $acceptedInput - $rejectedInput; 
            $pi->rejected_qty   = $existingRejected + $rejectedInput;
        }

     
        $pi->status = 2;
        $pi->save();

        $totals = ProductionItemsModel::where('production_id', $item['production_id'])
            ->selectRaw('SUM(qty) as total_qty, 
                         SUM(accepted_qty) as total_accepted, 
                         SUM(COALESCE(repairable_qty,0)) as total_repairable, 
                         SUM(COALESCE(rejected_qty,0)) as total_rejected')
            ->first();

        $totalQty   = $totals->total_qty;
        $accepted   = $totals->total_accepted;
        $repairable = $totals->total_repairable;
        $rejected   = $totals->total_rejected;

        // Determine production status
        $status = null;
        $repairStatus = null;
        $notrepairStatus = null;
       
        // Fully rejected case (special)
        if ($rejected == $totalQty && $accepted == 0 && $repairable == 0) {
            $status = 5; // fully QC done
            $notrepairStatus = 4; // mark as fully rejected
        } 
        // Fully accepted
        elseif ($accepted == $totalQty && $repairable == 0 && $rejected == 0) {
            $status = 5;
        }
        // Accepted + Repairable
        elseif ($accepted > 0 && $repairable > 0 && $rejected == 0) {
            $status = 6;
            $repairStatus = 1;
        }
        // Accepted + Rejected
        elseif ($accepted > 0 && $repairable == 0 && $rejected > 0) {
            $status = 5;
            $notrepairStatus = 2;
        }
        // Accepted + Repairable + Rejected
        elseif ($accepted > 0 && $repairable > 0 && $rejected > 0) {
            $status = 6;
            $repairStatus = 1;
            $notrepairStatus = 2;
        }
        // Fully Repairable
        elseif ($accepted == 0 && $repairable == $totalQty && $rejected == 0) {
            $status = 6;
            $repairStatus = 1;
        }
        // Repairable + Rejected
        elseif ($accepted == 0 && $repairable > 0 && $rejected > 0) {
            $status = 6;
            $repairStatus = 1;
            $notrepairStatus = 2;
        }

       if ($status) {
            DB::table('tbl_production')
                ->where('production_id', $item['production_id'])
                ->update([
                    'status'           => $status,
                    'repair_status'    => $repairStatus,
                    'notrepair_status' => $notrepairStatus,
                ]);
        }
           $descParts = [];
        if ($pi->accepted_qty > 0)   $descParts[] = "Accepted {$pi->accepted_qty} for {$pi->prod_code}";
        if ($pi->repairable_qty > 0) $descParts[] = "Repairable {$pi->repairable_qty} for {$pi->prod_code}";
        if ($pi->rejected_qty > 0)   $descParts[] = "Rejected {$pi->rejected_qty} for {$pi->prod_code}";

        if (!empty($descParts)) {
            $allDescriptions = array_merge($allDescriptions, $descParts);
        }
           
    }
     $message = "Work order {$wo->wo_no} has been accepted during Final QC.";
    if (!empty($allDescriptions)) {
        $message .= " Final Details: " . implode(", ", $allDescriptions) . ".";
    }

    createproductionhistory($wo_id, null, $message);

    return response()->json([
        'status'  => true,
        'message' => 'Final QC quantities updated successfully!',
    ]);
}

}
