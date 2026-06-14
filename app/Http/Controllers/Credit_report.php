<?php

namespace App\Http\Controllers;
use App\Models\CreditModel;
use App\Models\Purchase_return_items_Model;
use App\Models\Purchase_return_Model;
use App\Models\Purchase_order_Model;
use App\Models\Purchase_items_Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
     

class Credit_report extends Controller
{
    //
    public function index()
    {
        $page_title = 'Credit Report';
        $page_name = 'Credit Report';
        
        return view('company/report/credit_report', compact('page_title', 'page_name'));
    }

public function list()
{
    $credit = CreditModel::select([
            'tbl_credit.credit_id',
            'tbl_credit.pr_id',
            'tbl_credit.credit_code',
            'tbl_credit.credit_date',
            'tbl_credit.credit_expiry',
            'tbl_credit.credit_amount',
            'tbl_credit.remark',
            'tbl_purchase_return.po_no'
        ])
        ->join('tbl_purchase_return', 'tbl_purchase_return.pr_id', '=', 'tbl_credit.pr_id')
        ->where('tbl_credit.status', '0');

    return DataTables::of($credit)
        ->addIndexColumn()
        ->editColumn('credit_date', function ($row) {
            return \Carbon\Carbon::parse($row->credit_date)->format('d-m-Y');
        })
        ->editColumn('credit_expiry', function ($row) {
            return \Carbon\Carbon::parse($row->credit_expiry)->format('d-m-Y');
        })
        ->editColumn('credit_amount', function ($row) {
            return number_format($row->credit_amount, 2);
        })
        ->addColumn('action', function ($row) {
            return '<a href="'. route('creditpdf', $row->credit_id).'" class="btn btn-sm btn-success" target="_blank">
                        <i class="fe fe-file"></i>
                    </a>';
        })
        ->rawColumns(['action'])
        ->make(true);
}

public function credit_pdf($credit_id)
{
    $credit_data = CreditModel::where('credit_id', $credit_id)
        ->where('status', 0)
        ->first();

    $query = Purchase_return_Model::where('pr_id', $credit_data->pr_id)->first();

    $query_data = Purchase_return_items_Model::select(
            'tbl_purchase_return_items.rm_id',
            'tbl_purchase_return_items.price',
            'tbl_raw_material.name',
            DB::raw("SUM(
                    CASE 
                        WHEN tbl_purchase_return_items.return_inw_qty IS NULL 
                            THEN tbl_purchase_return_items.return_qty
                        ELSE (tbl_purchase_return_items.return_qty - tbl_purchase_return_items.return_inw_qty)
                    END
            ) as total_return_qty")
        )
        ->join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_purchase_return_items.rm_id')
        ->where('tbl_purchase_return_items.pr_id', $credit_data->pr_id)
        ->where('tbl_purchase_return_items.status', 0)
        ->groupBy('tbl_purchase_return_items.rm_id', 'tbl_purchase_return_items.price', 'tbl_raw_material.name')
        ->get();

    $po = Purchase_order_Model::join('tbl_supplier', 'tbl_purchase_order.supplier_id', '=', 'tbl_supplier.supplier_id')
        ->join('mst_state', 'mst_state.state_id', '=', 'tbl_supplier.state_id')
        ->select(
            'tbl_purchase_order.*',
            'tbl_supplier.name as supplier_name',
            'tbl_supplier.email as supplier_email',
            'tbl_supplier.address as address',
            'tbl_supplier.contact_no',
            'tbl_supplier.gst_no',
            'mst_state.state_name'
        )
        ->where('tbl_purchase_order.po_no', $query->po_no)
        ->first();

    $items = Purchase_items_Model::join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_purchase_items.rm_id')
        ->leftJoin('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_purchase_items.unit_id')
        ->leftJoin('tbl_alternate_unit', 'tbl_alternate_unit.alt_unit_id', '=', 'tbl_purchase_items.unit_id')
        ->where('tbl_purchase_items.po_no', $query->po_no)
        ->select(
            'tbl_purchase_items.*',
            'tbl_raw_material.name as raw_material',
            DB::raw('COALESCE(tbl_unit.unit, tbl_alternate_unit.unit) as unit')
        )
        ->get();

    
        $subtotal = 0;
        $igst = 0;
        $cgst = 0;
        $sgst = 0;

        foreach ($query_data as $row) {
            $line_total = $row->total_return_qty * $row->price;
            $subtotal += $line_total;
            $gst_item = $items->firstWhere('rm_id', $row->rm_id);
            if ($gst_item && $gst_item->gst_id) {
                $gst_rate = $gst_item->gst_id; 
                 if ($po->cgst > 0 && $po->sgst > 0) {
                    $half_gst = $gst_rate / 2;
                    $cgst += ($line_total * $half_gst) / 100;
                    $sgst += ($line_total * $half_gst) / 100;
                }  elseif ($po->igst > 0) {
                    $igst += ($line_total * $gst_rate) / 100;
                }
            }
        }

        $total_with_gst = $subtotal + $igst + $cgst + $sgst;

    $pdf = Pdf::loadView('company/po/credit_pdf', compact(
        'credit_data',
        'query',
        'query_data',
        'po',
        'items',
        'subtotal',
        'igst',
        'cgst',
        'sgst',
        'total_with_gst'
    ));

    return $pdf->stream("Credit_$credit_id.pdf");
}

}
