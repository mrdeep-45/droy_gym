<?php

namespace App\Http\Controllers;

use App\Events\DealCreated;
use App\Models\DealModel;
use App\Models\DealStage;
use App\Models\LeadmanageModel;
use App\Models\QuotationModel;
use App\Models\CallSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CRM_dashboard extends Controller
{
    protected $actual_path;
    public function __construct()
    {
        $this->actual_path = config('app.actual_url') . '/uploads/';
    }
    public function index()
    {
        $page_title = 'Dashboard';
        $page_name = 'Dashboard';

        $total_leads = LeadmanageModel::count();
        $total_deals = DealModel::count();
        $total_quotations = QuotationModel::count();
        $today_call = CallSchedule::where('call_date',date('Y-m-d'))->count();
        return view('company/crm_dashboard', compact('page_title', 'page_name', 'total_leads', 'total_deals', 'total_quotations','today_call'));
    }
    public function get_deal_counts_stage()
    {
        $stages = DealStage::withCount('deals')->get();

        $fixedColors = ["#B14BD5", "#FFC102", "#2B3E65", "#FFA505", "#1DD871", "#E6533C"];

        // Always use fixed colors in sequence, ignoring database colors
        $colors = [];
        foreach ($stages as $index => $stage) {
            $colors[] = $fixedColors[$index % count($fixedColors)];
        }

        return response()->json([
            'series' => $stages->pluck('deals_count')->toArray(),
            'labels' => $stages->pluck('stage_name')->toArray(),
            'colors' => $colors,
            'stage_ids' => $stages->pluck('id')->toArray()
        ]);
    }
    public function list_deal_close(Request $request)
    {
        if ($request->ajax()) {
            $query = DealModel::with(['stage'])
                ->where('status', 0)
                ->select([
                    'deal_id',
                    'deal_name',
                    'deal_amount',
                    'stage_id',
                    'closing_date',
                    'contact_person_name'
                ]);
            if ($request->has('month_filter')) {
                $now = Carbon::now();
                $start = clone $now;
                $end = clone $now;

                switch ($request->month_filter) {
                    case 'last':
                        $start->subMonth()->startOfMonth();
                        $end->subMonth()->endOfMonth();
                        break;
                    case 'next':
                        $start->addMonth()->startOfMonth();
                        $end->addMonth()->endOfMonth();
                        break;
                    case 'current':
                    default:
                        $start->startOfMonth();
                        $end->endOfMonth();
                        break;
                }

                $query->whereBetween('closing_date', [
                    $start->format('Y-m-d'),
                    $end->format('Y-m-d')
                ]);
            }

            $deals = $query->get();

            return DataTables::of($deals)
                ->addIndexColumn()
                ->editColumn('deal_amount', function ($row) {
                    return number_format($row->deal_amount, 2);
                })
                ->editColumn('closing_date', function ($row) {
                    return Carbon::parse($row->closing_date)->format('d-m-Y');
                })
                ->addColumn('stage_name', function ($row) {
                    return $row->stage->stage_name ?? 'N/A';
                })
                ->rawColumns([])
                ->make(true);
        }
    }
}
