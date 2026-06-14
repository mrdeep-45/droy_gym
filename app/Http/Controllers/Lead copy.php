<?php

namespace App\Http\Controllers;

use App\Models\LeadHistorymodel;
use App\Models\LeadmanageModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class Lead extends Controller
{
    protected $actual_path;
    public function __construct()
    {
        $this->actual_path = config('app.actual_url') . '/uploads/';
    }
    public function index()
    {
        $page_title = 'Lead';
        $page_name = 'Lead';

        $source = DB::table('mst_source')->select('source_id', 'source_name')->where('status', 0)->orderBy('source_name', 'asc')->get();
        $service = DB::table('mst_service_manage')->select('service_id', 'service_name', 'service_desc')->where('status', 0)->get();
        $lead_status = DB::table('lead_status')->select('status_id', 'status_name')->where('status', 0)->orderBy('status_name', 'asc')->get();

        return view('company/lead/lead', compact('page_title', 'page_name', 'source', 'service', 'lead_status'));
    }
    // public function store(Request $request)
    // {
    //     // Validate the request
    //     $validator = Validator::make($request->all(), [
    //         'lead_name' => 'required|string|max:255',
    //         'company_name' => 'nullable|string|max:255',
    //         'contact_person_name' => 'nullable|string|max:255',
    //         'email' => 'nullable|email|max:255',
    //         'phone' => 'required|string|max:20',
    //         'alternate_phone_1' => 'nullable|string|max:20',
    //         'alternate_phone_2' => 'nullable|string|max:20',
    //         'source_id' => 'required|exists:mst_source,source_id',
    //         'country_id' => 'nullable|exists:mst_country,c_id',
    //         'state_id' => 'nullable|exists:mst_state,state_id',
    //         'city_id' => 'nullable|exists:mst_city,city_id',
    //         'service_id' => 'required|exists:mst_service_manage,service_id',
    //         'lead_date' => 'required|date',
    //         'notes' => 'nullable|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation errors',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try {
    //         // Create new lead using create() method
    //         $lead = LeadmanageModel::create([
    //             'lead_no' => LeadmanageModel::get_lead_no(),
    //             'lead_name' => $request->lead_name,
    //             'company_name' => $request->company_name,
    //             'contact_person_name' => $request->contact_person_name,
    //             'email' => $request->email,
    //             'phone' => $request->phone,
    //             'alternate_phone_1' => $request->alternate_phone_1,
    //             'alternate_phone_2' => $request->alternate_phone_2,
    //             'source' => $request->source_id,
    //             'service_id' => $request->service_id,
    //             'lead_date' => Carbon::parse($request->lead_date)->format('Y-m-d'),
    //             'notes' => $request->notes,
    //             'country_id' => $request->country_id,
    //             'state_id' => $request->state_id,
    //             'city_id' => $request->city_id,
    //             'created_by' => getCreatedBy(),
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Lead created successfully!',
    //             'data' => $lead
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create lead. Please try again.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone_1' => 'nullable|string|max:20',
            'alternate_phone_2' => 'nullable|string|max:20',
            'source_id' => 'required|exists:mst_source,source_id',
            'countries_id' => 'nullable|exists:mst_country,c_id',
            'state_id' => 'nullable|exists:mst_state,state_id',
            'city_id' => 'nullable|exists:mst_city,city_id',
            'status_id' => 'nullable|exists:lead_status,status_id',
            // 'service_id' => 'required|exists:mst_service_manage,service_id',
            'lead_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $lead = LeadmanageModel::create([
                'lead_no' => LeadmanageModel::get_lead_no(),
                'lead_name' => $request->lead_name,
                'company_name' => $request->company_name,
                'contact_person_name' => $request->contact_person_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'alternate_phone_1' => $request->alternate_phone_1,
                'alternate_phone_2' => $request->alternate_phone_2,
                'source' => $request->source_id,
                'status_id' => $request->status_id,
                'lead_date' => Carbon::parse($request->lead_date)->format('Y-m-d'),
                'notes' => $request->notes,
                'countries_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'created_by' => getCreatedBy(),
            ]);

            insertLeadHistory(
                $lead->lead_id,
                'Lead Created',
                $request->notes,
                $request->next_followup_date ?? null,
                0
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully!',
                'data' => $lead
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create lead. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function list()
    {
        $lead = LeadmanageModel::select(['*'])->orderBy('lead_id', 'desc')->get();

        $permissions = checkPermissions(get_index_route($this));
        $canUpdate = $permissions['canUpdate'] ?? false;
        $canDelete = $permissions['canDelete'] ?? false;

        $statuses = config('lead_status.statuses');

        foreach ($lead as $l) {
            $l->source_status = get_source_name($l->source) . ' <br> ' . get_status_name($l->status_id);
            $l->contact_details = $l->contact_person_name . '<br>' . $l->phone . '<br>' . $l->email;
            $l->lead_name = $l->lead_name . '<br> <small class="text-muted">' . $l->lead_no . '</small>' . '<br> <small class="text-muted">' . get_createdby_name($l->created_by) . '</small>';

            if (isset($statuses[$l->status])) {
                $statusData = $statuses[$l->status];
                $l->status = '<span class="badge rounded-pill ' . $statusData['classes'] . ' status-badge" data-id="' . $l->lead_id . '" data-status-value="' . $statusData['frontend_value'] . '" style="cursor:pointer">' . $statusData['name'] . '</span>';
            } else {
                $l->status = '-';
            }
        }

        return DataTables::of($lead)
            ->addIndexColumn()
            ->addColumn('action', function ($row) use ($canUpdate, $canDelete) {
                $btn = '<div class="btn-group">';
                if ($canUpdate) {
                    $btn .= '<a href="javascript:void(0)" class="btn btn-sm btn-primary edit-staff" data-id="' . $row->staff_id . '">Edit</a>';
                }
                if ($canDelete) {
                    $btn .= '<a href="javascript:void(0)" class="btn btn-sm btn-danger delete-staff" data-id="' . $row->staff_id . '">Delete</a>';
                }
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['action', 'source_status', 'contact_details', 'lead_name', 'status'])
            ->make(true);
    }

    public function get_lead_details(Request $request)
    {
        $leadId = $request->lead_id;

        $lead = LeadmanageModel::where('lead_id', $leadId)->first();

        $lead_history = LeadHistorymodel::where('lead_id', $leadId)->orderBy('created_at', 'desc')->get();


        // /        dd($lead);
        if (!$lead) {
            return response()->json([
                'status' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        $statuses = config('lead_status.statuses');

        $currentStatus = $statuses[$lead->status] ?? null;

        return response()->json([
            'status' => true,
            'data' => [
                'lead_id' => $lead->lead_id ?? '-',
                'lead_no' => $lead->lead_no ?? '-',
                'lead_name' => $lead->lead_name ?? '-',
                'company_name' => $lead->company_name ?? '-',
                'contact_person_name' => $lead->contact_person_name ?? '-',
                'email' => $lead->email ?? '-',
                'phone' => $lead->phone ?? '-',
                'source' => $lead->source ?? '-',
                'source_name' => get_source_name($lead->source ?? 0),
                'service_id' => $lead->service_id ?? '-',
                'service_name' => get_service_name($lead->service_id ?? 0),
                'lead_date' => Carbon::parse($lead->lead_date)->format('d-M-Y') ?? '-',
                'notes' => $lead->notes ?? '-',
                'rollback_count' => $lead->rollback_count ?? '-',
                'close_report_remark' => $lead->close_report_remark ?? '-',
                'status' => $lead->status ?? '-',
                'lead_status' => $lead->lead_status ?? '-',
                'countries_id' => $lead->countries_id ?? '-',
                'country_name' => get_country_name($lead->countries_id ?? ''),
                'state_id' => $lead->state_id ?? '-',
                'state_name' => get_state_name($lead->state_id ?? ''),
                'city_id' => $lead->city_id ?? '-',
                'city_name' => get_city_name($lead->city_id ?? ''),
                'created_by' => $lead->created_by ?? '-',
                'updated_by' => $lead->updated_by ?? '-',
                'created_at' => $lead->created_at ?? '-',
                'updated_at' => $lead->updated_at ?? '-',
                'current_status_name' => $currentStatus['name'] ?? '-',
                'status_classes' => $currentStatus['classes'] ?? 'bg-secondary text-white',
                'all_statuses' => $statuses ?? [],
                'next_follow_up_date' => $lead->next_follow_up_date ? Carbon::parse($lead->next_follow_up_date)->format('d-M-Y') : '-',
                'note_history' => $lead_history->map(function ($item) {
                    return [
                        'notes' => $item->notes ?? '-',
                        'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('d-M-Y h:i A') : '-',
                        'status' => $item->status ?? '-',
                    ];
                }),
            ]
        ]);
    }

    public function update_lead_status(Request $request)
    {
        $validatedData = $request->validate([
            'lead_id' => 'required|exists:lead_manage,lead_id',
            'status' => 'required|string',
            'notes' => 'required|string|',
            'next_follow_up_date' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $lead = LeadmanageModel::findOrFail($validatedData['lead_id']);

            $lead->status = $validatedData['status'];
            $lead->notes = $validatedData['notes'];

            if ($validatedData['next_follow_up_date']) {
                $lead->next_follow_up_date = Carbon::parse($validatedData['next_follow_up_date'])->format('Y-m-d');
            }

            $lead->save();

            insertLeadHistory(
                $lead->lead_id,
                'Status Updated',
                $validatedData['notes'],
                Carbon::parse($validatedData['next_follow_up_date'])->format('Y-m-d'),
                $validatedData['status'],
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function sendStaticInvoiceWhatsApp(Request $request)
    {
        $invoice = 1;

        $pdfUrl = asset('assets/uploads/company_doc/invoice.pdf');

        $phone = "919099714378";

        $response = Http::post('http://192.168.0.121:3000/send-invoice', [
            'phone' => $phone,
            'pdfUrl' => $pdfUrl
        ]);

        if ($response->successful()) {
            return back()->with('success', 'Invoice sent via WhatsApp');
        } else {
            return back()->with('error', 'Failed to send invoice');
        }
    }
}
