<?php

namespace App\Http\Controllers;

use App\Events\DealCreated;
use App\Models\CustomerModel;
use App\Models\DealModel;
use App\Models\DealStage;
use App\Models\LeadmanageModel;
use App\Models\CallPurposeModel;
use App\Models\DealCallScheduleModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pusher\Pusher;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Session;

class Deal extends Controller
{
    protected $actual_path;
    public function __construct()
    {
        $this->actual_path = config('app.actual_url') . '/uploads/';
    }
    public function index()
    {
        $page_title = 'Deal';
        $page_name = 'Deal';

        $source = DB::table('mst_source')->select('source_id', 'source_name')->where('status', 0)->orderBy('source_name', 'asc')->get();
        $service = DB::table('mst_service_manage')->select('service_id', 'service_name', 'service_desc')->where('status', 0)->get();
        $lead = LeadmanageModel::where('status', 0)->orderBy('lead_name', 'asc')->get();
        $lead_status = DB::table('lead_status')->select('status_id', 'status_name')->where('status', 0)->orderBy('status_name', 'asc')->get();
        $stage = DB::table('deal_stage')->select('stage_name', 'id')->where('status', 0)->orderBy('stage_name', 'asc')->get();
        $call_purpose = CallPurposeModel::get();

        return view('company/lead/deal', compact('page_title', 'page_name', 'source', 'service', 'lead_status', 'lead', 'stage', 'call_purpose'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deal_name' => 'required',
            'deal_amount' => 'required|numeric',
            'lead_id' => 'required|exists:lead_manage,lead_id',
            'closing_date' => 'required|date',
            'stage_id' => 'required|exists:deal_stage,id',
            'probability' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Update the lead status to 1 (converted to deal)
            $lead = LeadmanageModel::where('lead_id', $request->lead_id)->first();
            if ($lead) {
                $lead->update(['status' => 1]);
            }

            $deal = DealModel::create([
                'lead_id' => $request->lead_id,
                'deal_name' => $request->deal_name,
                'type' => $request->deal_type,
                'source_id' => $request->lead_source,
                'contact_person_name' => $request->contact_person_name,
                'deal_amount' => $request->deal_amount,
                'closing_date' => Carbon::parse($request->closing_date)->format('Y-m-d'),
                'stage_id' => $request->stage_id,
                'profit' => $request->probability,
                'expected_revenue' => $request->expected_revenue,
                'description' => $request->description,
                'status' => 0,
                'created_at' => Carbon::now(),
                'created_by' => getCreatedBy(),
            ]);

            // Insert deal history
            insert_deal_history(
                $deal->deal_id,
                'created',
                'Deal created .' . $deal->deal_name
            );

            // Push notification
            push_to_channel('deals', 'deal.created', ['deal' => $deal]);

            // Commit the transaction
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Deal created successfully and lead converted!',
                'data' => $deal
            ], 201);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create deal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function get_kanban_data()
    {
        $stages = DealStage::with([
            'deals' => function ($query) {
                $query->orderBy('closing_date', 'asc');
            }
        ])->orderBy('id')->get();

        $stages->map(function ($stage) {
            $stage->deals->map(function ($deal) {
                $lead_details = get_lead_name($deal->lead_id);
                // $product_id = LeadmanageModel::where('lead_id', $deal->lead_id)->value('product_id');
                // $product_details = get_product_details($product_id);
                $deal->created_by_name = get_createdby_name($deal->created_by);
                $deal->closing_date = Carbon::parse($deal->closing_date)->format('d/M/Y');
                $deal->encrypted_id = encrypt($deal->deal_id);
                $deal->convert_client = $deal->convert_client;
                $deal->lead_name = $lead_details['lead_name'];
                // $deal->product_name = $product_details['name']; 
                return $deal;
            });
            return $stage;
        });

        return response()->json([
            'success' => true,
            'data' => $stages
        ]);
    }

    public function get_list_data()
    {
        $stages = DealStage::with([
            'deals' => function ($query) {
                $query->orderBy('closing_date', 'asc');
            }
        ])->orderBy('id')->get();

        $deals = $stages->flatMap(function ($stage) {
            return $stage->deals->map(function ($deal) {
                $leadDetails = get_lead_name($deal->lead_id);
                return [
                    'deal_id' => $deal->deal_id,
                    'deal_name' => $deal->deal_name,
                    'deal_amount' => $deal->deal_amount,
                    'closing_date' => $deal->closing_date ? Carbon::parse($deal->closing_date)->format('d/M/Y') : null,
                    'stage' => $deal->stage,
                    'created_by' => get_createdby_name($deal->created_by),
                    'contact_person_name' => $deal->contact_person_name,
                    'lead_name' => $leadDetails['lead_name'],
                    'lead_no' => $leadDetails['lead_no'],
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $deals
        ]);
    }

    public function update_deal_stage(Request $request, $dealId)
    {
        $request->validate([
            'stage_id' => 'required|exists:deal_stage,id'
        ]);

        $deal = DealModel::with('stage')->findOrFail($dealId);
        $oldStage = $deal->stage ? $deal->stage->stage_name : 'N/A';

        $newStage = DealStage::findOrFail($request->stage_id);

        $deal->stage_id = $request->stage_id;
        $deal->updated_by = getCreatedBy();
        $deal->save();

        // Log history
        insert_deal_history(
            $deal->deal_id,
            'stage_updated',
            'Stage changed from "' . $oldStage . '" to "' . $newStage->stage_name . '"'
        );

        push_to_channel('deal-stages', 'stage.updated', [
            'deal' => $deal,
            'new_stage' => $newStage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deal stage updated successfully'
        ]);
    }
    public function get_deal_details($id)
    {
        $deal_id = decrypt($id);

        $lead_id = DealModel::where('deal_id', $deal_id)->value('lead_id');
        $stage_id = DealModel::where('deal_id', $deal_id)->value('stage_id');

        $page_title = 'Deal';
        $page_name = 'Deal';

        $source = DB::table('mst_source')->select('source_id', 'source_name')->where('status', 0)->orderBy('source_name', 'asc')->get();
        $service = DB::table('mst_service_manage')->select('service_id', 'service_name', 'service_desc')->where('status', 0)->get();
        $lead = LeadmanageModel::where('status', 0)->orderBy('lead_name', 'asc')->get();
        $lead_status = DB::table('lead_status')->select('status_id', 'status_name')->where('status', 0)->orderBy('status_name', 'asc')->get();
        $stage = DB::table('deal_stage')->select('stage_name', 'id')->where('status', 0)->orderBy('stage_name', 'asc')->get();

        $call_purpose = CallPurposeModel::get();
        $outgoing_res = DB::table('tbl_outgoing_res')->get();

        return view('company/lead/deal_details', compact('page_title', 'deal_id', 'stage_id', 'id', 'lead_id', 'page_name', 'source', 'service', 'lead_status', 'lead', 'stage', 'outgoing_res', 'call_purpose'));
    }



    //closedwondealbymk
    public function closedWon(Request $request)
    {

        $request->validate([
            'deal_id' => 'required',
            'lead_id' => 'required',
            'stage_id' => 'required|exists:deal_stage,id',
            'company_name' => 'required|string',
            'contact_person_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
        ]);

        $closingdate = Carbon::createFromFormat('d/m/Y', $request->closing_date)
            ->format('Y-m-d');

        $deal = DealModel::with('stage')->findOrFail($request->deal_id);
        $oldStage = $deal->stage ? $deal->stage->stage_name : 'N/A';

        $newStage = DealStage::findOrFail($request->stage_id);

        // Update deal stage
        $deal->stage_id = $request->stage_id;
        $deal->update_deal_amount = $request->amount;
        $deal->closing_date = $closingdate;
        $deal->convert_client = 1;
        $deal->updated_by = getCreatedBy();
        $deal->save();


        DB::table('tbl_deal_close')->insert([
            'deal_id' => $request->deal_id,
            'lead_id' => $request->lead_id,
            'updated_deal_amount' => $request->amount,
            'close_date' => $closingdate,
            'status' => 0,
            'closed_type' => "won",
            'created_by' => getCreatedBy(),
            'created_at' => now(),
            'updated_by' => getCreatedBy(),
            'updated_at' => now(),
        ]);



        insert_deal_history(
            $deal->deal_id,
            'stage_updated',
            'Stage changed from "' . $oldStage . '" to "' . $newStage->stage_name . '" For Closed Won'
        );


        $customer = new CustomerModel();
        $customer->lead_id = $request->lead_id;
        $customer->deal_id = $request->deal_id;
        $customer->company_name = $request->company_name;
        $customer->contact_person = $request->contact_person_name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->created_by = getCreatedBy();
        $customer->created_at = now();
        $customer->updated_by = getCreatedBy();
        $customer->updated_at = now();
        $customer->save();

        insert_deal_history(
            $deal->deal_id,
            'converted_to_customer',
            'Lead converted to customer: ' . $request->company_name
        );

        // Push to channel
        push_to_channel('deal-stages', 'stage.updated', [
            'deal' => $deal,
            'new_stage' => $newStage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deal moved to Closed Won successfully'
        ]);
    }

    public function closedLost(Request $request)
    {
        $request->validate([
            'deal_id' => 'required',
            'lead_id' => 'required',
            'stage_id' => 'required|exists:deal_stage,id',
        ]);

        $closingdate = Carbon::createFromFormat('d/m/Y', $request->closing_date)
            ->format('Y-m-d');

        $deal = DealModel::with('stage')->findOrFail($request->deal_id);
        $oldStage = $deal->stage ? $deal->stage->stage_name : 'N/A';

        $newStage = DealStage::findOrFail($request->stage_id);

        // Update deal stage
        $deal->stage_id = $request->stage_id;
        $deal->update_deal_amount = $request->amount;
        $deal->closing_date = $closingdate;
        $deal->updated_by = getCreatedBy();
        $deal->save();


        DB::table('tbl_deal_close')->insert([
            'deal_id' => $request->deal_id,
            'lead_id' => $request->lead_id,
            'updated_deal_amount' => $request->amount,
            'close_date' => $closingdate,
            'status' => 0,
            'closed_type' => "lost",
            'created_by' => getCreatedBy(),
            'created_at' => now(),
            'updated_by' => getCreatedBy(),
            'updated_at' => now(),
        ]);


        insert_deal_history(
            $deal->deal_id,
            'stage_updated',
            'Stage changed from "' . $oldStage . '" to "' . $newStage->stage_name . '" For Closed Won'
        );

        // Push to channel
        push_to_channel('deal-stages', 'stage.updated', [
            'deal' => $deal,
            'new_stage' => $newStage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deal moved to Closed Won successfully'
        ]);
    }

    // public function updateSourceStatus(Request $request)
    // {
    //     $validated = $request->validate([
    //         'lead_id' => 'required',
    //         'deal_source_id' => 'required|exists:mst_source,source_id',
    //         'deal_status_select' => 'required|exists:lead_status,status_id'
    //     ]);

    //     $leadId = decrypt($request->lead_id);
    //     $lead = LeadmanageModel::findOrFail($leadId);

    //     $isSourceChanged = ($lead->deal_source != $request->deal_source_id);
    //     $isStatusChanged = ($lead->deal_status_id != $request->deal_status_select);

    //     if (!$isSourceChanged && !$isStatusChanged) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No changes detected.'
    //         ]);
    //     }

    //     if ($isSourceChanged) {
    //         $oldSourceName = DB::table('mst_source')->where('source_id', $lead->deal_source)->value('source_name') ?? 'N/A';
    //         $newSourceName = DB::table('mst_source')->where('source_id', $request->deal_source_id)->value('source_name') ?? 'N/A';
    //         $lead->deal_source = $request->deal_source_id;
    //     }

    //     if ($isStatusChanged) {
    //         $oldStatusName = DB::table('lead_status')->where('status_id', $lead->deal_status_id)->value('status_name') ?? 'N/A';
    //         $newStatusName = DB::table('lead_status')->where('status_id', $request->deal_status_select)->value('status_name') ?? 'N/A';
    //         $lead->deal_status_id = $request->deal_status_select;
    //     }

    //     $lead->save();

    //     if ($isSourceChanged) {
    //         insertLeadHistory(
    //             $leadId,
    //             'Deal Source Updated',
    //             "Deal Source changed from {$oldSourceName} to {$newSourceName}",
    //             'lead_manage',
    //             getCreatedBy()
    //         );
    //     }

    //     if ($isStatusChanged) {
    //         insertLeadHistory(
    //             $leadId,
    //             'Deal Status Updated',
    //             "Deal Status changed from {$oldStatusName} to {$newStatusName}",
    //             'lead_manage',
    //             getCreatedBy()
    //         );
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Source & Status updated successfully!'
    //     ]);
    // }



    public function calldealschedule(Request $request)
    {

        try {
            $validated = $request->validate([
                'call_for' => 'required|string',
                'lead_name' => 'required|string',
                'related_to' => 'required|string',
                'call_type' => 'required|string',
                'call_type_select' => 'required|string',
                'call_status' => 'required|string',
                'call_date' => 'required|date',
                'call_time' => 'required',
                'subject' => 'required|string',
                'purpose' => 'nullable|string',
                'agenda' => 'nullable|string',

                'call_outcome_result' => 'nullable',
                'outgoing_description' => 'nullable',
            ]);





            $orId = $validated['call_outcome_result'] ?? null;

            $orName = null;
            if ($orId) {
                $orName = DB::table('tbl_outgoing_res')
                    ->where('or_id', $orId)
                    ->value('or_name');
            }




            $lead_id = decrypt($request->lead_id);
            $createdBy = getCreatedBy();

            $user_name = get_createdby_name($createdBy);

            // Create or Update logic
            if ($request->filled('dcs_id')) {
                $schedule = DealCallScheduleModel::find($request->dcs_id);

                if (!$schedule) {
                    return response()->json(['message' => 'Call schedule not found for update.'], 404);
                }

                $schedule->updated_at = now();
                $action = 'updated';
                $historyTitle = 'Call Schedule Updated';
            } else {
                $schedule = new DealCallScheduleModel();
                $schedule->lead_id = $lead_id;
                $schedule->status = '0';
                $schedule->created_by = $createdBy;
                $action = 'created';
                $historyTitle = 'Call Schedule Created';
            }

            // Set common fields
            $schedule->call_for = $validated['call_for'];
            $schedule->lead_name = $validated['lead_name'];
            $schedule->related_to = $validated['related_to'];
            $schedule->call_type = $validated['call_type_select']; // for table
            $schedule->call_status = $validated['call_status'];
            $schedule->call_date = $validated['call_date'];
            $schedule->call_time = $validated['call_time'];
            $schedule->subject = $validated['subject'];
            $schedule->call_result = $orName ?? '';
            $schedule->call_purpose = $validated['purpose'] ?? null;
            $schedule->call_agenda = $validated['agenda'] ?? null;

            // Optional: set is_log_call if applicable
            if ($request->call_type === 'deal_log_call') {
                $schedule->is_log_call = 1;
            }


            $schedule->save();

            // Insert or Update into tbl_call_log if log_call
            if ($request->call_type === 'deal_log_call') {
                $existingLog = DB::table('tbl_deal_log_call')
                    ->where('dcs_id', $schedule->dcs_id)
                    ->where('lead_id', $lead_id)
                    ->first();

                if ($existingLog) {
                    // Update existing log
                    DB::table('tbl_deal_log_call')
                        ->where('dcs_id', $schedule->dcs_id)
                        ->where('lead_id', $lead_id)
                        ->update([
                            'call_outcome_result' => $request->call_outcome_result ?? null,
                            'call_description' => $validated['outgoing_description'] ?? null,
                            'updated_at' => now()
                        ]);
                } else {
                    // Insert new log
                    DB::table('tbl_deal_log_call')->insert([
                        'dcs_id' => $schedule->dcs_id,
                        'lead_id' => $lead_id,
                        'call_outcome_result' => $request->call_outcome_result ?? null,
                        'call_description' => $validated['outgoing_description'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }


            // Lead details
            $lead = LeadmanageModel::find($lead_id);
            $lead_name = $lead->lead_name ?? 'Unknown Lead';

            $historyTitle = '';
            if ($request->call_type === 'log_call') {
                $historyTitle = 'Log Call';
            } elseif ($request->call_type === 'call_schedule') {
                $historyTitle = 'Schedule Call';
            } else {
                $historyTitle = 'Call Activity'; // fallback
            }
            // Insert history
            insertLeadHistory(
                $lead_id,
                $historyTitle,
                "{$historyTitle} {$action} by {$user_name} for lead {$lead_name}",
                'tbl_deal_call_schedule',
                $createdBy
            );

            if ($request->call_type === 'deal_call_schedule') {
                // Trigger Pusher event only for call_schedule
                $pusher = new Pusher(
                    '713b914b10219f63d205',
                    '5fdfb3495e412d16d848',
                    '2029158',
                    [
                        'cluster' => 'ap2',
                        'useTLS' => true
                    ]
                );



                $pusher->trigger('deal-call.' . $createdBy, 'call.scheduled', [
                    'dcs_id' => $schedule->dcs_id,
                    'lead_name' => $schedule->lead_name,
                    'email' => $lead->email ?? '',
                    'phone' => $lead->phone ?? '',
                    'company' => $lead->company_name ?? '',
                    'notes' => $lead->notes ?? '',
                    'lead_owner' => $lead->contact_person_name ?? '',
                    'lead_status' => get_status_name($lead->status_id) ?? '',
                    'trigger_at' => $schedule->call_date . ' ' . $schedule->call_time,
                    'message' => "It's time to call {$schedule->lead_name}!",
                    'target_type' => Session::get('login_type'), // so JS can match it
                    'user' => $user_name,
                    'created_by' => $createdBy
                ]);
            }


            return response()->json([
                'message' => "Call schedule {$action} and notification set.",
                'data' => $schedule
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(['message' => 'Invalid lead ID.'], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong.', 'error' => $e->getMessage()], 500);
        }
    }



    public function dealcallfetch(Request $request)
    {
        if ($request->ajax()) {
            $leadId = decrypt(value: $request->lead_id);
            $lead_id = $request->lead_id;

            $data = DealCallScheduleModel::select('*')
                ->orderBy('dcs_id', 'desc')
                ->where('lead_id', $leadId);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('callstarttime', function ($row) {
                    $date = Carbon::parse($row->call_date)->format('d/m/Y');
                    $time = Carbon::parse($row->call_time)->format('h:i A');
                    return $date . '<br>' . $time;
                })
                ->addColumn('created_by', function ($row) {
                    return get_createdby_name($row->created_by);
                })
                ->addColumn('call_outcome', function ($row) {
                    if ($row->call_result || $row->call_result_desc) {
                        return trim("{$row->call_result} <br> {$row->call_result_desc}", ' -');
                    }
                    return '';
                })
                ->addColumn('type', function ($row) {
                    if ($row->is_log_call == 1) {
                        return '<span style="color: rgb(var(--success-rgb)); border: 1px solid rgb(var(--success-rgb)); padding: 2px 6px; border-radius: 4px;">Log Call</span>';
                    } else {
                        return '<span style="color: rgb(var(--primary-rgb)); border: 1px solid rgb(var(--primary-rgb)); padding: 2px 6px; border-radius: 4px;">Schedule Call</span>';
                    }
                })
                ->addColumn('actions', function ($row) use ($lead_id) {
                    $date = Carbon::parse($row->call_date)->format('d/m/Y');
                    $time = Carbon::parse($row->call_time)->format('h:i A');

                    $deleteBtn = '
                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row->dcs_id . '">
                        <i class="bx bx-trash"></i>
                    </button>';

                    if ($row->completion_status == 1) {
                        $completedIcon = '
                        <i class="bx bx-check-circle text-success" title="Marked as Completed" style="font-size: 1.5rem;"></i>';
                        return '<div class="d-flex align-items-center gap-2">' . $completedIcon . '</div>';
                    }

                    if ($row->completion_status == 2) {
                        $rescheduleIcon = '
                        <i class="bx bx-calendar-check text-warning" title="Rescheduled Call on ' . $date . '' . $time . '" style="font-size: 1.5rem;"></i>';
                        $dropdown = '
                        <div class="dropdown d-inline">
                            <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item mark-completed" data-id="' . $row->dcs_id . '" href="#">Mark as Completed</a></li>
                                <li><a class="dropdown-item reschedule-call" data-id="' . $row->dcs_id . '" href="#">Reschedule Call</a></li>
                                <li><a class="dropdown-item cancel-call d-none" data-lead="' . $lead_id . '" data-id="' . $row->dcs_id . '" href="#">Cancel Call</a></li>
                            </ul>
                        </div>';
                        return '<div class="d-flex align-items-center gap-2">' . $rescheduleIcon . $dropdown . '</div>';
                    }

                    if ($row->completion_status == 3) {
                        $cancelIcon = '
                        <i class="bx bx-x-circle text-danger" title="Call Cancelled" style="font-size: 1.5rem;"></i>';
                        return '<div class="d-flex align-items-center gap-2">' . $cancelIcon . '</div>';
                    }


                    if ($row->is_log_call == 1) {

                        $editBtn = '
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $row->dcs_id . '">
                        <i class="bx bx-edit"></i>
                    </button>';

                        $deleteBtn = '
                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row->dcs_id . '">
                        <i class="bx bx-trash"></i>
                    </button>';


                        return '<div class="d-flex align-items-center gap-2">' . $editBtn . $deleteBtn . '</div>';
                    }

                    $editBtn = '
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $row->dcs_id . '">
                        <i class="bx bx-edit"></i>
                    </button>';

                    $dropdown = '
                    <div class="dropdown d-inline">
                        <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item mark-completed" data-id="' . $row->dcs_id . '" href="#">Mark as Completed</a></li>
                            <li><a class="dropdown-item reschedule-call" data-id="' . $row->dcs_id . '" href="#">Reschedule Call</a></li>
                            <li><a class="dropdown-item cancel-call d-none" data-lead="' . $lead_id . '" data-id="' . $row->dcs_id . '" href="#">Cancel Call</a></li>
                        </ul>
                    </div>';

                    return '<div class="d-flex align-items-center gap-2">' . $editBtn . $dropdown . $deleteBtn . '</div>';
                })
                ->rawColumns(['type', 'actions', 'call_outcome','callstarttime'])
                ->make(true);
        }
    }


    public function fetchdealcalldata(Request $request)
    {
        $dcs_id = $request->id;

        $call = DealCallScheduleModel::where('dcs_id', $dcs_id)->first();

        if (!$call) {
            return response()->json([
                'success' => false,
                'message' => 'Call schedule not found.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $call
        ]);
    }

    public function dealfetchCallLog(Request $request)
    {
        $dcs_id = $request->dcs_id;

        $log = DB::table('tbl_deal_log_call')
            ->where('dcs_id', $dcs_id)
            ->first();



        if ($log) {
            return response()->json(['success' => true, 'data' => $log]);
        }

        return response()->json(['success' => false, 'message' => 'Call log not found.']);
    }



    public function dealdelete(Request $request)
    {
        $id = $request->id;

        $schedule = DealCallScheduleModel::where('dcs_id', $id)->first();

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Call schedule not found.',
            ]);
        }

        // Optional lead history insert if lead_id is provided
        if ($request->filled('lead_id')) {
            $lead_id = decrypt($request->lead_id);
            $lead = LeadmanageModel::find($lead_id);
            $lead_name = $lead->lead_name ?? 'Unknown Lead';

            $createdBy = getCreatedBy();
            $user_name = get_createdby_name($createdBy);

            insertLeadHistory(
                $lead_id,
                'Call Schedule Deleted',
                'Call schedule deleted by ' . $user_name . ' for lead ' . $lead_name,
                'tbl_call_schedule',
                $createdBy
            );
        }

        // Delete related logs
        DB::table('tbl_deal_log_call')
            ->where('dcs_id', $id)
            ->delete();

        // Delete the schedule
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Call schedule deleted successfully.',
        ]);
    }



    public function dealsave_call_outcome(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|string',
            'call_result' => 'required|string',
            'call_result_desc' => 'nullable|string',
            'dcs_id' => 'required|string',
        ]);



        try {
            $lead_id = decrypt($request->lead_id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid lead ID.',
            ], 400);
        }

        $dcs_id = $request->dcs_id;

        $lead = DealCallScheduleModel::where('lead_id', $lead_id)
            ->where('dcs_id', $dcs_id)
            ->where('completion_status', '!=', 1)
            ->first();



        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found.',
            ], 404);
        }

        $lead->update([
            'call_result' => $request->call_result,
            'call_result_desc' => $request->call_result_desc,
            'completion_status' => '1',
            'updated_by' => getCreatedBy(),
        ]);
        insertLeadHistory(
            $lead_id,
            'Marked as Completed',
            'Lead marked as completed with Call Result ' . $request->call_result . ' and description ' . $request->call_result_desc,
            'tbl_call_schedule',
            getCreatedBy()
        );

        return response()->json([
            'success' => true,
            'message' => 'Call outcome updated successfully.',
        ]);
    }



    public function dealrescheduleCall(Request $request)
    {
        $request->validate([
            'lead_id' => 'required',
            'call_date' => 'required|date',
            'call_time' => 'required',
            'dcs_id' => 'required',
            'reschedule_notes' => 'nullable|string',
        ]);


        try {
            $lead_id = decrypt($request->lead_id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Lead ID.'
            ]);
        }

        $call = DealCallScheduleModel::where('lead_id', $lead_id)
            ->where('dcs_id', $request->dcs_id)
            ->first();

        if (!$call) {
            return response()->json([
                'success' => false,
                'message' => 'Rescheduling not allowed for this call.'
            ]);
        }

        // Update call schedule
        $call->call_date = $request->call_date;
        $call->call_time = $request->call_time;
        $call->reschedule_notes = $request->reschedule_notes;
        $call->completion_status = 2; // Marked as rescheduled
        $call->save();

        // Fetch lead details
        $lead = LeadmanageModel::find($lead_id);
        $deal_id = DealModel::where('lead_id', $lead_id)->value('deal_id');





        $createdBy = getCreatedBy();

        $user_name = get_createdby_name($createdBy);

        // Trigger Pusher event
        $pusher = new Pusher(
            '713b914b10219f63d205',
            '5fdfb3495e412d16d848',
            '2029158',
            [
                'cluster' => 'ap2',
                'useTLS' => true
            ]
        );

        $lead_name = $lead->lead_name;





        insert_deal_history(
            $deal_id,
            "Deal Call Re-Scheduled",
            "Call Re-Scheduled  by {$user_name} for deal {$lead_name}"

        );


        $pusher->trigger('deal-call.' . $createdBy, 'call.scheduled', [
            'dcs_id' => $call->dcs_id,
            'lead_name' => $lead->lead_name ?? '',
            'email' => $lead->email ?? '',
            'phone' => $lead->phone ?? '',
            'company' => $lead->company_name ?? '',
            'notes' => $lead->notes ?? '',
            'lead_owner' => $lead->contact_person_name ?? '',
            'lead_status' => get_status_name($lead->status_id) ?? '',
            'trigger_at' => $call->call_date . ' ' . $call->call_time,
            'message' => "It's time to call {$lead->lead_name}!",
            'created_by' => $createdBy
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call rescheduled successfully.'
        ]);
    }


    public function getDealLeadinfo(Request $request)
    {
        // Correct way to decrypt the lead_id from the request
        $leadId = decrypt($request->lead_id);


        // Fetch the lead record
        $lead = LeadManageModel::where('lead_id', $leadId)->first();

        if ($lead) {
            return response()->json([
                'status' => 'success',
                'lead_name' => $lead->lead_name,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead not found',
            ]);
        }
    }



    public function dealcancelCall(Request $request)
    {
        $request->validate([
            'dcs_id' => 'required|integer',
            'lead_id' => 'required',
            'cancel_note' => 'nullable|string|max:500',
        ]);



        try {
            $lead_id = decrypt($request->lead_id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid lead ID.',
            ]);
        }

        $lead = LeadmanageModel::find($lead_id);
        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found.',
            ]);
        }

        $lead_name = $lead->lead_name ?? 'Unknown Lead';

        // Get user info once
        $createdBy = getCreatedBy();
        $user_name = get_createdby_name($createdBy);

        // Log to lead history
        insertLeadHistory(
            $lead_id,
            'Call Cancelled',
            'Lead for ' . $lead_name . ' was cancelled by ' . $user_name,
            'tbl_call_schedule',
            $createdBy
        );

        // Update the call schedule
        $call = DealCallScheduleModel::where('dcs_id', $request->dcs_id)
            ->where('lead_id', $lead_id)
            ->first();

        if (!$call) {
            return response()->json([
                'success' => false,
                'message' => 'Call not eligible for cancellation or not found.',
            ]);
        }

        $call->completion_status = 3; // Cancelled
        $call->cancel_note = $request->cancel_note;
        $call->save();

        return response()->json([
            'success' => true,
            'message' => 'Call successfully cancelled.',
        ]);
    }


    public function deal_store_note(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $note_id = DB::table('deal_notes')->insertGetId([
            'lead_id' => decrypt($request->lead_id),
            'notes' => $request->notes,
            'created_by' => getCreatedBy(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        insertLeadHistory(
            decrypt($request->lead_id),
            'Notes Added',
            'Note added: ' . $request->notes,
            'Deal_Notes',
            getCreatedBy()
        );

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully.',
            'note_id' => $note_id
        ]);
    }

    public function get_deal_notes(Request $request)
    {
        $lead_id = decrypt($request->lead_id);

        $notes = DB::table('deal_notes')
            ->where('lead_id', $lead_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($note) {
                return [
                    'dn_id' => $note->dn_id,
                    'notes' => $note->notes,
                    'lead_name' => LeadmanageModel::where('lead_id', $note->lead_id)->value('lead_name'),
                    'created_by_name' => get_createdby_name($note->created_by),
                    'time_ago' => \Carbon\Carbon::parse($note->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notes
        ]);
    }


    public function deal_update_note(Request $request)
    {
        $request->validate([
            'dn_id' => 'required|integer|exists:deal_notes,dn_id',
            'notes' => 'required|string',
            'lead_id' => 'required'
        ]);

        try {
            $note = DB::table('deal_notes')
                ->where('dn_id', $request->dn_id)
                ->first();

            if (!$note) {
                return response()->json([
                    'success' => false,
                    'message' => 'Note not found'
                ], 404);
            }

            // Store the old note before updating
            $oldNote = $note->notes;

            $affected = DB::table('deal_notes')
                ->where('dn_id', $request->dn_id)
                ->update([
                    'notes' => $request->notes,
                    'updated_by' => getUpdatedBy(),
                    'updated_at' => now()
                ]);

            if ($affected) {
                insertLeadHistory(
                    decrypt($request->lead_id),
                    'Note Updated',
                    'Note updated from: "' . $oldNote . '" to: "' . $request->notes . '"',
                    'deal_notes',
                    getCreatedBy()
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Note updated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes made to note'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update note'
            ], 500);
        }
    }

    public function deal_delete_note(Request $request)
    {
        $request->validate([
            'dn_id' => 'required|integer|exists:deal_notes,dn_id',
        ]);

        try {
            $note = DB::table('deal_notes')
                ->where('dn_id', $request->dn_id)
                ->where('lead_id', decrypt($request->lead_id))
                ->first();

            if ($note) {
                DB::table('deal_notes')
                    ->where('dn_id', $request->dn_id)
                    ->delete();

                insertLeadHistory(
                    decrypt($request->lead_id),
                    'Note Deleted',
                    'Note deleted: ' . $note->notes,
                    'deal_notes',
                    getCreatedBy()
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Note deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Note not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete note'
            ], 500);
        }
    }


    public function updateStage(Request $request)
    {
        $request->validate([
            'stage_id' => 'required|integer',
            'deal_id' => 'required',
            'lead_id' => 'required',
        ]);

        // Decrypt the IDs if they were encrypted in the frontend
        $deal_id = $request->deal_id;
        $lead_id = decrypt($request->lead_id);

        $updated = DealModel::where('deal_id', $deal_id)
            ->where('lead_id', $lead_id)
            ->update([
                'stage_id' => $request->stage_id,
                'updated_at' => now()
            ]);

        if ($updated) {
            return response()->json([
                'status' => true,
                'message' => 'Stage updated successfully.'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'No record found or no changes made.'
            ]);
        }
    }


    public function deal_timeline_data(Request $request)
    {
        $lead_id = decrypt($request->lead_id);


        $history = DB::table('deal_history')
            ->where('lead_id', $lead_id)
            ->orderBy('changed_at', 'desc')
            ->get()
            ->map(function ($item) {
                $changeTypeFull = strtolower($item->change_type);
                $action = '';
                if (strpos($changeTypeFull, 'create') !== false) {
                    $action = 'created';
                } elseif (strpos($changeTypeFull, 'add') !== false) {
                    $action = 'added';
                } elseif (strpos($changeTypeFull, 'update') !== false) {
                    $action = 'updated';
                } elseif (strpos($changeTypeFull, 'delete') !== false) {
                    $action = 'deleted';
                } elseif (strpos($changeTypeFull, 'note') !== false) {
                    $action = 'note';
                } elseif (strpos($changeTypeFull, 'note') !== false) {
                    $action = 'Call Schedule';
                } else {
                    $action = 'default';
                }
                switch ($action) {
                    case 'created':
                        $display = 'Created';
                        $icon = 'bx bx-plus-circle';
                        $badge = 'created';
                        break;
                    case 'added':
                        $display = 'Added';
                        $icon = 'bx bx-plus';
                        $badge = 'added';
                        break;
                    case 'updated':
                        $display = 'Updated';
                        $icon = 'bx bx-sync';
                        $badge = 'updated';
                        break;
                    case 'deleted':
                        $display = 'Deleted';
                        $icon = 'bx bx-trash';
                        $badge = 'deleted';
                        break;
                    case 'note':
                        $display = 'Note';
                        $icon = 'bx bx-note';
                        $badge = 'note';
                        break;
                    case 'cancelled':
                        $display = 'cancelled';
                        $icon = 'bx bx-x';
                        $badge = 'cancelled';
                        break;
                    case 'Re-Scheduled':
                        $display = 'Call Re-Schedule';
                        $icon = 'bx bx-x';
                        $badge = 'Call Re-Schedule';
                        break;
                    case 'Completed':
                        $display = 'Mark as Completed';
                        $icon = 'bx bx-x';
                        $badge = 'Mark as Completed';
                        break;
                    default:
                        $display = ucfirst($changeTypeFull);
                        $icon = 'bx bx-info-circle';
                        $badge = 'default';
                }
                return [
                    'history_id' => $item->history_id,
                    'lead_id' => $item->lead_id,
                    'change_type' => $changeTypeFull,
                    'change_display' => $display,
                    'icon' => $icon,
                    'badge_class' => $badge,
                    'reason' => $item->reason,
                    'table_name' => $item->table_name,
                    'changed_by' => get_createdby_name($item->changed_by),
                    'changed_at' => $item->changed_at,
                    'date_display' => Carbon::parse($item->changed_at)->format('d M'),
                    'time_display' => Carbon::parse($item->changed_at)->format('h:i A'),
                ];
            })
            ->groupBy(function ($item) {
                return Carbon::parse($item['changed_at'])->format('d M Y');
            })
            ->sortKeysDesc();
        return response()->json($history);
    }


    public function getDealclosedwonDetails($lead_id)
    {

        $lead = LeadmanageModel::select(
            'company_name',
            'contact_person_name',
            'email',
            'phone',
            'countries_id',
            'state_id',
            'city_id'
        )->find($lead_id);

        if (!$lead) {
            return response()->json(['success' => false, 'message' => 'Lead not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $lead]);
    }
}
