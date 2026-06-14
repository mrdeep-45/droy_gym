<?php

namespace App\Http\Controllers;

use App\Models\Purchase_order_Model;
use App\Models\Purchase_items_Model;
use App\Models\Purchase_inward_Model;
use App\Models\Purchase_inward_items_Model;
use App\Models\WoMaterialModel;
use App\Models\Purchase_return_model;
use App\Models\Purchase_return_items_model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Purchase_inward extends Controller
{
    //

    public function index()
    {
        $page_title = 'PO Inward';
        $page_name = 'PO Inward';
        $podata = Purchase_order_Model::with('supplier')->whereIn('po_status', [0, 2])->active()->get();
        return view('company/po/po_inward', compact('page_title', 'page_name','podata'));
    }

 public function get_po_items(Request $request)
{
    $po_id = $request->input('poid');

    $materials = DB::table('tbl_purchase_items')
        ->join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_purchase_items.rm_id')
        ->join('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_purchase_items.unit_id')
        ->where('tbl_purchase_items.po_id', $po_id)
        ->select(
            'tbl_raw_material.name as raw_material',
            'tbl_raw_material.rm_id',
            'tbl_unit.unit',
            'tbl_unit.unit_id',
            'tbl_purchase_items.qty',
            'tbl_purchase_items.price'
        )
        ->get();

    foreach ($materials as $item) {
        $totalInwQty = DB::table('tbl_purchase_inw_items')
            ->join('tbl_purchase_inward', 'tbl_purchase_inward.pi_id', '=', 'tbl_purchase_inw_items.pi_id')
            ->where('tbl_purchase_inward.po_id', $po_id)
            ->where('tbl_purchase_inw_items.rm_id', $item->rm_id)
            ->sum('tbl_purchase_inw_items.inw_qty');
        $item->remaining_qty = max($item->qty - $totalInwQty, 0); 
    }

    return response()->json($materials);
}



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
//             'items.*.inw_qty' => 'required|numeric|min:0.01',
//             'items.*.rm_id' => 'required|integer|exists:tbl_raw_material,rm_id',
//             'items.*.unit_id' => 'required|integer',
//             'items.*.qty' => 'required|numeric|min:0',
//             'items.*.price' => 'required|numeric|min:0',
//             'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
//         ];

//         $customMessages = [];
//         if (isset($data['items']) && is_array($data['items'])) {
//             foreach ($data['items'] as $index => $item) {
//                 $rmIdentifier = $item['rm_name'] ?? ($item['rm_id'] ?? "Unknown Material");

//                 $customMessages["items.$index.inw_qty.required"] = "Inward Qty is required for $rmIdentifier";
//                 $customMessages["items.$index.inw_qty.min"] = "Inward Qty must be more than 0 for $rmIdentifier";
//             }
//         }

//         $validator = Validator::make($data, $rules, $customMessages);

//         if ($validator->fails()) {
//             $errors = $validator->errors()->toArray();
//             $firstField = array_key_first($errors);
//             $firstMessage = $errors[$firstField][0];

//             return response()->json([
//                 'success' => false,
//                 'field' => $firstField,
//                 'message' => $firstMessage,
//                 'errors' => $errors
//             ], 422);
//         }

//         DB::beginTransaction();

//         $totalPOQty = collect($data['items'])->sum('qty');
//         $totalInwQty = collect($data['items'])->sum('inw_qty');
//         $attachment = "";
//          if ($request->hasFile('attachment')) {
//             $file = $request->file('attachment');
//             $filename = time() . '_' . $file->getClientOriginalName();
//             $destinationPath = public_path('assets/uploads/inward_attach');
//             if (!file_exists($destinationPath)) {
//                 mkdir($destinationPath, 0755, true);
//             }
//             $file->move($destinationPath, $filename);
//             $attachment = '/inward_attach/' . $filename; 
//         }

//         $pi = Purchase_inward_Model::create([
//             'po_id' => $data['po_id'],
//             'po_no' => $data['po_no'],
//             'total_po_qty' => $totalPOQty,
//             'total_inw_qty' => $totalInwQty,
//             'attachment' => $attachment,
//             'created_by' => getCreatedBy(),
//             'created_at' => now(),
//         ]);

//         foreach ($data['items'] as $item) {
//           Purchase_inward_items_Model::create([
//                 'pi_id' => $pi->pi_id,
//                 'rm_id' => $item['rm_id'],
//                 'unit_id' => $item['unit_id'],
//                 'qty' => $item['qty'],
//                 'price' => $item['price'],
//                 'inw_qty' => $item['inw_qty'],
//                 'created_by' => getCreatedBy(),
//                 'created_at' => now(),
//             ]);
//             updateInventory($item['rm_id'], $item['inw_qty']);
//         }


//         $totalPOQty = Purchase_items_Model::whereHas('order', function ($query) use ($data) {
//             $query->where('po_no', $data['po_no']);
//         })->sum('qty');

//         $totalInwardedQty = Purchase_inward_Model::where('po_no', $data['po_no'])
//             ->sum('total_inw_qty');

//         $poStatus = ($totalPOQty == $totalInwardedQty) ? 1 : 2;

//         Purchase_order_Model::where('po_id', $data['po_id'])
//             ->where('po_no', $data['po_no'])
//             ->update(['po_status' => $poStatus]);
         

//         DB::commit();

//         return response()->json(['success' => true, 'message' => 'Inward entry saved successfully.']);
//     } catch (\Throwable $e) {
//         DB::rollBack(); 
//         return response()->json([
//             'success' => false,
//             'message' => 'Unexpected error occurred!',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }




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
            'items.*.inw_qty' => 'nullable|numeric|min:0',
            'items.*.rm_id' => 'required|integer|exists:tbl_raw_material,rm_id',
            'items.*.unit_id' => 'required|integer',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ];

        $customMessages = [
            'items.required' => 'No items found for the selected PO.',
        ];
        
        $validator = Validator::make($data, $rules, $customMessages);
        $validator->after(function ($validator) use ($data) {
            if (isset($data['items']) && is_array($data['items'])) {
                $nonEmptyCount = collect($data['items'])
                    ->filter(function ($item) {
                        return isset($item['inw_qty']) && $item['inw_qty'] > 0;
                    })
                    ->count();

                if ($nonEmptyCount === 0) {
                    $validator->errors()->add('items', 'At least one Inward Qty must be entered.');
                }
            }
        });
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $firstField = array_key_first($errors);
            $firstMessage = $errors[$firstField][0];

            return response()->json([
                'success' => false,
                'field' => $firstField,
                'message' => $firstMessage,
                'errors' => $errors
            ], 422);
        }

        DB::beginTransaction();

        foreach ($data['items'] as $item) {
            $inwQty = (float) ($item['inw_qty'] ?? 0);

            if ($inwQty <= 0) {
                continue;
            }

            $existingInwardQty = (float) Purchase_inward_items_Model::whereHas('inward', function ($q) use ($data) {
                $q->where('po_no', $data['po_no']);
            })->where('rm_id', $item['rm_id'])->sum('inw_qty');

            $totalAfterCurrent = $existingInwardQty + $inwQty;

            if ($totalAfterCurrent > (float) $item['qty']) {
                return response()->json([
                    'success' => false,
                    'message' => "Inward quantity for RM ID {$item['rm_id']} exceeds PO quantity.",
                ], 422);
            }
        }

    //    $lastBatch = Purchase_inward_items_Model::whereHas('inward', function ($q) use ($data) {
    //         $q->where('po_no', $data['po_no']);
    //     })->orderByDesc(DB::raw("CAST(SUBSTRING(batch_no, 4) AS UNSIGNED)"))->value('batch_no');

    //     $lastBatchNumber = $lastBatch ? (int)substr($lastBatch, 3) : 0;
    //     $batchNo = 'IRN' . ($lastBatchNumber + 1);
           

        $existingPI = Purchase_inward_Model::where('po_id', $data['po_id'])
                        ->where('po_no', $data['po_no'])
                        ->first();

        $totalPOQty = collect($data['items'])->sum(function ($item) {
            return (float) ($item['qty'] ?? 0);
        });

        $totalInwQty = collect($data['items'])->sum(function ($item) {
            return (float) ($item['inw_qty'] ?? 0);
        });

        $attachment = "";
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('assets/uploads/inward_attach');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $filename);
            $attachment = '/inward_attach/' . $filename;
        }

        if ($existingPI) {
                $existingPI->total_inw_qty = $existingPI->total_inw_qty + $totalInwQty;
                if ($attachment) {
                    $existingPI->attachment = $attachment;
                }
                $existingPI->updated_at = now();
                $existingPI->updated_by = getCreatedBy();
                $existingPI->save();
                $pi = $existingPI; 
            } else {
                 
                $pi = Purchase_inward_Model::create([
                    'po_id' => $data['po_id'],
                    'po_no' => $data['po_no'],
                    'total_po_qty' => $totalPOQty,
                    'total_inw_qty' => $totalInwQty,
                    'attachment' => $attachment,
                    'created_by' => getCreatedBy(),
                    'created_at' => now(),
                ]);
            }
            $inward_number = Purchase_inward_items_Model::generateIRNNo();
            foreach ($data['items'] as $item) {
                $inwQty = (float) ($item['inw_qty'] ?? 0);
                if ($inwQty <= 0) {
                    continue;
                }
                Purchase_inward_items_Model::create([
                    'pi_id'       => $pi->pi_id,
                    'batch_no'    => $inward_number,
                    'rm_id'       => $item['rm_id'],
                    'unit_id'     => $item['unit_id'],
                    'qty'         => (float) $item['qty'],
                    'price'       => (float) $item['price'],
                    'inw_qty'     => $inwQty,
                    'pending_qty' => $inwQty,
                    'created_by'  => getCreatedBy(),
                    'created_at'  => now(),
                ]);

                $remainingInward = $inwQty;
               $unreservedList = WoMaterialModel::where('rm_id', $item['rm_id'])
                                ->whereNotNull('unreserved_qty')
                                ->where('unreserved_qty', '>', 0)
                                ->orderBy('wo_id', 'asc')
                                ->lockForUpdate()
                                ->get();


                foreach ($unreservedList as $woMaterial) {
                        if ($remainingInward <= 0) break;
                        $available = (float) $woMaterial->unreserved_qty;
                        if ($available <= 0) continue;
                        $deduct = min($remainingInward, $available);
                        WoMaterialModel::where('wo_id', $woMaterial->wo_id)
                        ->where("wo_rm_id",$woMaterial->wo_rm_id)
                            ->where('rm_id', $item['rm_id'])
                            ->update([
                                'unreserved_qty' => DB::raw("GREATEST(unreserved_qty - {$deduct}, 0)"),
                                'reserved_qty'   => DB::raw("reserved_qty + {$deduct}")
                            ]);

                        $remainingInward -= $deduct;
                    }

               if ($remainingInward > 0) {
                    updateInventory($item['rm_id'], $remainingInward);
                }
            }

        $totalPOQty = Purchase_items_Model::whereHas('order', function ($query) use ($data) {
            $query->where('po_no', $data['po_no']);
        })->sum('qty');

        $totalInwardedQty = Purchase_inward_Model::where('po_no', $data['po_no'])->sum('total_inw_qty');

        $poStatus = ($totalPOQty == $totalInwardedQty) ? 1 : 2;

      $purchase_return = Purchase_return_model::where('po_no', $data['po_no'])->first();

if ($purchase_return) {
    foreach ($data['items'] as $item) {
        $inwQty = (float) ($item['inw_qty'] ?? 0);
        if ($inwQty <= 0) {
            continue;
        }

        // ✅ Update header totals once per item
        $purchase_return->total_inw_qty += $inwQty;
        $purchase_return->total_return_qty = max(
            0,
            $purchase_return->total_return_qty - $inwQty
        );
        $purchase_return->save();

        // ✅ Distribute this item's inward qty to return items (rm_id + batch)
        $remainingInward = $inwQty;

        $returnItems = Purchase_return_items_model::where('pr_id', $purchase_return->pr_id)
            ->where('rm_id', $item['rm_id'])
            // ->whereRaw('return_qty > return_inw_qty')
            ->orderBy('return_item_id', 'asc')
            ->lockForUpdate()
            ->get();
           

        foreach ($returnItems as $retItem) {
            if ($remainingInward <= 0) break;

            $available = $retItem->return_qty - $retItem->return_inw_qty;
            if ($available <= 0) continue;

            $consume = min($remainingInward, $available);

            $retItem->return_inw_qty += $consume;

            // mark completed
            if ($retItem->return_inw_qty >= $retItem->return_qty) {
                $retItem->status = 1;
            }

            $retItem->save();

            $remainingInward -= $consume;
        }
    }
}

   $totalPOQty = Purchase_items_Model::whereHas('order', function ($query) use ($data) {
            $query->where('po_no', $data['po_no']);
        })->sum('qty');

        $totalInwardedQty = Purchase_inward_Model::where('po_no', $data['po_no'])
            ->sum('total_inw_qty');

        $poStatus = ($totalPOQty == $totalInwardedQty) ? 1 : 2;

        Purchase_order_Model::where('po_id', $data['po_id'])
            ->where('po_no', $data['po_no'])
            ->update(['po_status' => $poStatus]);


        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Purchase Inward saved successfully.",
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Unexpected error occurred!',
            'error' => $e->getMessage()
        ], 500);
    }
}


// public function list()
// {
//     $purchase_inward = Purchase_inward_Model::where('status',0)
//         ->orderBy('tbl_purchase_inward.pi_id', 'desc');
//     return DataTables::of($purchase_inward)
//         ->addIndexColumn()

//         ->editColumn('date', function ($row) {
//             return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i:s');
//         })

//         ->addColumn('action', function ($purchase_inward) {
//             return '
//                 <div class="">
//                      <a href="#" class="btn btn-sm btn-info view-btn" 
//                         data-id="' . $purchase_inward->pi_id . '" 
//                         data-bs-toggle="modal" 
//                         data-bs-target="#purchaseInwardModal" 
//                         title="Show Purchase Inward">View</a>
//                     <button class="btn btn-sm btn-primary edit-btn" data-id="' . $purchase_inward->pi_id . '">Edit</button>
//                     <button 
//                         class="btn btn-sm btn-danger delete-btn" 
//                         data-id="' . $purchase_inward->pi_id . '" 
//                         data-name="' . $purchase_inward->po_no . '" 
//                         data-module="purchase_inward"
//                         data-table="poinwdata"
//                         data-bs-toggle="modal" 
//                         data-bs-target="#deleteModal"
//                     >
//                         Delete
//                     </button>
//                 </div>
//             ';
//         })

//         ->rawColumns(['action']) // Allow HTML rendering
//         ->make(true);
// }



public function list()
{
    $purchase_inward = Purchase_inward_Model::whereIn('status', [0, 1])
        // ->orderBy('tbl_purchase_inward.po_no', 'desc')
        ->orderBy('tbl_purchase_inward.created_at', 'desc')
        ->get()
        ->groupBy('po_no');

    $data = [];
    $i = 1;

    foreach ($purchase_inward as $po_no => $rows) {
        $first = $rows->first();
        $total_po_qty = $first->total_po_qty;
        $total_inw_qty  = '';
        $dates          = '';

        foreach ($rows as $row) {
            $total_inw_qty .= $row->total_inw_qty . '<br>';
            $dates         .= \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i:s') . '<br>';
        }

        $data[] = [
            'DT_RowIndex'   => $i++,
            'po_no'         => $po_no,
            'total_po_qty'  => $total_po_qty, 
            'total_inw_qty' => $total_inw_qty,
            'date'          => $dates,
            'action' => '
                <div>
                    <a href="#" class="btn btn-sm btn-info view-btn" 
                        data-id="' . $first->pi_id . '" 
                        data-bs-toggle="modal" 
                        data-bs-target="#purchaseInwardModal">View</a>
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $first->pi_id . '">Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . $first->pi_id . '" 
                        data-name="' . $first->po_no . '" 
                        data-module="purchase_inward"
                        data-table="poinwdata"
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal">Delete</button>
                </div>'
        ];
    }

    return DataTables::of($data)
        ->rawColumns(['po_no', 'total_po_qty', 'total_inw_qty', 'date', 'action'])
        ->make(true);
}



public function get_data(Request $request)
{
    $id = $request->input("id");

    $po_inw = Purchase_inward_Model::where("pi_id", $id)
                ->whereIn('status', [0, 1])
                ->first();

    $po_items = Purchase_inward_items_Model::with(['raw_material', 'unit'])
                ->where('pi_id', $id)
                ->where("status", 0)
                ->orderBy('rm_id')
                ->orderBy('batch_no')
                ->get();

    return view('company/po/po_inward_view_modal', compact('po_inw','po_items'));
}



}
