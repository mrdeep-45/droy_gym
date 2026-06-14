<?php

namespace App\Http\Controllers;

use App\Models\LeadHistorymodel;
use App\Models\LeadmanageModel;
use App\Models\CallPurposeModel;
use App\Models\CallSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pusher\Pusher;
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
            'country_id' => 'nullable|exists:mst_country,c_id',
            'state_id' => 'nullable|exists:mst_state,state_id',
            'city_id' => 'nullable|exists:mst_city,city_id',
            'status_id' => 'nullable|exists:lead_status,status_id',
            'lead_date' => 'required|date',
            'notes' => 'nullable|string',
            'assign_to_user' => 'nullable|integer', // Add validation for assign_to_user
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
            // Only assign assign_to_user if the checkbox is checked and assign_to_user is not empty
            $assignTo = $request->has('assign_to_lead') && $request->filled('assign_to_user') ? $request->input('assign_to_user') : null;



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
                'assign_to' => $assignTo,
            ]);

            insertLeadHistory(
                $lead->lead_id,
                'Lead Created',
                'Lead created with name ' . $lead->lead_name,
                'lead_manage',
                getCreatedBy()
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
        $lead = LeadmanageModel::select(['*'])->orderBy('lead_id', 'desc')->where('status', 0)->get();
        $permissions = checkPermissions(get_index_route($this));
        $canUpdate = $permissions['canUpdate'] ?? false;
        $canDelete = $permissions['canDelete'] ?? false;
        foreach ($lead as $l) {
            $l->source_status = get_source_name($l->source) . ' <br> ' . get_status_name($l->status_id);
            $l->contact_details = $l->contact_person_name . '<br>' . $l->phone . '<br>' . $l->email;
            $l->lead_name = '<a href="' . route('lead.details', encrypt($l->lead_id)) . '" class=""><span class="text-primary">' . $l->lead_name . '</span></a><br><small class="text-muted">' . $l->lead_no . '</small>';
            $l->owner_name = get_createdby_name($l->created_by);
        }
        return DataTables::of($lead)
            ->addIndexColumn()
            ->rawColumns(['source_status', 'contact_details', 'lead_name', 'owner_name'])
            ->make(true);
    }
    public function lead_details_index(Request $request, $id)
    {
        $lead_id = decrypt($id);

        $page_title = 'Lead Details';
        $page_name = 'Lead Details';
        $call_purpose = CallPurposeModel::get();
        $source_id_get = get_lead_name($lead_id)['source'];
        // dd($source_id_get);
        $source = DB::table('mst_source')->select('source_id', 'source_name')->where('status', 0)->orderBy('source_name', 'asc')->get();
        $service = DB::table('mst_service_manage')->select('service_id', 'service_name', 'service_desc')->where('status', 0)->get();
        $lead = LeadmanageModel::where('status', 0)->orderBy('lead_name', 'asc')->get();
        $lead_status = DB::table('lead_status')->select('status_id', 'status_name')->where('status', 0)->orderBy('status_name', 'asc')->get();
        $stage = DB::table('deal_stage')->select('stage_name', 'id')->where('status', 0)->orderBy('stage_name', 'asc')->get();
        $outgoing_res = DB::table('tbl_outgoing_res')->get();
        return view('company/lead/lead_details', compact('page_title', 'source_id_get', 'page_name', 'id', 'lead_id', 'call_purpose', 'source', 'service', 'lead', 'lead_status', 'stage', 'outgoing_res'));
    }
    public function get_lead_details(Request $request)
    {
        $leadId = decrypt($request->lead_id);
        $lead = LeadmanageModel::where('lead_id', $leadId)->first();
        if (!$lead) {
            return response()->json([
                'status' => false,
                'message' => 'Lead not found',
            ], 404);
        }
        $sources = DB::table('mst_source')->select('source_id', 'source_name')->where('status', 0)->get();
        $lead_statuses = DB::table('lead_status')->select('status_id', 'status_name')->where('status', 0)->get();
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
                'status_id' => $lead->status_id ?? '-', // <-- ADD THIS LINE
                'deal_source' => get_source_name($lead->deal_source ?? 0),
                'deal_status_id' => $lead->deal_status_id ?? '-', // <-- ADD THIS LINE
                'status_name' => get_status_name($lead->status_id ?? 0),
                'lead_date' => Carbon::parse($lead->lead_date)->format('d-M-Y') ?? '-',
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
                'sources' => $sources,
                'lead_statuses' => $lead_statuses,
            ]
        ]);
    }
    public function store_note(Request $request)
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
        $note_id = DB::table('lead_notes')->insertGetId([
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
            'lead_notes',
            getCreatedBy()
        );
        return response()->json([
            'success' => true,
            'message' => 'Note added successfully.',
            'note_id' => $note_id
        ]);
    }
    public function get_lead_notes(Request $request)
    {
        $lead_id = decrypt($request->lead_id);
        $notes = DB::table('lead_notes')
            ->where('lead_id', $lead_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($note) {
                return [
                    'note_id' => $note->note_id,
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
    public function update_note(Request $request)
    {
        $request->validate([
            'note_id' => 'required|integer|exists:lead_notes,note_id',
            'notes' => 'required|string',
            'lead_id' => 'required'
        ]);
        try {
            $note = DB::table('lead_notes')
                ->where('note_id', $request->note_id)
                ->first();
            if (!$note) {
                return response()->json([
                    'success' => false,
                    'message' => 'Note not found'
                ], 404);
            }
            // Store the old note before updating
            $oldNote = $note->notes;
            $affected = DB::table('lead_notes')
                ->where('note_id', $request->note_id)
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
                    'lead_notes',
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
    public function delete_note(Request $request)
    {
        $request->validate([
            'note_id' => 'required|integer|exists:lead_notes,note_id',
        ]);
        try {
            $note = DB::table('lead_notes')
                ->where('note_id', $request->note_id)
                ->where('lead_id', decrypt($request->lead_id))
                ->first();
            if ($note) {
                DB::table('lead_notes')
                    ->where('note_id', $request->note_id)
                    ->delete();
                insertLeadHistory(
                    decrypt($request->lead_id),
                    'Note Deleted',
                    'Note deleted: ' . $note->notes,
                    'lead_notes',
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
    public function update_source_status(Request $request)
    {
        $validated = $request->validate([
            'source_id' => 'required|exists:mst_source,source_id',
            'status_id' => 'required|exists:lead_status,status_id'
        ]);
        $lead = LeadmanageModel::find(decrypt($request->lead_id));
        $isSourceChanged = ($lead->source != $request->source_id);
        $isStatusChanged = ($lead->status_id != $request->status_id);
        if (!$isSourceChanged && !$isStatusChanged) {
            return response()->json([
                'success' => false,
                'message' => 'No changes detected.'
            ]);
        }
        if ($isSourceChanged) {
            $oldSourceName = DB::table('mst_source')->where('source_id', $lead->source)->value('source_name') ?? 'N/A';
            $newSourceName = DB::table('mst_source')->where('source_id', $request->source_id)->value('source_name') ?? 'N/A';
            $lead->source = $request->source_id;
        }
        if ($isStatusChanged) {
            $oldStatusName = DB::table('lead_status')->where('status_id', $lead->status_id)->value('status_name') ?? 'N/A';
            $newStatusName = DB::table('lead_status')->where('status_id', $request->status_id)->value('status_name') ?? 'N/A';
            $lead->status_id = $request->status_id;
        }
        $lead->save();
        if ($isSourceChanged) {
            insertLeadHistory(
                decrypt($request->lead_id),
                'Source Updated',
                "Source changed from {$oldSourceName} to {$newSourceName}",
                'lead_manage',
                getCreatedBy()
            );
        }
        if ($isStatusChanged) {
            insertLeadHistory(
                decrypt($request->lead_id),
                'Status Updated',
                "Status changed from {$oldStatusName} to {$newStatusName}",
                'lead_manage',
                getCreatedBy()
            );
        }
        return response()->json([
            'success' => true,
            'message' => 'Source & Status updated successfully'
        ]);
    }
    public function lead_timeline_data(Request $request)
    {
        $lead_id = decrypt($request->lead_id);
        $history = DB::table('lead_history')
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
    // public function update_lead_status(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'lead_id' => 'required|exists:lead_manage,lead_id',
    //         'status' => 'required|string',
    //         'notes' => 'required|string|',
    //         'next_follow_up_date' => 'nullable|date',
    //     ]);
    //     DB::beginTransaction();
    //     try {
    //         $lead = LeadmanageModel::findOrFail($validatedData['lead_id']);
    //         $lead->status = $validatedData['status'];
    //         $lead->notes = $validatedData['notes'];
    //         if ($validatedData['next_follow_up_date']) {
    //             $lead->next_follow_up_date = Carbon::parse($validatedData['next_follow_up_date'])->format('Y-m-d');
    //         }
    //         $lead->save();
    //         insertLeadHistory(
    //             $lead->lead_id,
    //             'Status Updated',
    //             $validatedData['notes'],
    //             Carbon::parse($validatedData['next_follow_up_date'])->format('Y-m-d'),
    //             $validatedData['status'],
    //         );
    //         DB::commit();
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Lead updated successfully'
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function sendStaticInvoiceWhatsApp(Request $request)
    {
        $invoice = 1;
        $pdfUrl = asset('assets/uploads/company_doc/invoice.pdf');
        $phone = "";
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
    public function getLeadinfo(Request $request)
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
    public function callschedule(Request $request)
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
            if ($request->filled('schedule_id')) {
                $schedule = CallSchedule::find($request->schedule_id);
                if (!$schedule) {
                    return response()->json(['message' => 'Call schedule not found for update.'], 404);
                }
                $schedule->updated_at = now();
                $action = 'updated';
                $historyTitle = 'Call Schedule Updated';
            } else {
                $schedule = new CallSchedule();
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
            if ($request->call_type === 'log_call') {
                $schedule->is_log_call = 1;
            }
            $schedule->save();
            // Insert or Update into tbl_call_log if log_call
            if ($request->call_type === 'log_call') {
                $existingLog = DB::table('tbl_call_log')
                    ->where('cs_id', $schedule->cs_id)
                    ->where('lead_id', $lead_id)
                    ->first();
                if ($existingLog) {
                    // Update existing log
                    DB::table('tbl_call_log')
                        ->where('cs_id', $schedule->cs_id)
                        ->where('lead_id', $lead_id)
                        ->update([
                            'call_outcome_result' => $request->call_outcome_result ?? null,
                            'call_description' => $validated['outgoing_description'] ?? null,
                            'updated_at' => now()
                        ]);
                } else {
                    // Insert new log
                    DB::table('tbl_call_log')->insert([
                        'cs_id' => $schedule->cs_id,
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
                'tbl_call_schedule',
                $createdBy
            );
            if ($request->call_type === 'call_schedule') {
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
                    'cs_id' => $schedule->cs_id,
                    'lead_name' => $schedule->lead_name,
                    'email' => $lead->email ?? '',
                    'phone' => $lead->phone ?? '',
                    'company' => $lead->company_name ?? '',
                    'notes' => $lead->notes ?? '',
                    'lead_owner' => $lead->contact_person_name ?? '',
                    'lead_status' => get_status_name($lead->status_id) ?? '',
                    'trigger_at' => $schedule->call_date . ' ' . $schedule->call_time,
                    'message' => "It's time to call {$schedule->lead_name}!",
                    'created_by' => $user_name
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
    public function fetch(Request $request)
    {
        if ($request->ajax()) {
            $leadId = decrypt($request->lead_id);
            $lead_id = $request->lead_id;
            $data = CallSchedule::select('*')
                ->orderBy('cs_id', 'desc')
                ->where('lead_id', $leadId);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('callstarttime', function ($row) {
                    $date = Carbon::parse($row->call_date)->format('d/m/Y');
                    $time = Carbon::parse($row->call_time)->format('h:i A');
                    return $date . ' <br>' . $time;
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
                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row->cs_id . '">
                        <i class="bx bx-trash"></i>
                    </button>';
                    if ($row->completion_status == 1) {
                        $completedIcon = '
                        <i class="bx bx-check-circle text-success" title="Marked as Completed" style="font-size: 1.5rem;"></i>';
                        return '<div class="d-flex align-items-center gap-2">' . $completedIcon . '</div>';
                    }
                    if ($row->completion_status == 2) {
                        $rescheduleIcon = '
                        <i class="bx bx-calendar-check text-warning" title="Rescheduled Call on ' . $date . ' ' . $time . '" style="font-size: 1.5rem;"></i>';
                        $dropdown = '
                        <div class="dropdown d-inline">
                            <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item mark-completed" data-id="' . $row->cs_id . '" href="#">Mark as Completed</a></li>
                                <li><a class="dropdown-item reschedule-call" data-id="' . $row->cs_id . '" href="#">Reschedule Call</a></li>
                                <li><a class="dropdown-item cancel-call" data-lead="' . $lead_id . '" data-id="' . $row->cs_id . '" href="#">Cancel Call</a></li>
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
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $row->cs_id . '">
                        <i class="bx bx-edit"></i>
                    </button>';
                        $deleteBtn = '
                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row->cs_id . '">
                        <i class="bx bx-trash"></i>
                    </button>';
                        return '<div class="d-flex align-items-center gap-2">' . $editBtn .  $deleteBtn . '</div>';
                    }
                    $editBtn = '
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $row->cs_id . '">
                        <i class="bx bx-edit"></i>
                    </button>';
                    $dropdown = '
                    <div class="dropdown d-inline">
                        <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item mark-completed" data-id="' . $row->cs_id . '" href="#">Mark as Completed</a></li>
                            <li><a class="dropdown-item reschedule-call" data-id="' . $row->cs_id . '" href="#">Reschedule Call</a></li>
                            <li><a class="dropdown-item cancel-call" data-lead="' . $lead_id . '" data-id="' . $row->cs_id . '" href="#">Cancel Call</a></li>
                        </ul>
                    </div>';
                    return '<div class="d-flex align-items-center gap-2">' . $editBtn . $dropdown . $deleteBtn . '</div>';
                })
                ->rawColumns(['type', 'actions', 'call_outcome', 'callstarttime'])
                ->make(true);
        }
    }
    public function delete(Request $request)
    {
        try {
            $id = $request->id;
            $schedule = CallSchedule::where('cs_id', $id)->first();
            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Call schedule not found.',
                ]);
            }
            // Optional lead history insert if lead_id is provided
            if ($request->filled('lead_id')) {
                try {
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
                } catch (\Exception $ex) {
                    Log::error('Error inserting lead history during schedule deletion: ' . $ex->getMessage());
                }
            }
            // Delete the schedule
            $schedule->delete();
            return response()->json([
                'success' => true,
                'message' => 'Call schedule deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Schedule deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting schedule.',
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function fetchcalldata(Request $request)
    {
        $cs_id = $request->id;
        $call = CallSchedule::where('cs_id', $cs_id)->first();
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
    public function save_call_outcome(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|string',
            'call_result' => 'required|string',
            'call_result_desc' => 'nullable|string',
            'cs_id' => 'required|string',
        ]);
        try {
            $lead_id = decrypt($request->lead_id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid lead ID.',
            ], 400);
        }
        $cs_id = $request->cs_id;
        $lead = CallSchedule::where('lead_id', $lead_id)
            ->where('cs_id', $cs_id)
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
    public function rescheduleCall(Request $request)
    {
        $request->validate([
            'lead_id' => 'required',
            'call_date' => 'required|date',
            'call_time' => 'required',
            'cs_id' => 'required',
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
        $call = CallSchedule::where('lead_id', $lead_id)
            ->where('cs_id', $request->cs_id)
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
        $lead = LeadmanageModel::find($lead_id); // Replace with your actual lead model
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
        insertLeadHistory(
            $lead_id,
            "Call Re-Scheduled",
            "Call Re-Scheduled  by {$user_name} for lead {$lead_name}",
            'tbl_call_schedule',
            $createdBy
        );
        $pusher->trigger('deal-call.' . $createdBy, 'call.scheduled', [
            'cs_id' => $call->cs_id,
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
    public function cancelCall(Request $request)
    {
        $request->validate([
            'cs_id'       => 'required|integer',
            'lead_id'     => 'required',
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
        $call = CallSchedule::where('cs_id', $request->cs_id)
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
    public function fetchCallLog(Request $request)
    {
        $cs_id = $request->cs_id;
        $log = DB::table('tbl_call_log')
            ->where('cs_id', $cs_id)
            ->first();
        if ($log) {
            return response()->json(['success' => true, 'data' => $log]);
        }
        return response()->json(['success' => false, 'message' => 'Call log not found.']);
    }
    public function uploadLeadFile(Request $request)
    {
        $request->validate([
            'lead_file_upload' => 'required',
            'lead_id' => 'required|string',
            'type' => 'required|integer',
        ]);
        try {
            $lead_id = decrypt($request->lead_id);
            if (!is_int($lead_id) && !ctype_digit($lead_id)) {
                return response()->json(['success' => false, 'message' => 'Invalid lead ID.']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid lead ID format.']);
        }
        if ($request->hasFile('lead_file_upload')) {
            $files = $request->file('lead_file_upload');
            $folder = 'lead_files';
            $insertData = [];
            foreach ($files as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $filename = $originalName . '-' . time() . '.' . $extension;
                $uploadPath = public_path('assets/uploads/' . trim($folder, '/'));
                $file->move($uploadPath, $filename);
                $data = [
                    'lead_id' => $lead_id,
                    'type' => $request->type,
                    'file' => $filename,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => getCreatedBy(),
                    'updated_by' => getCreatedBy(),
                ];
                if ($request->type == 1) {
                    $data['status'] = 1;
                }
                $insertData[] = $data;
            }

            // Insert all records at once
            $inserted = DB::table('tbl_file_call')->insert($insertData);
            if ($inserted) {
                return response()->json(['success' => true, 'message' => 'Files uploaded successfully.']);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to insert records.']);
            }
        }
        return response()->json(['success' => false, 'message' => 'No files uploaded.']);
    }
    public function getLeadFiles(Request $request)
    {

        if ($request->ajax()) {
            $type = $request->input('type', 0); // default to 0 if not passed
            $query = DB::table('tbl_file_call')
                ->where('type', $type)
                ->where('lead_id', $request->lead_id)
                ->orderBy('fc_id', 'desc');
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('file', function ($row) use ($type) {
                    $fileUrl = $this->actual_path . '/lead_files/' . $row->file;
                    return '<a class="text-truncate" href="' . $fileUrl . '" target="_blank">' . $row->file . '</a>';
                })
                ->addColumn('actions', function ($row) {
                    return '<button class="btn btn-sm p-1 btn-outline-danger delete-file" data-id="' . $row->fc_id . '" title="Delete File">
                            <i class="bx bx-trash"></i>
                        </button>';
                })
                ->rawColumns(['file', 'actions'])
                ->make(true);
        }
        abort(404);
    }
    public function deleteLeadFile(Request $request)
    {
        $fc_id = $request->input('fc_id');
        if (!$fc_id) {
            return response()->json([
                'success' => false,
                'message' => 'File ID is required.'
            ]);
        }
        $file = DB::table('tbl_file_call')->where('fc_id', $fc_id)->first();
        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.'
            ]);
        }
        // Delete physical file from storage if exists
        $filePath = public_path('admin_assets/uploads/lead_files/' . $file->file);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        // Delete DB record
        DB::table('tbl_file_call')->where('fc_id', $fc_id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully.'
        ]);
    }


    public function getCompanyNames(Request $request)
    {
        $term = $request->get('term');

        $results = DB::table('lead_manage')
            ->select('company_name', 'lead_name', 'created_by')
            ->where('company_name', 'LIKE', '%' . $term . '%')
            ->limit(10)
            ->get();

        $data = [];
        foreach ($results as $row) {
            // Get the name of the creator using your function
            $createdByName = $this->get_createdby_name($row->created_by);

            $data[] = [
                'label' => $row->company_name . ' (' . $row->lead_name . ')' . ' [' . $createdByName . ']',
                'value' => $row->company_name,
                'created_by' => $row->created_by // Include created_by in the response
            ];
        }

        return response()->json($data);
    }

    function get_createdby_name($created_by)
    {
        $name = null;

        $staff = DB::table('mst_staff')->where('staff_id', $created_by)->value('staff_name');
        if ($staff) {
            $name = $staff;
        } else {
            $company = DB::table('companies')->where('company_id', $created_by)->value('name');
            if ($company) {
                $name = $company;
            } else {
                $admin = DB::table('super_admin')->where('admin_id', $created_by)->value('name');
                if ($admin) {
                    $name = $admin;
                }
            }
        }

        return $name;
    }
}
