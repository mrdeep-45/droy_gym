<?php

namespace App\Http\Controllers;

use App\Models\ProductionItemsModel;
use App\Models\ProductionModel;
use App\Models\WorkOrderModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Internal extends Controller
{
     public function index()
    {
        $page_title = 'Internal QC';
        $page_name = 'Internal QC';
        return view('company/internal/internal_qc', compact('page_title', 'page_name'));
    }

    // public function list(Request $request)
    // {
    //     $data = DB::table('tbl_production as p')
    //         ->leftJoin('tbl_work_order as w', 'p.wo_id', '=', 'w.wo_id')
    //         ->select(
    //             'p.production_id',
    //             'w.work_order_no',
    //             'p.production_no',
    //             'p.created_at',
    //             'p.status',
    //             'p.remark','p.repair_status'
    //         )
    //         ->whereIn('p.status', [2, 4])
    //         ->orderBy('p.production_id', 'desc');

    //     return datatables()->of($data)
    //         ->addIndexColumn()
    //         ->editColumn('created_at', function($row) {
    //             return Carbon::parse($row->created_at)->format('d-m-Y');
    //         })
    //     ->editColumn('status', function($row) {
    //              $buttons = '<div class="">';
    //                 if ($row->status == 2) {
                        
    //                     $buttons .= '
    //                         <button class="btn btn-sm btn-success accept-btn" data-id="' . $row->production_id . '">
    //                             <i class="fe fe-check"></i> Accept
    //                         </button>
    //                         <button class="btn btn-sm btn-danger reject-btn" data-id="' . $row->production_id . '">
    //                             <i class="fe fe-x"></i> Reject
    //                         </button>
    //                     ';
    //                 } elseif ($row->status == 3) {
    //                     $buttons .= '
    //                        <span class="badge bg-success">Accepted</span>
    //                     ';
    //                 } elseif ($row->status == 4) {
                        
    //                     if($row->repair_status == 1){
    //                         $buttons .= '<span class="badge bg-primary">Repairable</span>';
    //                     }else if($row->repair_status == 2){
    //                         $buttons .= '<span class="badge bg-danger">Not Repairable</span>';
    //                     }

    //                 }

    //                 $buttons .= '</div>';
    //                 return $buttons;
    // })
    // ->rawColumns(['status'])
    //         ->make(true);
    // }

    
     /*public function list(Request $request)
    {
        $data = DB::table('tbl_production as p')
            ->leftJoin('tbl_work_order as w', 'p.wo_id', '=', 'w.wo_id')
            ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('tbl_production_items as pi')
                        ->whereRaw('pi.production_id = p.production_id')
                        ->whereIn('pi.status', [1,0]); 
            })
            ->select(
                'p.production_id',
                'w.work_order_no',
                'w.wo_id',
                'p.production_no',
                'p.created_at',
                'p.status',
                'p.remark','p.repair_status'
            )
            ->whereIn('p.status', [2, 4])
            ->orderBy('p.production_id', 'desc');

        return datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('created_at', function($row) {
                return Carbon::parse($row->created_at)->format('d-m-Y');
            })
        ->editColumn('status', function($row) {
                 $buttons = '<div class="">';
                    if ($row->status == 2) {
                        
                        $buttons .= '
                            <a href="#" class="btn btn-sm btn-info view-btn" 
                            data-id="' . $row->production_id . '" 
                            data-bs-toggle="modal" 
                            data-bs-target="#internalModal" 
                            title="Show Work Order"><i class="fe fe-eye"></i></a>
                        ';
                    } elseif ($row->status == 3) {
                        $buttons .= '
                           <span class="badge bg-success">Accepted</span>
                        ';
                    } elseif ($row->status == 4) {
                        
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
    } */
    public function list(Request $request)
    {
   $data = DB::table('tbl_production as p')
        ->leftJoin('tbl_work_order as w', 'p.wo_id', '=', 'w.wo_id')
        ->where(function ($q) {
            $q->where('p.status', 2) // Case 1: production status = 2
              ->orWhereExists(function ($query) { 
                  $query->select(DB::raw(1))
                        ->from('tbl_production_items as pi')
                        ->whereRaw('pi.production_id = p.production_id')
                        ->whereNotNull('pi.repairable_qty')
                        ->where('pi.status',1)
                       ->where('pi.repairable_qty','>',0); // Case 2: any repairable item exists
              });
        })
        ->select(
            'p.production_id',
            'w.work_order_no',
            'w.wo_id',
            'p.production_no',
            'p.created_at',
            'p.status',
            'p.remark',
            'p.repair_status',
            'p.notrepair_status'
        )
        ->orderBy('p.production_id', 'desc')
        ->get();

        return datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('created_at', function($row) {
                return Carbon::parse($row->created_at)->format('d-m-Y');
            })
        ->editColumn('status', function($row) {
                 $buttons = '<div class="">';
                    if ($row->status == 2) {
                        
                        $buttons .= '
                            <a href="#" class="btn btn-sm btn-info view-btn" 
                            data-id="' . $row->production_id . '" 
                            data-wo_id="' . $row->wo_id . '" 
                            data-bs-toggle="modal" 
                            data-bs-target="#internalModal" 
                            title="Show Work Order"><i class="fe fe-eye"></i></a>
                        ';
                    } elseif ($row->status == 3) {
                        $buttons .= '
                           <span class="badge bg-success">Accepted</span>
                        ';
                    } elseif ($row->status == 4) {
                        
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
     
        $query = ProductionModel::where('production_id',$id)->update(['status'=> 3]);

        if($query){
             return response()->json(['success' => true, 'message' => 'Interal Order approved successfully.']);
        }else{
              return response()->json(['success' => false, 'message' => 'Interal Order not found.']);
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
                    'status' => 4, 
                    'reject_remark' => $request->remark,
                    'repair_status' => $request->condition,
                    'updated_at' => now()
                ]);

            return response()->json(['success' => true, 'message' => 'Internal Order rejected successfully']);
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

    return view('company/internal/internal_order_view_modal',compact('data','wo_id'));
}

// public function store(Request $request)
// {
//     $wo_id = $request->input('wo_id');
    
//     foreach ($request->items as $item) {
//        $pi = ProductionItemsModel::select('tbl_production_items.*', 'tbl_product.prod_code')
//             ->join('tbl_product', 'tbl_product.product_id', '=', 'tbl_production_items.product_id')
//             ->where('tbl_production_items.production_id', $item['production_id'])
//             ->where('tbl_production_items.product_id', $item['product_id'])
//             ->first();

//         if (!$pi) {
//             return response()->json([
//                 'status' => false,
//                 'message' => "Invalid product {$item['product_id']} for production {$item['production_id']}."
//             ], 422);
//         }

//         $totalQty = $pi->repairable_qty !== null && $pi->repairable_qty !== ""
//             ? (int) $pi->repairable_qty
//             : (int) $pi->qty;

//         $accepted   = (int) ($item['accepted_qty'] );
//         $repairable = (int) ($item['repairable_qty']);
//       //  dd($repairable);
//         $rejected   = (int) ($item['rejected_qty'] );

//         // if (($accepted + $repairable + $rejected) !== $totalQty) {
//         //     return response()->json([
//         //         'status' => false,
//         //         'message' => "For product {$item['product_id']}, Accepted + Repairable + Rejected must equal {$totalQty}."
//         //     ], 422);
//         // }

      

//         if ($accepted > 0) {
//             $pi->accepted_qty += $accepted;

//             if ($pi->repairable_qty > 0) {
//                 $pi->repairable_qty -= $accepted;
//                 if ($pi->repairable_qty <= 0) {
//                     $pi->repairable_qty = null;
//                 }
//             }
          
//         }

//         if ($repairable >= 0) {
//             $pi->repairable_qty = $repairable;
            
//         }

//         if ($rejected > 0) {
//             $pi->rejected_qty += $rejected;
//         }

//         $pi->status = 1;
//         $pi->save();

//         $totals = ProductionItemsModel::where('production_id', $item['production_id'])
//             ->selectRaw('SUM(qty) as total_qty, 
//                          SUM(accepted_qty) as total_accepted, 
//                          SUM(COALESCE(repairable_qty,0)) as total_repairable, 
//                          SUM(COALESCE(rejected_qty,0)) as total_rejected')
//             ->first();

//             $status = null;
//             $repairStatus = null;
//             $notrepairStatus = null;

//             $totalQty       = $totals->total_qty;
//             $accepted       = $totals->total_accepted;
//             $repairable     = $totals->total_repairable;
//             $rejected       = $totals->total_rejected;

            
//             $status = null;
//             $repairStatus = null;
//             $notrepairStatus = null;
//             $work_orderno = WorkOrderModel::where('wo_id',$wo_id)->first();
//             if ($rejected == $totalQty && $accepted == 0 && $repairable == 0) {
//                 $status = 3; // fully QC done
//                 $notrepairStatus =3; // mark as fully rejected
//                 $description = "Work Order {$work_orderno->work_order_no} is Fully Rejected ({$rejected}) during Internal QC.";
//             } 
//             // Case 1: Fully accepted
//             if ($accepted == $totalQty && $repairable == 0 && $rejected == 0) {
//                 $status = 3;
//                 $description = "Work Order {$work_orderno->work_order_no} is Fully Accepted during Internal QC";
//             }   
//             // Case 2: Accepted + Repairable
//             elseif ($accepted > 0 && $repairable > 0 && $rejected == 0) {
//                 $status = 4;
//                 $repairStatus = 1;
//                 $description = "Work Order {$work_orderno->work_order_no} is Partially Accepted. Product {$pi->prod_code} has {$repairable} items marked as Repairable during Internal QC.";
//             }
//             // Case 3: Accepted + Rejected
//             elseif ($accepted > 0 && $repairable == 0 && $rejected > 0) {
//                 $status = 3;
//                 $notrepairStatus = 2;
//                 $description = "Work Order {$work_orderno->work_order_no} is Partially Accepted. Product {$pi->prod_code} has {$rejected} items Rejected during Internal QC.";
//             }
//             // Case 4: Accepted + Repairable + Rejected
//             elseif ($accepted > 0 && $repairable > 0 && $rejected > 0) {
//                 $status = 4;
//                 $repairStatus = 1;
//                 $notrepairStatus = 2;
//                 $description = "Work Order {$work_orderno->work_order_no} is Accepted. Product {$pi->prod_code} has {$repairable} items Repairable and {$rejected} items Rejected during Internal QC.";
//             }
//             // Case 5: Fully Repairable
//             elseif ($accepted == 0 && $repairable == $totalQty && $rejected == 0) {
//                 $status = 4;
//                 $repairStatus = 1;
//                  $description = "Work Order {$work_orderno->work_order_no} is Fully Repairable ({$repairable}) during Internal QC.";
//             }
           
//             // Case 7: Repairable + Rejected
//             elseif ($accepted == 0 && $repairable > 0 && $rejected > 0) {
//                 $status = 4;
//                 $repairStatus = 1;
//                 $notrepairStatus = 2;
//                 $description = "Work Order {$work_orderno->work_order_no} has Product {$pi->prod_code} with {$repairable} items Repairable and {$rejected} items Rejected during Internal QC.";
//             }


//         if ($status) {
//             DB::table('tbl_production')
//                 ->where('production_id', $item['production_id'])
//                 ->update([
//                     'status'        => $status,
//                     'repair_status' => $repairStatus,
//                     'notrepair_status' => $notrepairStatus,
//                 ]);
//                 if ($description) {
//                     createproductionhistory($wo_id, null, $description);
//                 }
//         }
//     }

//     return response()->json([
//         'status'  => true,
//         'message' => 'Quantities updated successfully!',
//     ]);
// }



// public function store(Request $request)
// {
//     $wo_id = $request->input('wo_id');
//     $historyMessages = [];

//     foreach ($request->items as $item) {
//         $pi = ProductionItemsModel::select('tbl_production_items.*', 'tbl_product.prod_code')
//             ->join('tbl_product', 'tbl_product.product_id', '=', 'tbl_production_items.product_id')
//             ->where('tbl_production_items.production_id', $item['production_id'])
//             ->where('tbl_production_items.product_id', $item['product_id'])
//             ->first();

//         if (!$pi) {
//             return response()->json([
//                 'status'  => false,
//                 'message' => "Invalid product {$item['product_id']} for production {$item['production_id']}."
//             ], 422);
//         }

//         $acceptedInput   = (int) $item['accepted_qty'];
//         $repairableInput = (int) $item['repairable_qty'];
//         $rejectedInput   = (int) $item['rejected_qty'];

//         $existingAccepted   = (int) $pi->accepted_qty;
//         $existingRepairable = $pi->repairable_qty !== null ? (int)$pi->repairable_qty : 0;
//         $existingRejected   = $pi->rejected_qty !== null ? (int)$pi->rejected_qty : 0;

//         $newAccepted   = $existingAccepted + $acceptedInput;
//         $newRepairable = $repairableInput; 
//         $newRejected   = $existingRejected + $rejectedInput;

//         $totalQty = $pi->qty;
//         if ($newAccepted < $totalQty) {
//             $messageParts = [];
//             if ($newRepairable > 0) {
//                 $messageParts[] = "{$newRepairable} items marked as Repairable";
//             }
//             if ($newRejected > 0) {
//                 $messageParts[] = "{$newRejected} items Rejected";
//             }

//             if (!empty($messageParts)) {
//                 $historyMessages[] = "Product {$pi->prod_code} has " . implode(' and ', $messageParts) . " during Internal QC.";
//             }
//         }

//         $pi->accepted_qty += $acceptedInput;
//         $pi->repairable_qty = $repairableInput;
//         $pi->rejected_qty += $rejectedInput;
//         $pi->status = 1;
//         $pi->save();

//         $totals = ProductionItemsModel::where('production_id', $item['production_id'])
//             ->selectRaw('SUM(qty) as total_qty, SUM(accepted_qty) as total_accepted, 
//                          SUM(COALESCE(repairable_qty,0)) as total_repairable, 
//                          SUM(COALESCE(rejected_qty,0)) as total_rejected')
//             ->first();

   

//         $totalQty   = $totals->total_qty;
//         $accepted   = $totals->total_accepted;
//         $repairable = $totals->total_repairable;
//         $rejected   = $totals->total_rejected;
//              $status = null;
//         $repairStatus = null;
//         $notrepairStatus = null;

//         if ($rejected == $totalQty && $accepted == 0 && $repairable == 0) {
//             $status = 3;
//             $notrepairStatus = 3;
//         } elseif ($accepted == $totalQty && $repairable == 0 && $rejected == 0) {
//             $status = 3; 
//         } elseif ($accepted > 0 && $repairable > 0 && $rejected == 0) {
//             $status = 4;
//             $repairStatus = 1;
//         } elseif ($accepted > 0 && $repairable == 0 && $rejected > 0) {
//             $status = 3;
//             $notrepairStatus = 2;
//         } elseif ($accepted > 0 && $repairable > 0 && $rejected > 0) {
//             $status = 4;
//             $repairStatus = 1;
//             $notrepairStatus = 2;
//         } elseif ($accepted == 0 && $repairable == $totalQty && $rejected == 0) {
//             $status = 4;
//             $repairStatus = 1;
//         } elseif ($accepted == 0 && $repairable > 0 && $rejected > 0) {
//             $status = 4;
//             $repairStatus = 1;
//             $notrepairStatus = 2;
//         }

//         DB::table('tbl_production')
//             ->where('production_id', $item['production_id'])
//             ->update([
//                 'status' => $status,
//                 'repair_status' => $repairStatus,
//                 'notrepair_status' => $notrepairStatus,
//             ]);
//     }

//     $work_orderno = WorkOrderModel::where('wo_id', $wo_id)->first();

//     if (!empty($historyMessages)) {
//         $description = "Work Order {$work_orderno->work_order_no} is Partially Accepted. " . implode(" ", $historyMessages);
//     } else {
//         $description = "Work Order {$work_orderno->work_order_no} is Fully Accepted during Internal QC.";
//     }

//     createproductionhistory($wo_id, null, $description);

//     return response()->json([
//         'status' => true,
//         'message' => 'Quantities updated successfully!',
//     ]);
// }




// perfect chale che khali pelu 2 time rejected qty message jay eno issue che 
// public function store(Request $request)
// {
//     $wo_id = $request->input('wo_id');
//     $wo = WorkOrderModel::find($wo_id);

//     foreach ($request->items as $item) {
//         $pi = ProductionItemsModel::select('tbl_production_items.*', 'tbl_product.prod_code')
//             ->join('tbl_product', 'tbl_product.product_id', '=', 'tbl_production_items.product_id')
//             ->where('tbl_production_items.production_id', $item['production_id'])
//             ->where('tbl_production_items.product_id', $item['product_id'])
//             ->first();

//         if (!$pi) {
//             return response()->json([
//                 'status' => false,
//                 'message' => "Invalid product {$item['product_id']} for production {$item['production_id']}."
//             ], 422);
//         }

//         $accepted   = (int) $item['accepted_qty'];
//         $repairable = (int) $item['repairable_qty'];
//         $rejected   = (int) $item['rejected_qty'];

//         if ($accepted > 0) {
//             $pi->accepted_qty += $accepted;

//             if ($pi->repairable_qty > 0) {
//                 $pi->repairable_qty -= $accepted;
//                 if ($pi->repairable_qty <= 0) {
//                     $pi->repairable_qty = null;
//                 }
//             }
//         }

//         if ($repairable >= 0) {
//             $pi->repairable_qty = $repairable;
//         }

//         if ($rejected > 0) {
//             $pi->rejected_qty += $rejected;
//         }

//         $pi->status = 1;
//         $pi->save();

//         // ⚡ KEEPING YOUR LOGIC UNCHANGED
//         $totals = ProductionItemsModel::where('production_id', $item['production_id'])
//             ->selectRaw('SUM(qty) as total_qty, 
//                          SUM(accepted_qty) as total_accepted, 
//                          SUM(COALESCE(repairable_qty,0)) as total_repairable, 
//                          SUM(COALESCE(rejected_qty,0)) as total_rejected')
//             ->first();

//         $status = null;
//         $repairStatus = null;
//         $notrepairStatus = null;

//         $totalQty   = $totals->total_qty;
//         $accepted   = $totals->total_accepted;
//         $repairable = $totals->total_repairable;
//         $rejected   = $totals->total_rejected;

//         $work_orderno = WorkOrderModel::where('wo_id', $wo_id)->first();

//         if ($rejected == $totalQty && $accepted == 0 && $repairable == 0) {
//             $status = 3; // fully QC done
//             $notrepairStatus = 3; // fully rejected
//         }
//         if ($accepted == $totalQty && $repairable == 0 && $rejected == 0) {
//             $status = 3; // fully accepted
//         }
//         elseif ($accepted > 0 && $repairable > 0 && $rejected == 0) {
//             $status = 4;
//             $repairStatus = 1;
//         }
//         elseif ($accepted > 0 && $repairable == 0 && $rejected > 0) {
//             $status = 3;
//             $notrepairStatus = 2;
//         }
//         elseif ($accepted > 0 && $repairable > 0 && $rejected > 0) {
//             $status = 4;
//             $repairStatus = 1;
//             $notrepairStatus = 2;
//         }
//         elseif ($accepted == 0 && $repairable == $totalQty && $rejected == 0) {
//             $status = 4;
//             $repairStatus = 1;
//         }
//         elseif ($accepted == 0 && $repairable > 0 && $rejected > 0) {
//             $status = 4;
//             $repairStatus = 1;
//             $notrepairStatus = 2;
//         }

//         if ($status) {
//             DB::table('tbl_production')
//                 ->where('production_id', $item['production_id'])
//                 ->update([
//                     'status'            => $status,
//                     'repair_status'     => $repairStatus,
//                     'notrepair_status'  => $notrepairStatus,
//                 ]);
//         }
//     } 


   
//  $productionIds = DB::table('tbl_production')
//     ->where('wo_id', $wo_id)
//     ->pluck('production_id');


// $allProducts = ProductionItemsModel::select(
//         'tbl_production_items.*',
//         'tbl_product.prod_code'
//     )
//     ->join('tbl_product', 'tbl_product.product_id', '=', 'tbl_production_items.product_id')
//     ->whereIn('tbl_production_items.production_id', $productionIds)
//     ->get();

//     $allAccepted = true;
//     $allRejected = true;
//     $details = [];

//     foreach ($allProducts as $prod) {
//         $totalQty   = $prod->qty;
//         $accepted   = (int) $prod->accepted_qty;
//         $repairable = (int) $prod->repairable_qty;
//         $rejected   = (int) $prod->rejected_qty;

//         if ($accepted != $totalQty) {
//             $allAccepted = false;
//         }
//         if ($rejected != $totalQty) {
//             $allRejected = false;
//         }
//         if ($repairable > 0) {
//             $details[] = "{$prod->prod_code} has {$repairable} repairable";
//         }
//         if ($rejected > 0) {
//             $details[] = "{$prod->prod_code} has {$rejected} rejected";
//         }
//     }

//     $message = "";
//     if ($allAccepted) {
//         $message = "Work order {$wo->wo_no} has been fully accepted during Internal QC.";
//     } elseif ($allRejected) {
//         $message = "Work order {$wo->wo_no} has been fully rejected during Internal QC.";
//     } else {
//         $message = "Work order {$wo->wo_no} has been partially accepted during Internal QC.";
//         if (count($details)) {
//             $message .= " Details: " . implode(", ", $details) . ".";
//         }
//     }


//     createproductionhistory($wo_id, null, $message);

//     return response()->json([
//         'status'  => true,
//         'message' => 'Quantities updated & history added successfully!',
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
                'status' => false,
                'message' => "Invalid product {$item['product_id']} for production {$item['production_id']}."
            ], 422);
        }

        $accepted   = (int) $item['accepted_qty'];
        $repairable = (int) $item['repairable_qty'];
        $rejected   = (int) $item['rejected_qty'];

        // Update quantities
        if ($accepted > 0) {
            $pi->accepted_qty += $accepted;
            if ($pi->repairable_qty > 0) {
                $pi->repairable_qty -= $accepted;
                if ($pi->repairable_qty <= 0) $pi->repairable_qty = null;
            }
        }
        if ($repairable >= 0) $pi->repairable_qty = $repairable;
        if ($rejected > 0) $pi->rejected_qty += $rejected;
        $pi->status = 1;
        $pi->save();
        $totals = ProductionItemsModel::where('production_id', $item['production_id'])
            ->selectRaw('SUM(qty) as total_qty, 
                         SUM(accepted_qty) as total_accepted, 
                         SUM(COALESCE(repairable_qty,0)) as total_repairable, 
                         SUM(COALESCE(rejected_qty,0)) as total_rejected')
            ->first();

        $status = null;
        $repairStatus = null;
        $notrepairStatus = null;

        $totalQty   = $totals->total_qty;
        $acceptedTotal   = $totals->total_accepted;
        $repairableTotal = $totals->total_repairable;
        $rejectedTotal   = $totals->total_rejected;

        if ($rejectedTotal == $totalQty && $acceptedTotal == 0 && $repairableTotal == 0) {
            $status = 3; 
            $notrepairStatus = 3; 
        }
        if ($acceptedTotal == $totalQty && $repairableTotal == 0 && $rejectedTotal == 0) {
            $status = 3; 
        }
        elseif ($acceptedTotal > 0 && $repairableTotal > 0 && $rejectedTotal == 0) {
            $status = 4;
            $repairStatus = 1;
        }
        elseif ($acceptedTotal > 0 && $repairableTotal == 0 && $rejectedTotal > 0) {
            $status = 3;
            $notrepairStatus = 2;
        }
        elseif ($acceptedTotal > 0 && $repairableTotal > 0 && $rejectedTotal > 0) {
            $status = 4;
            $repairStatus = 1;
            $notrepairStatus = 2;
        }
        elseif ($acceptedTotal == 0 && $repairableTotal == $totalQty && $rejectedTotal == 0) {
            $status = 4;
            $repairStatus = 1;
        }
        elseif ($acceptedTotal == 0 && $repairableTotal > 0 && $rejectedTotal > 0) {
            $status = 4;
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
        if ($accepted > 0)   $descParts[] = "Accepted $accepted for {$pi->prod_code}";
        if ($repairable > 0) $descParts[] = "Repairable $repairable for {$pi->prod_code}";
        if ($rejected > 0)   $descParts[] = "Rejected $rejected for {$pi->prod_code}";

        if (!empty($descParts)) {
            $allDescriptions = array_merge($allDescriptions, $descParts);
        }
    } 
    $message = "Work order {$wo->wo_no} has been accepted during Internal QC.";
    if (!empty($allDescriptions)) {
        $message .= " Details: " . implode(", ", $allDescriptions) . ".";
    }

    createproductionhistory($wo_id, null, $message);

    return response()->json([
        'status'  => true,
        'message' => 'Quantities updated & history added successfully!',
    ]);
}



}
