<?php

namespace App\Http\Controllers;
use App\Models\Purchase_inward_Model;
use App\Models\Purchase_return_Model;
use App\Models\Purchase_return_items_Model;
use App\Models\Purchase_inward_items_Model;
use App\Models\Purchase_order_Model;
use App\Models\Purchase_items_Model;
use App\Models\WoMaterialModel;
use App\Models\InventoryModel;
use App\Models\CreditModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
class Purchase_return extends Controller
{
    //
    public function index()
    {
        $page_title = 'PO Return';
        $page_name = 'PO Return';
        $podata = Purchase_inward_Model::where('status', 0)->active()->get();
        return view('company/po/po_return', compact('page_title', 'page_name','podata'));
    }

//  public function po_items(Request $request)
// {
//     $pi_id = $request->input('piid');

//      $returnItems = DB::table('tbl_purchase_return_items')
//         ->select('rm_id', 'batch_no', DB::raw('SUM(return_qty) as total_return_qty'))
//         ->groupBy('rm_id', 'batch_no')
//         ->get()
//         ->keyBy(function ($item) {
//             return $item->rm_id . '_' . $item->batch_no;
//         });
  
//     $inwardItems = DB::table('tbl_purchase_inw_items')
//         ->join('tbl_purchase_inward', 'tbl_purchase_inward.pi_id', '=', 'tbl_purchase_inw_items.pi_id')
//         ->join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_purchase_inw_items.rm_id')
//         ->join('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_purchase_inw_items.unit_id')
//         ->where('tbl_purchase_inward.pi_id', $pi_id)
//         ->select(
//             'tbl_purchase_inw_items.rm_id',
//             'tbl_raw_material.name as raw_material',
//             'tbl_unit.unit',
//             'tbl_unit.unit_id',
//             'tbl_purchase_inw_items.price',
//             'tbl_purchase_inw_items.qty',
//             'tbl_purchase_inw_items.batch_no',
//             'tbl_purchase_inw_items.inw_qty',
//             'tbl_purchase_inw_items.inw_item_id'
//         )
//         ->get();

//     $grouped = $inwardItems->groupBy(function ($item) {
//         return $item->rm_id . '_' . $item->price . '_' . $item->qty;
//     });

//     $result = [];

//     foreach ($grouped as $groupKey => $items) {
//         $first = $items->first();
//         $batches = $items->map(function ($item) use ($returnItems) {
//             $key = $item->rm_id . '_' . $item->batch_no;
//             $returnQty = $returnItems[$key]->total_return_qty ?? 0;

//             return [
//                 'batch_no' => $item->batch_no,
//                 'inw_qty' => $item->inw_qty,
//                 'return_qty' => $returnQty,
//             ];
//         });

//         $totalReturnQty = $batches->sum('return_qty');
//         $result[] = [
//             'inw_item_id' => $first->inw_item_id,
//             'rm_id' => $first->rm_id,
//             'raw_material' => $first->raw_material,
//             'unit' => $first->unit,
//             'unit_id' => $first->unit_id,
//             'price' => $first->price,
//             'qty' => $first->qty,
//             'total_inw_qty' => $items->sum('inw_qty'),
//             'total_return_qty' => $totalReturnQty,
//             'batches' => $batches->values(),
//         ];
//     }

//     return response()->json($result);
// }


// public function store(Request $request)
// {
//     try {
//         $data = $request->all();

//         if (isset($data['items']) && is_string($data['items'])) {
//             $data['items'] = json_decode($data['items'], true);
//         }

//         $rules = [
//             'po_id' => 'required|exists:tbl_purchase_order,po_id',
//             'po_no' => 'required|exists:tbl_purchase_order,po_no',
//             'items' => 'required|array',
//             'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
//         ];

//         $validator = Validator::make($data, $rules);
//         $validator->after(function ($validator) use ($data) {
//             $hasReturnQty = false;

//             foreach ($data['items'] as $item) {
//                 if (!empty($item['batches']) && is_array($item['batches'])) {
//                     foreach ($item['batches'] as $batch) {
//                         if (!empty($batch['return_qty']) && $batch['return_qty'] > 0) {
//                             $hasReturnQty = true;
//                             break 2;
//                         }
//                     }
//                 }
//             }

//             if (!$hasReturnQty) {
//                 $validator->errors()->add('items', 'At least one Return Qty must be entered.');
//             }
//         });

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $validator->errors()->first(),
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         DB::beginTransaction();

//         $totalPOQty = (float) $data['total_po_qty'] ?? 0;
//         $totalInwQty = (float) $data['total_inw_qty'] ?? 0;
//         $totalReturnQty = (float) $data['total_return_qty'] ?? 0;

//         // Handle attachment
//         $attachment = null;
//         if ($request->hasFile('attachment')) {
//             $file = $request->file('attachment');
//             $filename = time() . '_' . $file->getClientOriginalName();
//             $destinationPath = public_path('assets/uploads/return_attach');
//             if (!file_exists($destinationPath)) {
//                 mkdir($destinationPath, 0755, true);
//             }
//             $file->move($destinationPath, $filename);
//             $attachment = '/return_attach/' . $filename;
//         }

//         $purchaseReturn = Purchase_return_Model::create([
//             'po_id'             => $data['po_id'],
//             'po_no'             => $data['po_no'],
//             'total_po_qty'      => $totalPOQty,
//             'total_inw_qty'     => $totalInwQty,
//             'total_return_qty'  => $totalReturnQty,
//             'attachment'        => $attachment,
//             'created_by'        => getCreatedBy(),
//             'created_at'        => now(),
//         ]);

//         foreach ($data['items'] as $item) {
//             $rmId = $item['rm_id'];
//             $unitId = $item['unit_id'];
//             $qty = (float) $item['qty'];
//             $price = (float) $item['price'];

//             if (!empty($item['batches']) && is_array($item['batches'])) {
//                 foreach ($item['batches'] as $batch) {
//                     $returnQty = (float) ($batch['return_qty'] ?? 0);

//                     if ($returnQty <= 0) {
//                         continue;
//                     }

//                     Purchase_return_items_Model::create([
//                         'pr_id'       => $purchaseReturn->pr_id,
//                         'batch_no'    => $batch['batch_no'],
//                         'rm_id'       => $rmId,
//                         'unit_id'     => $unitId,
//                         'qty'         => $qty,
//                         'price'       => $price,
//                         'inw_qty'     => (float) $batch['inw_qty'],
//                         'return_qty'  => $returnQty,
//                         'created_by'  => getCreatedBy(),
//                         'created_at'  => now(),
//                     ]);

//                     updateInventory($rmId, -$returnQty);
//                     $inwardItem = Purchase_inward_items_Model::where('batch_no', $batch['batch_no'])
//                         ->where('rm_id', $rmId)
//                         ->first();

//                     if ($inwardItem) {
//                         $updatedQty = max(0, (float)$inwardItem->inw_qty - $returnQty);
//                         $inwardItem->inw_qty = $updatedQty;
//                         $inwardItem->save();
//                     }
//                 }
//                  $poStatus = ($totalPOQty == $totalReturnQty) ? 1 : 2;
//                 Purchase_order_Model::where('po_id', $data['po_id'])
//                         ->where('po_no', $data['po_no'])
//                         ->update(['po_status' => $poStatus]);

//             }
//         }

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => 'Purchase return saved successfully.'
//         ]);

//     } catch (\Throwable $e) {
//         DB::rollBack();
//         return response()->json([
//             'success' => false,
//             'message' => 'An error occurred!',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }



public function po_items(Request $request)
{
    $pi_id = $request->input('piid');
    $returnItems = DB::table('tbl_purchase_return_items')
        ->select('rm_id', 'batch_no', DB::raw('SUM(return_qty) as total_return_qty'))
        ->groupBy('rm_id', 'batch_no')
        ->get()
        ->keyBy(function ($item) {
            return $item->rm_id . '_' . $item->batch_no;
        });

    $issueItems = DB::table('tbl_material_issue_items')
        ->select('rm_id', 'batch_no', DB::raw('SUM(issue_qty) as total_issue_qty'))
        ->groupBy('rm_id', 'batch_no')
        ->get()
        ->keyBy(function ($item) {
            return $item->rm_id . '_' . $item->batch_no;
        });

    $inwardItems = DB::table('tbl_purchase_inw_items')
        ->join('tbl_purchase_inward', 'tbl_purchase_inward.pi_id', '=', 'tbl_purchase_inw_items.pi_id')
        ->join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_purchase_inw_items.rm_id')
        ->join('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_purchase_inw_items.unit_id')
        ->where('tbl_purchase_inward.pi_id', $pi_id)
        ->select(
            'tbl_purchase_inw_items.rm_id',
            'tbl_raw_material.name as raw_material',
            'tbl_unit.unit',
            'tbl_unit.unit_id',
            'tbl_purchase_inw_items.price',
            'tbl_purchase_inw_items.qty',
            'tbl_purchase_inw_items.batch_no',
            'tbl_purchase_inw_items.inw_qty',
            'tbl_purchase_inw_items.inw_item_id'
        )
        ->get();

    $grouped = $inwardItems->groupBy(function ($item) {
        return $item->rm_id . '_' . $item->price . '_' . $item->qty;
    });

    $result = [];

    foreach ($grouped as $groupKey => $items) {
        $first = $items->first();

        $batches = $items->map(function ($item) use ($returnItems, $issueItems) {
            $key = $item->rm_id . '_' . $item->batch_no;

            $returnQty = $returnItems[$key]->total_return_qty ?? 0;
            $issueQty = $issueItems[$key]->total_issue_qty ?? 0;
            $availableQty = $item->inw_qty - $issueQty;
            if ($availableQty < 0) {
                $availableQty = 0; 
            }
            return [
                'batch_no' => $item->batch_no,
                'inw_qty' => $availableQty,
                'return_qty' => $returnQty,
            ];
        });

        $totalReturnQty = $batches->sum('return_qty');

        $result[] = [
            'inw_item_id' => $first->inw_item_id,
            'rm_id' => $first->rm_id,
            'raw_material' => $first->raw_material,
            'unit' => $first->unit,
            'unit_id' => $first->unit_id,
            'price' => $first->price,
            'qty' => $first->qty,
            'total_inw_qty' => $items->sum('inw_qty'),
            'total_return_qty' => $totalReturnQty,
            'batches' => $batches->values(),
        ];
    }

    return response()->json($result);
}


public function store(Request $request)
{
    try {
        $data = $request->all();
        if (isset($data['items']) && is_string($data['items'])) {
            $data['items'] = json_decode($data['items'], true);
        }
        $rules = [
            'po_id' => 'required|exists:tbl_purchase_order,po_id',
            'po_no' => 'required|exists:tbl_purchase_order,po_no',
            'items' => 'required|array',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ];
        $validator = Validator::make($data, $rules);
        $validator->after(function ($validator) use ($data) {
            $hasReturnQty = false;
            foreach ($data['items'] as $item) {
                if (!empty($item['batches']) && is_array($item['batches'])) {
                    foreach ($item['batches'] as $batch) {
                        if (!empty($batch['return_qty']) && $batch['return_qty'] > 0) {
                            $hasReturnQty = true;
                            break 2;
                        }
                    }
                }
            }
            if (!$hasReturnQty) {
                $validator->errors()->add('items', 'At least one Return Qty must be entered.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }
        DB::beginTransaction();
        $totalPOQty = (float) $data['total_po_qty'] ?? 0;
        $totalInwQty = (float) $data['total_inw_qty'] ?? 0;
        $totalReturnQty = (float) $data['total_return_qty'] ?? 0;

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('assets/uploads/return_attach');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $filename);
            $attachment = '/return_attach/' . $filename;
        }

        $purchaseReturn = Purchase_return_Model::where('po_id', $data['po_id'])
            ->where('po_no', $data['po_no'])
            ->where('status', 0)
            ->first();
        if ($purchaseReturn) {
            $newreturnqty = $purchaseReturn->total_return_qty + $totalReturnQty;
            $newinwqty = $purchaseReturn->total_po_qty - $newreturnqty;
            $purchaseReturn->update([
                'total_po_qty'     => $totalPOQty,
                'total_inw_qty'    => $newinwqty,
                'total_return_qty' => $newreturnqty,
                'attachment'       => $attachment ?? $purchaseReturn->attachment,
                'updated_at'       => now(),
                'updated_by'       => getCreatedBy(),
            ]);
        } else {
            $purchaseReturn = Purchase_return_Model::create([
                'po_id'            => $data['po_id'],
                'po_no'            => $data['po_no'],
                'total_po_qty'     => $totalPOQty,
                'total_inw_qty'    => $totalInwQty - $totalReturnQty,
                'total_return_qty' => $totalReturnQty,
                'attachment'       => $attachment,
                'created_by'       => getCreatedBy(),
                'created_at'       => now(),
            ]);
        }

        foreach ($data['items'] as $item) {
            $rmId = $item['rm_id'];
            $unitId = $item['unit_id'];
            $qty = (float) $item['qty'];
            $price = (float) $item['price'];

            if (!empty($item['batches']) && is_array($item['batches'])) {
                foreach ($item['batches'] as $batch) {
                    $returnQty = (float) ($batch['return_qty'] ?? 0);
                    if ($returnQty <= 0) {
                        continue;
                    }
                        Purchase_return_items_Model::create([
                            'pr_id'       => $purchaseReturn->pr_id,
                            'batch_no'    => $batch['batch_no'],
                            'rm_id'       => $rmId,
                            'unit_id'     => $unitId,
                            'qty'         => $qty,
                            'price'       => $price,
                            'inw_qty'     => (float) $batch['inw_qty'],
                            'return_qty'  => $returnQty,
                            'created_by'  => getCreatedBy(),
                            'created_at'  => now(),
                        ]);
                    
                    updateInventory($rmId, -$returnQty);
                    $inwardItem = Purchase_inward_items_Model::where('batch_no', $batch['batch_no'])
                        ->where('rm_id', $rmId)
                        ->first();

                    if ($inwardItem) {
                        $updatedQty = max(0, (float) $inwardItem->inw_qty - $returnQty);
                        $inwardItem->inw_qty = $updatedQty;
                        $updatedPendingQty = max(0, (float) $inwardItem->pending_qty - $returnQty);
                        $inwardItem->pending_qty = $updatedPendingQty;
                        $inwardItem->save();
                    }
                    
                    $poInventory = InventoryModel::where('rm_id', $rmId)->first();
                    $returnRemaining = $returnQty; 

                    if ($poInventory && $poInventory->avl_qty > 0) {
                        if ($returnRemaining <= $poInventory->avl_qty) {
                            $poInventory->avl_qty -= $returnRemaining;
                            $poInventory->save();
                            $returnRemaining = 0; 
                        } else {
                            $returnRemaining -= $poInventory->avl_qty;
                            $poInventory->avl_qty = 0;
                            $poInventory->save();
                        }
                    }
                    if ($returnRemaining > 0) {
                        $womaterials = WoMaterialModel::where('rm_id', $rmId)
                            ->where('reserved_qty', '>', 0)
                            ->orderBy('wo_rm_id', 'asc')
                            ->get();

                        foreach ($womaterials as $material) {
                            if ($returnRemaining <= 0) break;

                            $deductQty = min($material->reserved_qty, $returnRemaining);
                            $material->reserved_qty -= $deductQty;
                            $material->unreserved_qty += $deductQty;
                            $material->save();
                            $returnRemaining -= $deductQty;
                        }

                        if ($returnRemaining > 0) {
                            $womaterialsZeroReserved = WoMaterialModel::where('rm_id', $rmId)
                                ->where('reserved_qty', '=', 0)
                                ->orderBy('wo_rm_id', 'asc')
                                ->get();
                            foreach ($womaterialsZeroReserved as $material) {
                                if ($returnRemaining <= 0) break;
                                $material->unreserved_qty += $returnRemaining;
                                $material->save();
                                $returnRemaining = 0;
                            }
                        }
                    }


                }
            }
        }

       

        $inward = Purchase_inward_Model::where('pi_id', $data['po_id'])
                        ->where('po_no', $data['po_no'])
                        ->first();
        $newtotalinwqty = $inward->total_inw_qty - $totalReturnQty;
        // dd($newtotalinwqty);
        // exit();
        $poStatus = ($totalPOQty == $newtotalinwqty) ? 1 : 2;
        Purchase_order_Model::where('po_no', $data['po_no'])
            ->update(['po_status' => $poStatus]);
        Purchase_inward_Model::where('pi_id', $data['po_id'])
            ->where('po_no', $data['po_no'])
            ->update(['total_inw_qty' =>  $newtotalinwqty]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Purchase return saved successfully.'
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'An error occurred!',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function list()
{
    $purchase_return = Purchase_return_Model::whereIn('status', [0, 2])
        ->orderBy('tbl_purchase_return.created_at', 'desc')
        ->get()
        ->groupBy('po_no');

    $data = [];
    $i = 1;

    foreach ($purchase_return as $po_no => $rows) {
        $first = $rows->first();
        $total_po_qty = $first->total_po_qty;
        $total_inw_qty  = '';
        $dates          = '';
        $total_return_qty  = '';

        foreach ($rows as $row) {
            $total_inw_qty     .= $row->total_inw_qty . '<br>';
            $total_return_qty  .= $row->total_return_qty;
            $dates             .= Carbon::parse($row->created_at)->format('d-m-Y H:i:s') . '<br>';
        }
        $credit = CreditModel::where('pr_id', $first->pr_id)->first();
        if ($first->status == 2 && $credit) {
            $creditInfo = '<br><small style="color:darkgrey">Generated Credit Note: <b>' . $credit->credit_code .' - '. number_format($credit->credit_amount, 2). '</b></small>';
        } else {
            $creditInfo = '';
        }

        $creditButton = '';
        if ($first->status == 0) {
            $creditButton = '<button class="btn btn-sm btn-success credit-btn" 
                                data-id="' . $first->pr_id . '" 
                                data-po="' . $first->po_no . '" 
                                data-return_qty = "'.$total_return_qty.'"
                                data-bs-toggle="modal" 
                                data-bs-target="#creditModal">Credit</button>';
        }

        $data[] = [
            'DT_RowIndex'      => $i++,
            'po_no'            => $po_no,
            'total_po_qty'     => $total_po_qty, 
            'total_inw_qty'    => $total_inw_qty,
            'total_return_qty' => $total_return_qty . $creditInfo, 
            'date'             => $dates,
            'action' => '
                <div>
                    <a href="#" class="btn btn-sm btn-info view-btn" 
                        data-id="' . $first->pr_id . '" 
                        data-bs-toggle="modal" 
                        data-bs-target="#purchaseInwardModal">View</a>
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $first->pr_id . '">Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . $first->pr_id . '" 
                        data-name="' . $first->po_no . '" 
                        data-module="purchase_return"
                        data-table="poreturndata"
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal">Delete</button>
                        ' . $creditButton . '
                </div>'
        ];
    }

    return DataTables::of($data)
        ->rawColumns(['po_no', 'total_po_qty', 'total_inw_qty','total_return_qty', 'date', 'action'])
        ->make(true);
}

public function get_data(Request $request)
{
    $id = $request->input("id");
    $po_inw = Purchase_return_Model::where("pr_id", $id)
               ->whereIn('status', [0, 2])
                ->first();
    $po_items = Purchase_return_items_Model::with(['raw_material', 'unit'])
                ->where('pr_id', $id)
                ->whereIn('status', [0,1,2])
                ->orderBy('rm_id')
                ->orderBy('batch_no')
                ->get();
    $rawdata = Purchase_items_Model::with(['raw_material', 'unit'])->where('po_no',$po_inw->po_no)->where('status',0)->get();
    return view('company/po/po_return_view_modal', compact('po_inw','po_items','rawdata'));
}

public function get_data_credit(Request $request){
     $pr_id = $request->input("pr_id");
     $po_no = $request->input("po_no");
     $return_qty = $request->input("return_qty");
     $po_items = Purchase_return_items_Model::with(['raw_material', 'unit'])
                ->where('pr_id', $pr_id)
                ->where("status", 0)
                ->orderBy('rm_id')
                ->orderBy('batch_no')
                ->get();
     $crNo = CreditModel::generateCRNo();
        $totalCreditAmount = 0;
    foreach ($po_items as $item) {
        $qty   = $return_qty ?? 0;
        $price = $item->price ?? 0; 
        $totalCreditAmount += ($qty * $price);
    }
     return view('company/po/po_credit_view_modal', compact('po_items','crNo','totalCreditAmount','pr_id','po_no'));

}

public function credit_store(Request $request)
{
    try {
        $validated = $request->validate([
            'po_no'          => 'required|exists:tbl_purchase_order,po_no',
            'pr_id'          => 'required|exists:tbl_purchase_return,pr_id',
            'credit_code' => 'required|string|max:50|unique:tbl_credit,credit_code',
            'credit_date'    => 'required|date',
            'credit_expiry'  => 'required|date|after_or_equal:credit_date',
            'credit_amount'  => 'required|numeric|min:0.01',
            'remark'        => 'nullable|string|max:500',
        ]);
        $data = $validated;
        $data['credit_code'] = CreditModel::generateCRNo();  
        $data['credit_date'] = Carbon::createFromFormat('d-m-Y', $validated['credit_date'])->format('Y-m-d');
        $data['credit_expiry'] = Carbon::createFromFormat('d-m-Y', $validated['credit_expiry'])->format('Y-m-d');
        $data['created_by']  = getCreatedBy();
        $data['created_at']  = now();
        CreditModel::create($data);
        DB::table('tbl_purchase_return')
            ->where('pr_id', $validated['pr_id'])
            ->update([
                'status' => 2,
                'updated_by' => getUpdatedBy(),
                'updated_at' => now()
            ]);

        DB::table('tbl_purchase_order')
            ->where('po_no', $validated['po_no'])
            ->update([
                'po_status' => 1,
                'updated_by' => getUpdatedBy(),
                'updated_at' => now()
            ]);

             DB::table('tbl_purchase_inward')
            ->where('po_no', $validated['po_no'])
            ->update([
                'status' => 1,
                'updated_by' => getUpdatedBy(),
                'updated_at' => now()
            ]);
        return response()->json([
            'success' => true,
            'message' => 'Credit note created successfully!',
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong: ' . $e->getMessage(),
        ], 500);
    }
}


}
