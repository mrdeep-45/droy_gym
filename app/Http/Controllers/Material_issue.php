<?php

namespace App\Http\Controllers;
use App\Models\WorkOrderModel;
use App\Models\MaterialissueModel;
use App\Models\MaterialissueitemsModel;
use App\Models\Staffmodel;
use App\Models\WoMaterialModel;
use App\Models\Purchase_inward_items_Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
class Material_issue extends Controller
{
    //
     public function index()
    {
        $page_title = 'Material Issue';
        $page_name = 'Material Issue';
        $woNo = WorkOrderModel::where('status',1)->get();
        $staff = Staffmodel::get();
        $miNo = MaterialissueModel::generateMiNo();
        return view('company/material_issue/material_issue', compact('page_title', 'page_name','woNo','staff','miNo'));
    }

//    public function getMaterialData($quotationId, $woId)
//     {
//         $rawMaterials = DB::table('tbl_wo_raw_material as wm')
//             ->join('tbl_raw_material as rm', 'rm.rm_id', '=', 'wm.rm_id')
//             ->leftJoin('tbl_po_inventory as inv', 'inv.rm_id', '=', 'wm.rm_id')
//             ->where('wm.wo_id', $woId)
//             ->select(
//                 'wm.rm_id as id',
//                 'rm.name',
//                 'wm.qty as total_qty',
//                 'wm.qty_req as qty_req',
//                 DB::raw('IFNULL(wm.qty_issued, 0) as qty_issued'), 
//                 DB::raw('IFNULL(inv.avl_qty, 0) as avl_qty'),
//                 DB::raw('IFNULL(wm.unreserved_qty, 0) as unreserved_qty'),
//                 DB::raw('(wm.qty - IFNULL(wm.unreserved_qty, 0)) as reserved_qty'),
//                 'wm.wo_id'
//             )
//             ->get();

//         foreach ($rawMaterials as $material) {
//             $material->batches = DB::table('tbl_purchase_inw_items')
//                 ->where('rm_id', $material->id)
//                 ->where('pending_qty', '>', 0)
//                 ->select('batch_no', 'inw_qty', 'inw_item_id', 'pending_qty')
//                 ->get();

//             $material->work_orders = DB::table('tbl_wo_raw_material')
//              ->join('tbl_work_order', 'tbl_work_order.wo_id', '=', 'tbl_wo_raw_material.wo_id')
//                 ->where('rm_id', $material->id)
              
//                 ->select(
//                     'tbl_wo_raw_material.wo_id',
//                     'tbl_wo_raw_material.qty',
//                     'tbl_work_order.work_order_no',
//                     DB::raw('(tbl_wo_raw_material.qty - IFNULL(tbl_wo_raw_material.unreserved_qty, 0)) as reserved_qty'),
//                    DB::raw('IFNULL(CASE WHEN tbl_wo_raw_material.wo_id = '.$woId.' THEN tbl_wo_raw_material.unreserved_qty ELSE 0 END, 0) as unreserved_qty'),
//                 )
//                 ->orderBy('tbl_wo_raw_material.wo_id', 'asc')
//                  ->where('tbl_wo_raw_material.reserved_qty', '>', 0)
//                 ->get();
//         }

//         return response()->json([
//             'raw_materials' => $rawMaterials
//         ]);
//     }


    public function getMaterialData($quotationId, $woId)
{
    $rawMaterials = DB::table('tbl_wo_raw_material as wm')
        ->join('tbl_raw_material as rm', 'rm.rm_id', '=', 'wm.rm_id')
        ->leftJoin('tbl_po_inventory as inv', 'inv.rm_id', '=', 'wm.rm_id')
        ->where('wm.wo_id', $woId)
        ->select(
            'wm.rm_id as id',
            'rm.name',
            DB::raw('SUM(wm.qty) as total_qty'),
            DB::raw('SUM(wm.qty_req) as qty_req'),
            DB::raw('SUM(IFNULL(wm.qty_issued, 0)) as qty_issued'),
            DB::raw('IFNULL(inv.avl_qty, 0) as avl_qty'),
            DB::raw('SUM(IFNULL(wm.unreserved_qty, 0)) as unreserved_qty'),
            DB::raw('(SUM(wm.qty) - SUM(IFNULL(wm.unreserved_qty, 0))) as reserved_qty'),
            DB::raw('MAX(wm.wo_id) as wo_id') 
        )
        ->groupBy('wm.rm_id', 'rm.name', 'inv.avl_qty')
        ->get();

    foreach ($rawMaterials as $material) {
        $material->batches = DB::table('tbl_purchase_inw_items')
            ->where('rm_id', $material->id)
            ->where('pending_qty', '>', 0)
            ->select('batch_no', 'inw_qty', 'inw_item_id', 'pending_qty')
            ->get();

        $material->work_orders = DB::table('tbl_wo_raw_material')
            ->join('tbl_work_order', 'tbl_work_order.wo_id', '=', 'tbl_wo_raw_material.wo_id')
            ->where('rm_id', $material->id)
            ->select(
                'tbl_wo_raw_material.wo_id',
                'tbl_wo_raw_material.qty',
                'tbl_work_order.work_order_no',
                DB::raw('(tbl_wo_raw_material.qty - IFNULL(tbl_wo_raw_material.unreserved_qty, 0)) as reserved_qty'),
                DB::raw('IFNULL(CASE WHEN tbl_wo_raw_material.wo_id = '.$woId.' THEN tbl_wo_raw_material.unreserved_qty ELSE 0 END, 0) as unreserved_qty')
            )
            ->orderBy('tbl_wo_raw_material.wo_id', 'asc')
            ->where(DB::raw('(tbl_wo_raw_material.qty - IFNULL(tbl_wo_raw_material.unreserved_qty, 0))'), '>', 0)
            ->get();
    }

    return response()->json([
        'raw_materials' => $rawMaterials
    ]);
}

 public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
            'wo_id'       => 'required|exists:tbl_work_order,wo_id',
            'staff_id'    => 'required|exists:mst_staff,staff_id',
            'mi_no'       => 'required|string',
            'rm_id'       => 'required|array',
            'rm_id.*'     => 'required|exists:tbl_raw_material,rm_id',
            'issue_qty_batch'     => 'required|array', 
            'issue_qty_batch.*'   => 'required|array', 
        ];

        $customAttributes = [
            'wo_id'     => 'Work Order No.',
            'staff_id'  => 'Staff',
            'mi_no'     => 'Material Issue No.',
            'rm_id'     => 'Raw Material',
            'issue_qty_batch' => 'Issue Quantity',
        ];

        $validator = Validator::make($request->all(), $rules, [], $customAttributes);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json([
                'success' => false,
                'field'   => array_key_first($errors),
                'message' => reset($errors)[0],
            ], 422);
        }

      
            $issueQtys = $request->input('issue_qty_batch', []); 
            $hasPositiveQty = false;

            foreach ($issueQtys as $rmBatches) {
                foreach ($rmBatches as $qty) {
                    if (floatval($qty) > 0) {
                        $hasPositiveQty = true;
                        break 2;
                    }
                }
            }

            if (!$hasPositiveQty) {
                return response()->json([
                    'success' => false,
                    'field'   => 'issue_qty_batch',
                    'message' => 'Please enter at least one Issue Quantity.',
                ], 422);
            }

        $validated = $validator->validated();
        $material = MaterialissueModel::create([
            'wo_id'      => $validated['wo_id'],
            'staff_id'   => $validated['staff_id'],
            'mi_no'      => $validated['mi_no'],
            'status'     => 0,
            'created_by' => getCreatedBy(),
            'created_at' => now(),
        ]);
       foreach ($validated['rm_id'] as $rmId) {
            $batchInwItemIds = $request->input("inw_item_id.$rmId", []);
            $batchIssueQtys  = $request->input("issue_qty_batch.$rmId", []);

            foreach ($batchInwItemIds as $batchIndex => $inwItemId) {
                $issueQty = floatval($batchIssueQtys[$batchIndex] ?? 0);
                if ($issueQty <= 0) continue;

                $existingPo = Purchase_inward_items_Model::where("inw_item_id", $inwItemId)
                    ->where('rm_id', $rmId)
                    ->first();

                if (!$existingPo) continue;

                MaterialissueitemsModel::create([
                    'material_id' => $material->material_id,
                    'rm_id'       => $rmId,
                    'batch_no'    => $existingPo->batch_no,
                    'qty'         => $existingPo->inw_qty,
                    'issue_qty'   => $issueQty,
                    'status'      => 0,
                    'created_by'  => getCreatedBy(),
                    'created_at'  => now(),
                ]);
                    
                // updateIssueInventory($rmId, $issueQty);

                $existingWos = WoMaterialModel::where('rm_id', $rmId)
                    ->where('wo_id', $validated['wo_id'])
                    ->orderBy('wo_rm_id', 'asc')
                    ->get();

                $remainingIssueQty = $issueQty;

                foreach ($existingWos as $existingWo) {
                    if ($remainingIssueQty <= 0) break;

                    $canIssueFromRow = min(
                        $remainingIssueQty,
                        max(0, ($existingWo->qty_req ?? 0) - ($existingWo->qty_issued ?? 0))
                    );

                    if ($canIssueFromRow > 0) {
                        $newIssued = ($existingWo->qty_issued ?? 0) + $canIssueFromRow;
                        $newRemaining = max(0, ($existingWo->qty_req ?? 0) - $newIssued);

                        $existingWo->update([
                            'qty_issued'   => $newIssued,
                            'reserved_qty' => max(0, ($existingWo->reserved_qty ?? 0) - $canIssueFromRow),
                            'qty'          => $newRemaining,
                            'updated_by'   => getCreatedBy(),
                            'updated_at'   => now(),
                        ]);

                        $remainingIssueQty -= $canIssueFromRow;
                    }
                }

                Purchase_inward_items_Model::where('inw_item_id', $inwItemId)
                    ->update([
                        'pending_qty' => max(0, $existingPo->pending_qty - $issueQty),
                        'updated_by'  => getCreatedBy(),
                        'updated_at'  => now(),
                    ]);
            }
        }

        $allIssued = WoMaterialModel::where('wo_id', $validated['wo_id'])
            ->where('qty', '>', 0)
            ->doesntExist();

        if ($allIssued) {
            WorkOrderModel::where('wo_id', $validated['wo_id'])
                ->update([
                    'status'     => 1,
                    'updated_by' => getCreatedBy(),
                    'updated_at' => now(),
                ]);
        }

       DB::commit();

        return response()->json([
            'success'     => true,
            'message'     => 'Material Issue created successfully.',
            'material_id' => $material->material_id,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create material issue.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function list()
{
    $raws = MaterialissueItemsModel::select([
            'tbl_material_issue_items.mi_items_id',
            'tbl_material_issue_items.material_id',
            'tbl_material_issue_items.rm_id',
            'tbl_material_issue_items.issue_qty',
            'tbl_material_issue_items.created_at',
            'tbl_material_issue_items.batch_no',
            'tbl_material_issue.wo_id',
            'tbl_material_issue.mi_no',
            'tbl_work_order.work_order_no',
            'tbl_raw_material.name as raw_material',
            'mst_staff.staff_name'
        ])
        ->join('tbl_material_issue', 'tbl_material_issue.material_id', '=', 'tbl_material_issue_items.material_id')
        ->join('tbl_work_order', 'tbl_work_order.wo_id', '=', 'tbl_material_issue.wo_id')
        ->join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_material_issue_items.rm_id')
        ->join('mst_staff', 'mst_staff.staff_id', '=', 'tbl_material_issue.staff_id')
        ->where('tbl_material_issue_items.status', '0')
        ->orderBy('tbl_material_issue_items.mi_items_id', 'desc')
        ->orderBy('tbl_material_issue.material_id', 'desc')
        ->get()
        ->groupBy(function ($item) {
            return $item->wo_id . '_' . $item->mi_no;
        });

    $data = [];
    $i = 1;
    foreach ($raws as $group) {
        $first = $group->first();
        $materials = '';
        $issue_qty = '';
        $date = '';
        $count = 1;
        foreach ($group as $item) {
            $materials .= $count . '. ' . e($item->raw_material) . '<br>';
            $issue_qty .= e($item->issue_qty) .' ('.$item->batch_no.')'. '<br>';
            $date = $item->created_at->format('d-m-Y');
            $count++;
        }

        $data[] = [
            'DT_RowIndex'   => $i++,
            'work_order_no' => e($first->work_order_no),
            'mi_no'         => e($first->mi_no),
            'staff_name'    => e($first->staff_name),
            'raw_material'  => $materials,
            'issue_qty'     => $issue_qty,
            'date'          => $date,
            'action'        => '<button class="btn btn-sm btn-primary edit-btn"><i class="bx bx-edit"></i></button> 
                                 <button 
                                    class="btn btn-sm btn-danger delete-btn" 
                                    data-id="' . e($first->material_id) . '" 
                                    data-name="' . e($first->work_order_no) . '" 
                                    data-module="product_map"
                                    data-table="productmapdata"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal"
                                 >
                                    <i class="bx bx-trash"></i>
                                 </button>
                                 <a href="'. route('mipdf', $first->material_id) .'" class="btn btn-sm btn-success" target="_blank"><i class="fe fe-file"></i></a>'
        ];
    }

    return DataTables::of($data)
        ->rawColumns(['raw_material', 'issue_qty', 'action'])
        ->make(true);
}



public function mi_pdf($material_id)
{
    $material = MaterialissueItemsModel::select([
            'tbl_material_issue_items.mi_items_id',
            'tbl_material_issue_items.material_id',
            'tbl_material_issue_items.rm_id',
            'tbl_material_issue_items.qty',
            'tbl_material_issue_items.issue_qty',
            'tbl_material_issue_items.batch_no',
            'tbl_material_issue_items.created_at',
            'tbl_work_order.work_order_no',
            'tbl_raw_material.name as raw_material',
            'mst_staff.staff_name',
            'tbl_material_issue.mi_no'
        ])
        ->join('tbl_material_issue', 'tbl_material_issue.material_id', '=', 'tbl_material_issue_items.material_id')
        ->join('tbl_work_order', 'tbl_work_order.wo_id', '=', 'tbl_material_issue.wo_id')
        ->join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_material_issue_items.rm_id')
        ->join('mst_staff', 'mst_staff.staff_id', '=', 'tbl_material_issue.staff_id')
        ->where('tbl_material_issue_items.status', '0')
        ->where('tbl_material_issue.material_id', $material_id)
        ->orderBy('tbl_material_issue.wo_id')
        ->orderBy('tbl_material_issue_items.created_at')
        ->get();

    $materialHeader = $material->first();

    $pdf = Pdf::loadView('company/material_issue/mi_pdf', compact('material', 'materialHeader')); 
    return $pdf->stream("Material_Issue_$material_id.pdf"); 
}



}
