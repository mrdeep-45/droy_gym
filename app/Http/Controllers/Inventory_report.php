<?php

namespace App\Http\Controllers;
use App\Models\RawmaterialModel; 
use App\Models\Stock_group_Model;                                                                                                                                    ;
use App\Models\Stock_category_Model; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
class Inventory_report extends Controller
{
    //
    public function index()
    {
        $page_title = 'Inventory Report';
        $page_name = 'Inventory Report';
        $stock_group = Stock_group_Model::active()->get();
        return view('company/report/inventory_report', compact('page_title', 'page_name','stock_group'));
    }

public function list(Request $request)
{
    $raw_material = RawmaterialModel::select([
        'tbl_raw_material.rm_id',
        'tbl_raw_material.name',
        DB::raw('(SELECT avl_qty 
                  FROM tbl_po_inventory 
                  WHERE tbl_po_inventory.rm_id = tbl_raw_material.rm_id 
                  LIMIT 1) as avl_qty'),
        DB::raw('COALESCE(SUM(tbl_wo_raw_material.reserved_qty), 0) as reserved_qty'),
        DB::raw('COALESCE(SUM(tbl_wo_raw_material.unreserved_qty), 0) as unreserved_qty'),
     DB::raw('COALESCE(SUM(
    CASE 
        WHEN tbl_wo_raw_material.qty > 0 
            THEN tbl_wo_raw_material.qty
    END
), 0) as qty_req'),
   DB::raw('GROUP_CONCAT(
    CASE 
        WHEN tbl_wo_raw_material.qty > 0 
             AND (COALESCE(tbl_wo_raw_material.qty, 0)) > 0
        THEN CONCAT(
                tbl_work_order.work_order_no, " (", 
                (COALESCE(tbl_wo_raw_material.qty, 0)), 
                ")"
             ) 
        ELSE NULL 
    END
 SEPARATOR ", ") as reserved_details'),

DB::raw('GROUP_CONCAT(
    CASE 
        WHEN tbl_wo_raw_material.qty_req != COALESCE(tbl_wo_raw_material.qty_issued, 0) 
             AND tbl_wo_raw_material.unreserved_qty > 0
        THEN CONCAT(
                tbl_work_order.work_order_no, " (", 
                tbl_wo_raw_material.unreserved_qty, 
                ")"
             ) 
        ELSE NULL
    END
 SEPARATOR ", ") as unreserved_details'),
    ])
    ->leftJoin('tbl_wo_raw_material', 'tbl_wo_raw_material.rm_id', '=', 'tbl_raw_material.rm_id')
    ->leftJoin('tbl_work_order', 'tbl_work_order.wo_id', '=', 'tbl_wo_raw_material.wo_id')
    ->where('tbl_raw_material.status', '0');
     if ($request->sg_id) {
        $raw_material->where('tbl_raw_material.sg_id', $request->sg_id);
    }
    if ($request->sc_id) {
        $raw_material->where('tbl_raw_material.sc_id', $request->sc_id);
    }
     $raw_material->groupBy('tbl_raw_material.rm_id', 'tbl_raw_material.name')
                 ->orderBy('tbl_raw_material.name', 'asc');

    return DataTables::of($raw_material)
        ->addIndexColumn()
        ->editColumn('total_qty', function ($row) {
            return $row->avl_qty + $row->reserved_qty;
        })
        ->editColumn('reserved_qty', function ($row) {
            $tooltip = $row->reserved_details ?: '';
            return $row->qty_req > 0
                ? '<span class="reserved-tooltip" title="' . e($tooltip) . '" data-placement="right">'
                  . $row->qty_req . '</span>'
                : $row->reserved_qty;
        })
        ->editColumn('unreserved_qty', function ($row) {
            $tooltip = $row->unreserved_details ?: '';
            return $row->unreserved_qty > 0
                ? '<span class="unreserved-tooltip" title="' . e($tooltip) . '" data-placement="right" style="color:red">'
                  . $row->unreserved_qty . '</span>'
                : $row->unreserved_qty;
        })
        ->editColumn('net_qty', function ($row) {
            return $row->avl_qty + $row->reserved_qty; 
        })
        ->rawColumns(['total_qty','reserved_qty','unreserved_qty','net_qty'])
        ->make(true);
}

public function download_pdf(Request $request)
{
    $raw_material = RawmaterialModel::select([
        'tbl_raw_material.rm_id',
        'tbl_raw_material.name',
        DB::raw('(SELECT avl_qty 
                  FROM tbl_po_inventory 
                  WHERE tbl_po_inventory.rm_id = tbl_raw_material.rm_id 
                  LIMIT 1) as avl_qty'),
        DB::raw('COALESCE(SUM(tbl_wo_raw_material.reserved_qty), 0) as reserved_qty'),
        DB::raw('COALESCE(SUM(tbl_wo_raw_material.unreserved_qty), 0) as unreserved_qty'),
        DB::raw('COALESCE(SUM(
            CASE WHEN tbl_wo_raw_material.qty > 0 THEN tbl_wo_raw_material.qty END
        ), 0) as qty_req'),
        DB::raw('GROUP_CONCAT(
            CASE 
                WHEN tbl_wo_raw_material.qty > 0 
                THEN CONCAT(tbl_work_order.work_order_no, " (", tbl_wo_raw_material.qty, ")") 
                ELSE NULL 
            END
        SEPARATOR ", ") as reserved_details'),
        DB::raw('GROUP_CONCAT(
            CASE 
                WHEN tbl_wo_raw_material.qty_req != COALESCE(tbl_wo_raw_material.qty_issued, 0) 
                     AND tbl_wo_raw_material.unreserved_qty > 0
                THEN CONCAT(tbl_work_order.work_order_no, " (", tbl_wo_raw_material.unreserved_qty, ")") 
                ELSE NULL 
            END
        SEPARATOR ", ") as unreserved_details'),
    ])
    ->leftJoin('tbl_wo_raw_material', 'tbl_wo_raw_material.rm_id', '=', 'tbl_raw_material.rm_id')
    ->leftJoin('tbl_work_order', 'tbl_work_order.wo_id', '=', 'tbl_wo_raw_material.wo_id')
    ->where('tbl_raw_material.status', '0');

    if ($request->sg_id) {
        $raw_material->where('tbl_raw_material.sg_id', $request->sg_id);
    }
    if ($request->sc_id) {
        $raw_material->where('tbl_raw_material.sc_id', $request->sc_id);
    }

    $data = $raw_material->groupBy('tbl_raw_material.rm_id', 'tbl_raw_material.name')
        ->orderBy('tbl_raw_material.name', 'asc')
        ->get();

    $pdf = PDF::loadView('company/report/inventory_pdf', compact('data'));

    return $pdf->download('Inventory Report.pdf');
}



}
