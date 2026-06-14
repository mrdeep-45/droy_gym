<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Scrap extends Controller
{
    //

      public function index()
    {
        $page_title = 'Scrap';
        $page_name = 'Scrap';
        return view('company/scrap/scrap', compact('page_title', 'page_name'));
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
            'p.remark',
            'p.repair_status',
            'p.notrepair_status'
        )
        ->whereIn('p.notrepair_status', array(2,3,4))
        ->orderBy('p.production_id', 'desc');

    return datatables()->of($data)
        ->addIndexColumn()
        ->editColumn('created_at', function($row) {
            return Carbon::parse($row->created_at)->format('d-m-Y');
        })
        ->editColumn('status', function($row) {
            $buttons = '<div>';
            $buttons .= '<span class="badge bg-danger">Not Repairable</span>&nbsp;';
            $buttons .= '<a href="'.route('newworkorder', ['wo_id' => $row->wo_id, 'production_id' => $row->production_id]).'" class="btn btn-sm btn-primary" target="_blank">+</a>';
            $buttons .= '</div>';
            return $buttons;
        })
        ->rawColumns(['status','action'])
        ->make(true);
}

}

