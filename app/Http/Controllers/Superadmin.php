<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ForgotOutRequestmodel;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequestHistory;
use App\Models\Attendancemodel;
use App\Models\Staffmodel;
use App\Models\ForgotOutRequestHistory;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet; 
use PhpOffice\PhpSpreadsheet\Style\Alignment; 
use PhpOffice\PhpSpreadsheet\Style\Fill; 
use PhpOffice\PhpSpreadsheet\Style\Border; 
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection; 
use Carbon\Carbon;

class Superadmin extends Controller
{
    public function index()
    {
        $page_title = 'SuperAdmin Dashboard';
        $page_name = 'SuperAdmin Dashboard';
       // return view('company/superadmin/index', compact('page_title', 'page_name'));
        return view('superadmin/index', compact('page_title', 'page_name'));
    }

     public function index12()
    {
        $page_title = 'Leave Request';
        $page_name = 'Leave Request';
        
        return view('superadmin.leave_requests', compact('page_title', 'page_name'));
    }

    public function index13()
    {
        $page_title = 'Leave Allocation';
        $page_name = 'Leave Allocation';

        
        return view('superadmin.leave_balance', compact('page_title', 'page_name'));
    }
     public function attendanceview()
{
    $page_title = 'Attendance View';
    $page_name = 'Attendance View';
    $staffList = Staffmodel::all();
    
    return view('superadmin.attendance_view', compact('page_title', 'page_name', 'staffList'));
}

  public function showledgerreport()
{
    $page_title = 'Staff Ledger Report';
    $page_name = 'Staff Ledger Report';
    $staffList = Staffmodel::all();
    
    return view('superadmin.showledgerreport', compact('page_title', 'page_name', 'staffList'));
}

    public function showDetailedAttendanceReport()
    {
        $page_title = 'Detailed Staff Attendance Report';
        $page_name = 'Detailed Attendance Report';
        $staffList = Staffmodel::all();
        
        // We will use a new view file named 'superadmin.show_detailed_attendance_report'
        return view('superadmin.show_detailed_attendance_report', compact('page_title', 'page_name', 'staffList'));
    }

    /**
     * Fetches and processes detailed daily attendance data for DataTables.
     */
    public function getDetailedAttendanceData(Request $request)
    {
        $staffId = $request->input('staff_id');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        
        try {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid month/year format'], 400);
        }

        // Define the required start time
        $requiredTime = '10:30:00'; 

        $query = Attendancemodel::with('staff:staff_id,staff_name') // Eager load only necessary staff fields
            ->whereBetween('time_in', [$startDate, $endDate])
            ->whereNotNull('time_in')
            ->when($staffId, fn($q) => $q->where('staff_id', $staffId));
            
        $records = $query->get();

        $data = [];
        foreach ($records as $record) {
            
            $timeIn = $record->time_in ? Carbon::parse($record->time_in) : null;
            $timeOut = $record->time_out ? Carbon::parse($record->time_out) : null;
            
            $lateDuration = 'N/A';
            if ($timeIn) {
                // Get the required start time (10:30:00) on the day the staff clocked in
                $requiredStartTime = $timeIn->copy()->setTimeFromTimeString($requiredTime);
                
                if ($timeIn->gt($requiredStartTime)) {
                    $diff = $timeIn->diff($requiredStartTime);
                    
                    $hours = $diff->h;
                    $minutes = $diff->i;
                    
                    // Format late duration (e.g., "1 hr 3 min late")
                    $lateDuration = trim(($hours > 0 ? $hours . ' hr ' : '') . $minutes . ' min') . ' late';
                } else {
                    $lateDuration = 'On Time';
                }
            }
            
            // Determine forgot status
            // A record exists (time_in is not null) but time_out is null
            $forgotOutStatus = (!$timeOut && $timeIn) ? 'Yes' : 'No';

            $data[] = [
                'date' => $timeIn ? $timeIn->format('d-m-Y (D)') : 'N/A',
                'staff_name' => $record->staff->staff_name ?? 'N/A',
                'time_in' => $timeIn ? $timeIn->format('H:i:s') : 'N/A',
                'time_out' => $timeOut ? $timeOut->format('H:i:s') : 'N/A',
                'face_image_in_url' => $record->face_image_in_url ?? null,
                'face_image_out_url' => $record->face_image_out_url ?? null,
                'late_duration' => $lateDuration,
                'forgot_out_status' => $forgotOutStatus,
            ];
        }
        
        return DataTables::of($data)
            // Format the image URLs as clickable links
            ->editColumn('face_image_in_url', function($row) {
                return $row['face_image_in_url'] 
                    ? '<a href="' . asset($row['face_image_in_url']) . '" target="_blank" class="btn btn-primary">View Image</a>' 
                    : 'N/A';
            })
            ->editColumn('face_image_out_url', function($row) {
                return $row['face_image_out_url'] 
                    ? '<a href="' . asset($row['face_image_out_url']) . '" target="_blank" class="btn btn-primary">View Image</a>' 
                    : 'N/A';
            })
            ->rawColumns(['face_image_in_url', 'face_image_out_url'])
            ->make(true);
    }


    public function showForgotTimeoutRequests()
{
    $requests = ForgotOutRequestmodel::with('staff', 'attendance')->get();
    // $requests = ForgotOutRequestmodel::with('staff', 'attendance')->where('status', 'pending')->get();

    $page_title = "Forgot Timeout Requests"; // 

   return view('superadmin.forgot_timeout_requests', compact('requests', 'page_title'));
    //return view('company.superadmin.forgot_timeout_requests', compact('requests', 'page_title'));

}
public function getForgetTimeoutList(Request $request)
{
    if ($request->ajax()) {
        $data = ForgotOutRequestmodel::with('staff')->latest()->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('staff_name', function($row) {
                return $row->staff->staff_name ?? 'N/A';
            })
            ->addColumn('date', function($row) {
                return \Carbon\Carbon::parse($row->date)->format('d-m-Y');
            })
            ->addColumn('description', function($row) {
                return $row->description;
            })
            ->addColumn('action', function($row) {
                $statusClass = match($row->status) {
                    'approved' => 'btn-success',
                    'rejected' => 'btn-danger',
                    default => 'btn-warning',
                };

                // Fetch admin-only history (approved/rejected)
    $history = $row->history()
        ->whereIn('status', ['submitted', 'resubmitted'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function($h) {
            return [
                'status' => $h->status,
                'remark' => $h->remark,
                'created_at' => $h->created_at->format('d-m-Y H:i')
            ];
        })->values();

    // Encode to safe JSON for use in data-attribute
    $historyJson = htmlspecialchars(json_encode($history), ENT_QUOTES, 'UTF-8');

                return '
                    <button type="button"
                        class="btn btn-sm ' . $statusClass . '"
                        data-bs-toggle="modal"
                        data-bs-target="#statusModal"
                        data-id="' . $row->id . '"
                        data-status="' . $row->status . '"
                        data-name="' . ($row->staff->staff_name ?? 'N/A') . '"
                        data-date="' . \Carbon\Carbon::parse($row->date)->format('d-m-Y') . '"
                        data-description="' . $row->description . '"
                        data-history="' . $historyJson . '"
                    >
                        ' . ucfirst($row->status) . '
                    </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}


/*public function showForgotTimeoutRequests()
{
    // Check if user is logged in as Super Admin
    if (session('login_type') !== 'Super Admin') {
        return redirect('/login')->with('error', 'Please log in as Super Admin to continue.');
    }

    $requests = ForgotOutRequestmodel::with('staff', 'attendance')->get();
    $page_title = "Forgot Timeout Requests";

    return view('company.superadmin.forgot_timeout_requests', compact('requests', 'page_title'));
}
    */


    public function approveForgotTimeout(Request $request)
{
    $request->validate([
        'request_id' => 'required|exists:forgot_out_requests,id',
        'time_out' => 'required|date_format:H:i',
        'remark' => 'nullable|string|max:500'
    ]);

    $forgot = ForgotOutRequestmodel::with('attendance')->find($request->input('request_id'));

    if (!$forgot->attendance) {
        return response()->json(['message' => 'Attendance record not found.'], 404);
    }

    $forgot->attendance->update([
        'time_out' => $forgot->attendance->time_in->format('Y-m-d') . ' ' . $request->time_out,
    ]);

    $forgot->status = 'approved';
    $forgot->save();

    // Save to history
    ForgotOutRequestHistory::create([
        'request_id' => $forgot->id,
        'status' => 'approved',
        'remark' => $request->remark,
    ]);

    return response()->json(['message' => 'Time-out updated and request approved.']);
}

    public function rejectForgotTimeout(Request $request)
{
    $request->validate([
        'request_id' => 'required|exists:forgot_out_requests,id',
        'remark' => 'required|string|max:500'
    ]);

    $forgot = ForgotOutRequestmodel::find($request->input('request_id'));
    $forgot->status = 'rejected';
    $forgot->save();

     // Save rejection history
    ForgotOutRequestHistory::create([
        'request_id' => $forgot->id,
        'status' => 'rejected',
        'remark' => $request->remark,
    ]);

    return response()->json(['message' => 'Request rejected.']);
}

public function listLeaveRequests(Request $request)
{
    $query = LeaveRequest::with(['staff', 'staff.role']) // eager load relations
        ->orderByDesc('created_at');

    return datatables()->of($query)
        ->addIndexColumn()
        
        // Safely build staff name with role
        ->addColumn('staff_name', function ($row) {
            $staffName = optional($row->staff)->staff_name ?? 'N/A';
            $roleName = optional(optional($row->staff)->role)->role_name;
            return $roleName ? "{$staffName} ({$roleName})" : $staffName;
        })

        // Leave duration: start to end date
        ->addColumn('duration', function ($row) {
            $start = $row->start_date ? $row->start_date->format('d-m-Y') : '-';
            $end = $row->end_date ? $row->end_date->format('d-m-Y') : '-';
             return $start === $end ? $start : "$start to $end";
        })

        // Total leave days
        ->addColumn('total_days', function ($row) {
            return $row->total_days ?? '-';
        })

        // Leave type (capitalized)
        ->addColumn('leave_type', function ($row) {
            return strtoupper($row->leave_type ?? '-');
        })

        // Reason
        ->addColumn('reason', function ($row) {
            return $row->reason ?? '-';
        })

        ->editColumn('status', function ($row) {
    $status = strtolower($row->status);
    $label = ucfirst($status);

    $badgeClass = match($status) {
        'approved' => 'success',
        'rejected' => 'danger',
        default => 'info', // pending or others
    };

    return '<span class="badge text-bg-' . $badgeClass . '">' . $label . '</span>';
})


        // Action buttons
        ->addColumn('action', function ($row) {
            $historyJson = htmlspecialchars(json_encode($row->history ?? []));
            $isProcessed = $row->status !== 'Pending';

            // Safe access for leave balance
            $leaveBalance = optional(optional($row->staff)->leaveBalance)[$row->leave_type] ?? 0;

            return '
                <button
                    class="btn btn-sm btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#statusModal"
                    data-id="'.($row->leavemid ?? '').'"
                    data-name="'.(optional($row->staff)->staff_name ?? '').'"
                    data-start-date="'.($row->start_date ? $row->start_date->format('d-m-Y') : '-').'"
                    data-end-date="'.($row->end_date ? $row->end_date->format('d-m-Y') : '-').'"
                    data-duration="'.(
                        ($row->start_date && $row->end_date && $row->start_date->format('d-m-Y') === $row->end_date->format('d-m-Y')) 
                        ? $row->start_date->format('d-m-Y') 
                        : ($row->start_date ? $row->start_date->format('d-m-Y') : '-') . ' to ' . ($row->end_date ? $row->end_date->format('d-m-Y') : '-')
                    ).'"
                    data-reason="'.htmlspecialchars($row->reason ?? '-').'"
                    data-total-days="'.($row->total_days ?? '-').'"
                    data-type="'.strtoupper($row->leave_type ?? '-').'"
                    data-balance="'.$leaveBalance.'"
                    data-notes="'.htmlspecialchars($row->notes ?? '').'"
                    data-status="'.$row->status.'"
                    data-history=\''.$historyJson.'\'
                    data-processed="'.($isProcessed ? 'yes' : 'no').'"
                >
                    Action
                </button>';
        })

        ->rawColumns(['action','status'])
        ->make(true);
}

/*
public function approveLeave(Request $request)
{
    $leaveId = $request->input('leavemid');
    $remark = $request->input('remark', null);

   // $leave = LeaveRequest::findOrFail($leaveId);
    $leave = LeaveRequest::with('staff')->findOrFail($leaveId);

    $leave->status = 'approved';
    $leave->save();

    // Save to history
    LeaveRequestHistory::create([
        'leavemid' => $leave->leavemid,
        'status' => 'approved',
        'remark' => $remark,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // 3. Loop over each day in leave duration and insert attendance
    $start = Carbon::parse($leave->start_date);
    $end = Carbon::parse($leave->end_date);

   while ($start->lte($end)) {
    Attendancemodel::updateOrCreate(
        [
            'staff_id' => $leave->staff_id,
            'date' => $start->toDateString()
        ],
        [
            'date' => $start->toDateString(), //  Required for insert
            'status' => 'leave',
            'leave_type' => $leave->leave_type,
            'time_in' => null,
            'time_out' => null,
            'lunch_out' => null,
            'lunch_in' => null,
            'forgot_status' => 0,        // integer 0 or 1, not string
        'late_status' => null,       // or 'On Time' or 'Late' from enum values
        ]
    );

    $start->addDay();
}


    return response()->json(['message' => 'Leave request approved successfully.']);
}
*/
public function approveLeave(Request $request)
{
    DB::beginTransaction();

    try {
        $leaveId = $request->input('leavemid');
        $remark = $request->input('remark', null);

        // Load leave request with staff
        $leave = LeaveRequest::with('staff')->findOrFail($leaveId);

        // Check if already approved/rejected
        if ($leave->status !== 'Pending') {
            return response()->json([
                'message' => 'Leave request is already processed.',
            ], 400);
        }

        $leaveType = $leave->leave_type; // CL, PL, SL, LWP
        $staffId = $leave->staff_id;
        $totalDays = $leave->total_days;
        $year = Carbon::parse($leave->start_date)->year;

        // Get leave balance record
        $balance = LeaveBalance::where('staff_id', $staffId)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            return response()->json([
                'message' => 'Leave balance not initialized. Contact admin.',
            ], 422);
        }
/*
        // Check if enough leave is available
        if ($balance->$leaveType < $totalDays) {
            return response()->json([
                'message' => "Insufficient $leaveType balance. Only {$balance->$leaveType} day(s) left.",
            ], 422);
        }

        // Deduct leave balance
       // $balance->decrement($leaveType, $totalDays);
        //$balance->increment('used', $totalDays);

        $usedField = strtolower($leaveType) . '_used';
$balance->$usedField += $totalDays;
$balance->used += $totalDays; // Optional if you still want overall used count
$balance->save();
*/
// If leave type is not LWP, validate and deduct balance
        if (in_array($leaveType, ['CL', 'PL', 'SL','LWP'])) {
            $leaveKey = strtolower($leaveType); // 'CL' → 'cl'
            $allocatedField = $leaveKey . '_allocated';
            $usedField = $leaveKey . '_used';

            $allocated = $balance->$allocatedField;
            $used = $balance->$usedField;
            $available = $allocated - $used;

            if ($available < $totalDays) {
                return response()->json([
                    'message' => "Insufficient $leaveType balance. Only {$available} day(s) left.",
                ], 422);
            }

            // Deduct used balance
            $balance->$usedField += $totalDays;
            $balance->used += $totalDays; // optional global counter
            $balance->save();
        }

        // Approve leave
        $leave->status = 'approved';
        $leave->save();

        // Save history
        LeaveRequestHistory::create([
            'leavemid' => $leave->leavemid,
            'status' => 'approved',
            'remark' => $remark,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Attendance entries per day
        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        while ($start->lte($end)) {
            Attendancemodel::updateOrCreate(
                [
                    'staff_id' => $leave->staff_id,
                    'date' => $start->toDateString()
                ],
                [
                    'date' => $start->toDateString(),
                    'status' => 'leave',
                    'leave_type' => $leaveType,
                    'time_in' => $start->copy()->setTime(10, 30, 0),
                    'time_out' => null,
                    'lunch_out' => null,
                    'lunch_in' => null,
                    'forgot_status' => 0,
                    'late_status' => null,
                ]
            );

            $start->addDay();
        }

        DB::commit();

        return response()->json(['message' => 'Leave request approved and balance updated.']);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Error approving leave',
            'error' => $e->getMessage()
        ], 500);
    }
}



public function rejectLeave(Request $request)
{
    $leaveId = $request->input('leavemid');
    $remark = $request->input('remark');

    $leave = LeaveRequest::findOrFail($leaveId);

      // Prevent re-rejecting or re-approving an already processed request
    if ($leave->status !== 'Pending') {
        return response()->json([
            'message' => 'This leave request has already been processed.',
        ], 400);
    }
    
    $leave->status = 'rejected';
    $leave->save();

    // Save to history
    LeaveRequestHistory::create([
        'leavemid' => $leave->leavemid,
        'status' => 'rejected',
        'remark' => $remark,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json(['message' => 'Leave request rejected successfully.']);
}
// LEAVE ALLOCATION
/* old code 13-8-25
public function leaveBalanceList(Request $request)
{
    $query = LeaveBalance::with('staff');

    return datatables()->of($query)
        ->addIndexColumn()
       ->addColumn('staff_name', function ($row) {
    $cl = $row->CL ?? 0;
    $sl = $row->SL ?? 0;
    $pl = $row->PL ?? 0;
    $lwp = $row->LWP ?? 0;

    $subText = "<small>CL-$cl SL-$sl PL-$pl LWP-$lwp</small>";

    return $row->staff->staff_name . '<br>' . $subText;
})

        ->addColumn('cl', function ($row) {
            return '<input type="number" class="form-control cl-input" data-id="'.$row->leavebmid.'" value="'.$row->CL.'" />';
        })
        ->addColumn('pl', function ($row) {
            return '<input type="number" class="form-control pl-input" data-id="'.$row->leavebmid.'" value="'.$row->PL.'" />';
        })
        ->addColumn('sl', function ($row) {
            return '<input type="number" class="form-control sl-input" data-id="'.$row->leavebmid.'" value="'.$row->SL.'" />';
        })
        ->addColumn('lwp', function ($row) {
            return '<input type="number" class="form-control lwp-input" data-id="'.$row->leavebmid.'" value="'.$row->LWP.'" />';
        })

        ->editColumn('year', fn($row) => $row->year)

        ->addColumn('action', fn($row) =>
            '<button class="btn btn-sm btn-primary edit" data-id="'.$row->leavebmid.'">Edit</button>'
        )

        //  Important: Tell Laravel not to escape HTML in these columns
        ->rawColumns(['cl', 'pl', 'sl', 'lwp', 'action','staff_name'])

        ->make(true);
}
        */
public function leaveBalanceList(Request $request)
{
    $year = $request->get('year', Carbon::now()->year); // default to current year

    $query = LeaveBalance::with(['staff.role']) // assuming staff has a role relationship
                ->where('year', $year);

    return datatables()->of($query)
        ->addIndexColumn()
        ->addColumn('staff_name', function ($row) {
            $staff = $row->staff;
            $salute = $staff->salute ?? '';
            $name = $staff->staff_name ?? '';
            $role = $staff->role->role_name ?? ''; // ensure relationship exists

           // $cl = $row->CL ?? 0;
           // $sl = $row->SL ?? 0;
          //  $pl = $row->PL ?? 0;
           // $lwp = $row->LWP ?? 0;
           $cl_remaining = ($row->cl_allocated ?? 0) - ($row->cl_used ?? 0);
           $pl_remaining = ($row->pl_allocated ?? 0) - ($row->pl_used ?? 0);
           $sl_remaining = ($row->sl_allocated ?? 0) - ($row->sl_used ?? 0);
           $lwp_remaining = ($row->lwp_allocated ?? 0) - ($row->lwp_used ?? 0);

            $subText = "<small>credit:CL-$cl_remaining  PL-$pl_remaining SL-$sl_remaining LWP-$lwp_remaining</small>";

            return "<strong>$salute $name</strong> <br><em>($role)</em><br>$subText";
        })

        ->addColumn('cl', fn($row) => '<input type="number" class="form-control cl-input" data-id="'.$row->leavebmid.'" value="'.$row->cl_allocated.'" />')
        ->addColumn('pl', fn($row) => '<input type="number" class="form-control pl-input" data-id="'.$row->leavebmid.'" value="'.$row->pl_allocated.'" />')
        ->addColumn('sl', fn($row) => '<input type="number" class="form-control sl-input" data-id="'.$row->leavebmid.'" value="'.$row->sl_allocated.'" />')
        ->addColumn('lwp', fn($row) => '<input type="number" class="form-control lwp-input" data-id="'.$row->leavebmid.'" value="'.$row->lwp_allocated.'" />')
        ->editColumn('year', fn($row) => $row->year)
        ->addColumn('action', fn($row) =>
            '<button class="btn btn-sm btn-primary edit" data-id="'.$row->leavebmid.'">Edit</button>'
        )
        ->rawColumns(['cl', 'pl', 'sl', 'lwp', 'action', 'staff_name'])
        ->make(true);
}
public function updateBalance(Request $request)
{
    $validated = $request->validate([
        'leavebmid' => 'required|exists:leave_balances,leavebmid',
        'cl' => 'nullable|numeric|min:0',
        'pl' => 'nullable|numeric|min:0',
        'sl' => 'nullable|numeric|min:0',
        'lwp' => 'nullable|numeric|min:0',
    ]);

    $leaveBalance = LeaveBalance::find($validated['leavebmid']);

   // ✅ Update only allocated values
    $leaveBalance->cl_allocated = $validated['cl'] ?? $leaveBalance->cl_allocated;
    $leaveBalance->pl_allocated = $validated['pl'] ?? $leaveBalance->pl_allocated;
    $leaveBalance->sl_allocated = $validated['sl'] ?? $leaveBalance->sl_allocated;
    $leaveBalance->lwp_allocated = $validated['lwp'] ?? $leaveBalance->lwp_allocated;
    //$leaveBalance->{$validated['type']} += $validated['value'];
    $leaveBalance->save();

    return response()->json([
        'success' => true, // Add this line
        'message' => 'Leave Balance updated successfully.',
        'data' => [
        'CL' => $leaveBalance->cl_allocated - $leaveBalance->cl_used,
        'SL' => $leaveBalance->sl_allocated - $leaveBalance->sl_used,
        'PL' => $leaveBalance->pl_allocated - $leaveBalance->pl_used,
        'LWP' => $leaveBalance->lwp_allocated - $leaveBalance->lwp_used,
    ]
    ]);
}

    public function data(Request $request)
    {
        $staffId = $request->input('staff_id');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $dateRange = collect(range(1, $daysInMonth))
            ->map(fn($d) => Carbon::createFromDate($year, $month, $d)->format('Y-m-d'));

        $staffList = Staffmodel::with('role')->when($staffId, fn($q) => $q->where('staff_id', $staffId))->get();

        $attendance = Attendancemodel::whereYear('time_in', $year)
            ->whereMonth('time_in', $month)
            ->get()
            ->groupBy(['staff_id', fn($rec) => Carbon::parse($rec->time_in)->format('Y-m-d')]);

        $leaves = LeaveRequest::where('status', 'approved')
            ->whereYear('start_date', '<=', $year)
            ->whereYear('end_date', '>=', $year)
            ->whereMonth('start_date', '<=', $month)
            ->whereMonth('end_date', '>=', $month)
            ->get()
            ->groupBy('staff_id');

        $data = [];

        foreach ($staffList as $staff) {
            $roleName = $staff->role->role_name ?? 'N/A'; 
    $row = [
        'staff_name' => $staff->staff_name . ' (' . ucfirst($roleName) . ')'
    ];


            foreach ($dateRange as $date) {
                $att = $attendance[$staff->staff_id][$date][0] ?? null;
                $leave = ($leaves[$staff->staff_id] ?? collect())->firstWhere(fn($lv) =>
                    $date >= $lv->start_date && $date <= $lv->end_date
                );

                if ($att && $att->time_in && $att->time_out) {
                    // $row[$date] = '<span style="color:green;">✅</span>';
                    $row[$date] = '<button type="button" class="btn btn-sm btn-success attendance-detail-btn" ' .
                  'data-staff-id="' . $staff->staff_id . '" ' .
                  'data-date="' . $date . '" ' .
                  'data-bs-toggle="modal" data-bs-target="#attendanceDetailModal" ' .
                  'style="padding: 2px 5px; font-size: 10px;">✅</button>';
                } elseif ($leave) {
                    $row[$date] = 'L (' . strtoupper($leave->leave_type) . ')';
                } else {
                    $row[$date] = '<span style="color:red;">X</span>';
                }
            }

            $data[] = $row;
        }

        // Add 'staff_name' to the list of raw columns
        return DataTables::of($data)->rawColumns(array_merge(['staff_name'], $dateRange->toArray()))->make(true);
    }
    private function getAttendanceDataForExport(Request $request)
    {
        
        $staffId = $request->input('staff_id');
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        
        $dateRange = collect(range(1, $daysInMonth))
            ->map(fn($d) => Carbon::createFromDate($year, $month, $d)->format('Y-m-d'));
        
        $staffList = Staffmodel::with('role')->when($staffId, fn($q) => $q->where('staff_id', $staffId))->get();
        
        $attendance = Attendancemodel::whereYear('time_in', $year)
            ->whereMonth('time_in', $month)
            ->get()
            ->groupBy(['staff_id', fn($rec) => Carbon::parse($rec->time_in)->format('Y-m-d')]);
            
        $leaves = LeaveRequest::where('status', 'approved')
            ->whereYear('start_date', '<=', $year)
            ->whereYear('end_date', '>=', $year)
            ->whereMonth('start_date', '<=', $month)
            ->whereMonth('end_date', '>=', $month)
            ->get()
            ->groupBy('staff_id');
            
        $data = [];
        $header = ['Staff Name'];
        $header = array_merge($header, $dateRange->map(fn($date) => Carbon::parse($date)->format('D, d'))->toArray());

        $logoPath = public_path('assets/admin_assets/images/brand-logos/logo.jpg'); 
        foreach ($staffList as $staff) {
            $roleName = $staff->role ? $staff->role->role_name : 'N/A';
            $row = [
                'Staff Name' => $staff->staff_name . ' (' . ucfirst($roleName) . ')'
            ];
            
            foreach ($dateRange as $date) {
                $att = $attendance[$staff->staff_id][$date][0] ?? null;
                $leave = ($leaves[$staff->staff_id] ?? collect())->firstWhere(function ($lv) use ($date) {
    
    if ($lv === null) {
        return false;
    }
    // Perform date comparison only if $lv exists
    return $date >= $lv->start_date && $date <= $lv->end_date;
});
                
                if ($att && $att->time_in && $att->time_out) {
                     //$row[$date] = '✅';  // Present
                     $row[$date] = '✔';  // Present
                } elseif ($leave) {
                    //$row[$date] = 'L (' . strtoupper($leave->leave_type) . ')'; // Leave
                    $row[$date] = 'L (' . strtoupper($leave->leave_type) . ')'; // Leave
                } else {
                     //$row[$date] = '❌';  // Absent/No Data
                      $row[$date] = '✖'; 
                }
            }
            $data[] = $row;
        }

        return ['data' => $data, 'header' => $header, 'year' => $year, 'monthName' => Carbon::createFromDate($year, $month, 1)->format('F'),'logoPath' => $logoPath];
    }
    public function exportExcel(Request $request)
    {
        $exportData = $this->getAttendanceDataForExport($request);
        $monthName = $exportData['monthName'];
        $year = $exportData['year'];
        
        $data = $exportData['data'];
        $header = $exportData['header'];
        $logoPath = $exportData['logoPath']; // Keep this for potential future logo use
        $filename = "Attendance_Report_{$monthName}_{$year}.xlsx";

        return Excel::download(new class($data, $header, $monthName, $year, $logoPath) implements 
            \Maatwebsite\Excel\Concerns\FromArray, 
            \Maatwebsite\Excel\Concerns\WithHeadings, 
            \Maatwebsite\Excel\Concerns\WithEvents,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize,
            \Maatwebsite\Excel\Concerns\WithDrawings 
        {
            protected $data;
            protected $header;
            protected $monthName;
            protected $year;
            protected $logoPath; // New property to hold logo path

            public function __construct(array $data, array $header, string $monthName, int $year, string $logoPath)
            {
                $this->data = $data;
                $this->header = $header;
                $this->monthName = $monthName;
                $this->year = $year;
                $this->logoPath = $logoPath;
            }

            public function array(): array
            {
                // This now returns only the data rows, as title/logo space will be handled by WithEvents
                return array_map('array_values', $this->data);
            }

            public function headings(): array
            {
                // We will prepend a title/logo row in registerEvents, so this remains just the data headers.
                return [
            ['ATTENDANCE REPORT FOR ' . strtoupper($this->monthName) . ' ' . $this->year],
            [''], // This is where the logo will be placed
            $this->header,
        ]; 
            }
            public function drawings()
            {
                if (file_exists($this->logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setDescription('Company Logo');
                    $drawing->setPath($this->logoPath);
                    $drawing->setHeight(50); // Adjust logo height
                    $drawing->setCoordinates('A2'); // Place the logo in cell A2
                    $drawing->setOffsetX(5);
                    $drawing->setOffsetY(5);

                    return [$drawing];
                }
                return [];
            }

            public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $totalColumns = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                // Row 1: Title Styling
                $sheet->mergeCells('A1:' . $highestColumn . '1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '000000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Row 2: Logo Row height
                $sheet->getRowDimension(2)->setRowHeight(60); 

                // Row 3: Column Headings Styling
                $headerRow = 3;
                $sheet->getStyle('A' . $headerRow . ':' . $highestColumn . $headerRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E88E5']], 
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ]);

                // Set a wider column for Staff Name
                $sheet->getColumnDimension('A')->setWidth(30); 
                
                // Content Styling (Starts from Row 4)
                $dataStartRow = 4;
                $totalRows = count($this->data) + 3; // Data rows + 3 header rows (Title, Logo, Headings)
                
                for ($i = $dataStartRow; $i <= $totalRows; $i++) {
                    // Staff Name Column Styling
                    $sheet->getStyle('A' . $i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    // Date Columns Styling (starting from B, index 2)
                    for ($j = 2; $j <= $totalColumns; $j++) {
                        $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j) . $i;
                        $cellValue = $sheet->getCell($cellCoordinate)->getValue();
                        
                        $styleArray = [];

                        if ($cellValue === '✔') {
                            $styleArray = [
                                'font' => ['color' => ['rgb' => '008000'], 'bold' => true],
                                //'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']], 
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ];
                        } elseif ($cellValue === '✖') {
                            $styleArray = [
                                'font' => ['color' => ['rgb' => 'FF0000'], 'bold' => true],
                                //'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEBEE']], 
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ];
                        } elseif (substr($cellValue, 0, 2) === 'L') { // Leave
                            $styleArray = [
                                'font' => ['color' => ['rgb' => 'FF9800'], 'bold' => true],
                                //'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3E0']], 
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ];
                        }
                        
                        if (!empty($styleArray)) {
                            $sheet->getStyle($cellCoordinate)->applyFromArray($styleArray);
                        }
                    }
                }
                
                // Apply thin borders to all data cells
                $sheet->getStyle('A3:' . $highestColumn . $totalRows)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ]);
            },
        ];
    }
        }, $filename);
    }
    public function exportPdf(Request $request)
    {
        $exportData = $this->getAttendanceDataForExport($request);
        $monthName = $exportData['monthName'];
        $year = $exportData['year'];
        $data = $exportData['data'];
        $header = $exportData['header'];
         $logoPath = $exportData['logoPath'];
        $filename = "Attendance_Report_{$monthName}_{$year}.pdf";

        $pdf = Pdf::loadView('superadmin.attendance_report', compact('data', 'header', 'monthName', 'year','logoPath'))
                // Set paper to A4 Landscape for the wide attendance table
                ->setPaper('a4', 'landscape'); 

        //return $pdf->download($filename);
        return $pdf->stream($filename);
    }

    public function getAttendanceDetail(Request $request)
    {
        $staffId = $request->input('staff_id');
        $date = $request->input('date');

        if (!$staffId || !$date) {
            return response()->json(['error' => 'Missing staff_id or date'], 400);
        }

        // Since your existing logic groups attendance by date, we can replicate that or query directly.
        // Querying directly is cleaner for a single record lookup:
        $attendance = Attendancemodel::where('staff_id', $staffId)
            // Check if time_in (or date column) falls on the requested date
            ->whereDate('time_in', $date)
            ->first();

        // Prepare the response
        if ($attendance) {
            return response()->json([
                'success' => true,
                // Return the full attendance object. Datetime casts in the model will handle object formatting.
                'attendance' => $attendance,
            ]);
        }

        return response()->json([
            'success' => false,
            'attendance' => null,
        ]);
    }

    private function getStaffLedgerData(Request $request): array
    {
        // --- 1. Define Period and Filters ---
        $staffId = $request->input('staff_id'); // Optional filter
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        $daysInMonth = $startDate->daysInMonth;
        
        // --- 2. Fetch Core Data ---
        // $staffQuery = Staffmodel::with(['role', 'salaryRecords' => function ($query) use ($startDate) {
        //     $query->orderBy('created_at', 'desc')->take(1); 
        // }])
        // ->when($staffId, fn($q) => $q->where('staff_id', $staffId));
         $staffQuery = Staffmodel::with(['role']) 
        ->when($staffId, fn($q) => $q->where('staff_id', $staffId));
        
        $staffList = $staffQuery->get();

        // --- 3. Fetch Attendance and Leave Data for the Month ---
        $attendanceData = Attendancemodel::whereBetween('time_in', [$startDate, $endDate])
            ->get()
            ->groupBy('staff_id');

        $leaveData = LeaveRequest::where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<', $startDate)
                            ->where('end_date', '>', $endDate);
                    });
            })
            ->get()
            ->groupBy('staff_id');

        // --- 4. Process and Structure Ledger Data ---
        $ledgerData = [];
       // $workingDays = 0; 
        
        //$totalDays = $daysInMonth; 
        
        

        foreach ($staffList as $staff) {
            $staffAttendance = $attendanceData[$staff->staff_id] ?? collect();
            $staffLeaves = $leaveData[$staff->staff_id] ?? collect();

            // Attendance Calculations
            // $presentDays = $staffAttendance->filter(fn($att) => $att->time_out !== null)->count();
            // $halfDays = $staffAttendance->where('late_status', 'Half Day')->count();
            // $lateDays = $staffAttendance->where('late_status', 'Late')->count();

            // // Leave Calculations (requires iterating through the days)
            // $leaveDays = 0;
            // $leaveMap = []; // To store which type of leave on which day

            // $currentDate = $startDate->copy();
            // while ($currentDate->lte($endDate)) {
            //     $isLeave = $staffLeaves->first(function ($leave) use ($currentDate) {
            //         return $currentDate->between(
            //             Carbon::parse($leave->start_date)->startOfDay(), 
            //             Carbon::parse($leave->end_date)->endOfDay(), 
            //             true // Inclusive
            //         );
            //     });
                
            //     if ($isLeave) {
            //         $leaveDays++;
            //         $leaveMap[$currentDate->format('Y-m-d')] = $isLeave->leave_type;
            //     }
            //     $currentDate->addDay();
            // }
            
            // $absentDays = $totalDays - $presentDays - $leaveDays;
            
           
            // $absentDays = max(0, $absentDays); 
            
            // // Salary Data
            // $salary = $staff->salaryRecords->first(); // Get the current salary record
            // $monthlySalary = $salary->base_salary ?? 0;
             $presentDays = $absentDays = $leaveDays = $lwpDays = $halfDays = $lateDays = $weekOffs = 0;

        $weekOffDays = $staff->week_off_day 
            ? array_map('trim', explode(',', $staff->week_off_day))
            : [];
        
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayName = $currentDate->format('l');

            // 1. Skip future dates
            if ($currentDate->gt(Carbon::today()->endOfDay())) {
                $currentDate->addDay();
                continue;
            }

            // 2. Check for Week Off
            if (in_array($dayName, $weekOffDays)) {
                $weekOffs++;
                $currentDate->addDay();
                continue; 
            }
            
            $isLeave = $staffLeaves->first(function ($leave) use ($currentDate) {
                return $currentDate->between(
                    Carbon::parse($leave->start_date)->startOfDay(), 
                    Carbon::parse($leave->end_date)->endOfDay(), 
                    true
                );
            });
            
            $record = $staffAttendance->first(function($att) use ($dateStr) {
                 return Carbon::parse($att->time_in)->format('Y-m-d') === $dateStr;
            });

            // 3. Check for Approved Leave
            if ($isLeave) {
                $leaveDays++;
                if ($isLeave->leave_type === 'LWP') {
                    $lwpDays++;
                }
            } 
            // 4. Check for Presence (Time In/Out exist)
            elseif ($record && $record->time_in && $record->time_out) {
                $presentDays++;
                
                // Late/Half Day calculation
                $timeIn = Carbon::parse($record->time_in);
                $timeOut = Carbon::parse($record->time_out);
                $reportingTime = $timeIn->copy()->setTime(10, 30, 0); // Assuming 10:30 AM reporting time

                if ($timeIn->gt($reportingTime)) {
                    $lateDays++;
                }
                // Check attendance record's late_status for Half Day
                if ($record->late_status === 'Half Day') {
                    $halfDays++;
                }
            } 
            // 5. Default to Absent for all other past working days
            else {
                $absentDays++;
            }
            
            $currentDate->addDay();
        }
        
        // --- Salary Calculations (consistent with your reference logic) ---
        
        // 1. Get Base Salary
       // $salaryRecord = $staff->salaryRecords->first();
       // $baseSalary = $salaryRecord->base_salary ?? 0;
       $baseSalary = $staff->salary ?? 0;
        
        // 2. Calculate Paid Working Days
        // The days used to calculate the per-day value.
        // Paid Days = Total Days - Week Offs - (Paid Leaves)
        $paidLeaves = $leaveDays - $lwpDays;
        $salaryBasisDays = $daysInMonth - $weekOffs - $paidLeaves;
        
        // Prevent division by zero, use total days if week-off logic is complex
        $divisor = max(1, $salaryBasisDays); 
        // If your company uses Days In Month as the divisor:
        // $divisor = max(1, $daysInMonth);
        
        $perDaySalary = round($baseSalary / $divisor, 2);

        // 3. Calculate Deductions (Absent + LWP are deducted)
        $deductedDays = $absentDays + $lwpDays; 
        
        $deductionAmount = $deductedDays * $perDaySalary;
        
        // 4. Final Payable Salary
        $netPayable = max(0, $baseSalary - $deductionAmount);
            
            // --- Final Ledger Entry ---
            // $ledgerData[] = [
            //     'staff_id'        => $staff->staff_id,
            //     'staff_name'      => $staff->staff_name,
            //     'role'            => $staff->role->role_name ?? 'N/A',
            //     'base_salary'     => $monthlySalary,
            //     'total_days_month' => $totalDays,
                
            //     // Attendance Summary
            //     'present_days'    => $presentDays,
            //     'leave_days'      => $leaveDays,
            //     'absent_days'     => $absentDays,
            //     'late_mark_count' => $lateDays,
            //     'half_day_count'  => $halfDays,
                
            //     // Financial Calculations (Example: Simple deduction for absent days)
            //     'per_day_salary'  => $totalDays > 0 ? round($monthlySalary / $totalDays, 2) : 0,
            //     'deduction_absent'=> $absentDays * ($totalDays > 0 ? round($monthlySalary / $totalDays, 2) : 0),
                
            //     // Final Payable Salary (Gross - Deductions)
            //     'net_payable'     => $monthlySalary - ($absentDays * ($totalDays > 0 ? round($monthlySalary / $totalDays, 2) : 0)),

            //     // Raw data for detailed view
            //     'raw_attendance'  => $staffAttendance,
            //     'raw_leaves'      => $staffLeaves,
            // ];
            $ledgerData[] = [
            'staff_id'           => $staff->staff_id,
            'staff_name'         => $staff->staff_name,
            'role'               => $staff->role->role_name ?? 'N/A',
            'base_salary'        => $baseSalary,
            'total_days_month'   => $daysInMonth,
            
            // Attendance Summary
            'present_days'       => $presentDays,
            'leave_days'         => $leaveDays,
            'absent_days'        => $absentDays,
            'lwp_days'           => $lwpDays, // Added LWP for clarity
            'week_offs'          => $weekOffs,
            'late_mark_count'    => $lateDays,
            'half_day_count'     => $halfDays,
            
            // Financial Calculations
            'per_day_salary'     => $perDaySalary,
            'deducted_days_total'=> $deductedDays,
            'deduction_absent'   => round($deductionAmount, 2),
            
            // Final Payable Salary
            'net_payable'        => round($netPayable, 2),

            // Raw data for detailed view
            'raw_attendance'     => $staffAttendance,
            'raw_leaves'         => $staffLeaves,
        ];
        }
        
        return [
            'data' => $ledgerData, 
            'monthName' => $startDate->format('F'), 
            'year' => $year,
        ];
    }
    public function getLedgerDataJson(Request $request)
    {
        $exportData = $this->getStaffLedgerData($request);
        $data = $exportData['data'];

        return DataTables::of($data)
            // Add any necessary formatting or action columns here
            ->make(true);
    }
    
    public function exportLedgerExcel(Request $request)
    {
        $exportData = $this->getStaffLedgerData($request);
        $data = $exportData['data'];
        $monthName = $exportData['monthName'];
        $year = $exportData['year'];

        $filename = "Staff_Ledger_Report_{$monthName}_{$year}.xlsx";

        // Use the StaffLedgerExport class created in step 3
        // return Excel::download(new StaffLedgerExport($data), $filename);
        return Excel::download(new class($data, $monthName, $year) implements 
        FromCollection, 
        WithHeadings, 
        ShouldAutoSize, 
        WithEvents,
        WithMapping
    {
        protected $data;
        protected $monthName;
        protected $year;
        protected $srNo = 0;

        public function __construct(array $data, string $monthName, int $year)
        {
            // Note: Data is wrapped in new Collection to ensure it's iterable by map
            $this->data = new Collection($data); 
            $this->monthName = $monthName;
            $this->year = $year;
        }

        public function collection()
        {
            // We return the raw data. Mapping is handled by the map() method.
            return $this->data;
        }

        // 2. WithMapping: Handles Sr. No., column order, and type casting
        public function map($item): array
            {
                $this->srNo++; 
                
                
                // We use isset() or a ternary check to explicitly handle null/empty strings.

                $baseSalary = isset($item['base_salary']) && is_numeric($item['base_salary']) ? (float)$item['base_salary'] : 0.0;
                $totalDaysMonth = isset($item['total_days_month']) && is_numeric($item['total_days_month']) ? (int)$item['total_days_month'] : 0;
                
                $presentDays = isset($item['present_days']) && is_numeric($item['present_days']) ? (int)$item['present_days'] : 0;
                $leaveDays = isset($item['leave_days']) && is_numeric($item['leave_days']) ? (int)$item['leave_days'] : 0;
                $absentDays = isset($item['absent_days']) && is_numeric($item['absent_days']) ? (int)$item['absent_days'] : 0;
                $lwpDays = isset($item['lwp_days']) && is_numeric($item['lwp_days']) ? (int)$item['lwp_days'] : 0;
                $weekOffs = isset($item['week_offs']) && is_numeric($item['week_offs']) ? (int)$item['week_offs'] : 0;
                $lateMarkCount = isset($item['late_mark_count']) && is_numeric($item['late_mark_count']) ? (int)$item['late_mark_count'] : 0;
                $halfDayCount = isset($item['half_day_count']) && is_numeric($item['half_day_count']) ? (int)$item['half_day_count'] : 0;

                $perDaySalary = isset($item['per_day_salary']) && is_numeric($item['per_day_salary']) ? (float)$item['per_day_salary'] : 0.0;
                $deductionAbsent = isset($item['deduction_absent']) && is_numeric($item['deduction_absent']) ? (float)$item['deduction_absent'] : 0.0;
                $netPayable = isset($item['net_payable']) && is_numeric($item['net_payable']) ? (float)$item['net_payable'] : 0.0;

                return [
                    $this->srNo, 
                    $item['staff_name'] ?? '',
                    $item['role'] ?? '',
                    $baseSalary,
                    $totalDaysMonth,
                    
                    
                    $presentDays,
                    $leaveDays,
                    $absentDays, 
                    $lwpDays, 
                    $weekOffs,
                    $lateMarkCount,
                    $halfDayCount,

                    $perDaySalary,
                    $deductionAbsent, 
                    $netPayable,
                ];
            }

        public function headings(): array
        {
            return [
                // Title Header
                ['STAFF LEDGER REPORT FOR ' . strtoupper($this->monthName) . ' ' . $this->year], 
                
                [
                    'Sr. No.', 'Staff Name', 'Role', 'Base Salary', 'Total Days', 
                    'Present Days', 'Leave Days', 'Absent Days', 'LWP Days', 
                    'Week Offs', 'Late Marks', 'Half Days', 'Per Day Salary', 
                    'Deduction Amount', 'Net Payable',
                ]
            ];
        }

        public function registerEvents(): array
        {
            return [
                AfterSheet::class => function(AfterSheet $event) {
                    $sheet = $event->sheet;
                    $lastColumn = $sheet->getHighestColumn();
                    
                    // The headers now start from column A and go to the end
                    $sheet->mergeCells('A1:' . $lastColumn . '1');
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 16],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    // Style the Column Headers (Row 2)
                    $sheet->getStyle('A2:' . $lastColumn . '2')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']], 
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF007BFF']], 
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                    ]);

                    // Add borders to the data rows
                    $sheet->getStyle('A3:' . $lastColumn . $sheet->getHighestRow())
                        ->getBorders()->applyFromArray(['allBorders' => ['borderStyle' => Border::BORDER_THIN]]);
                },
            ];
        }

    }, $filename);
    }
    public function exportLedgerPdf(Request $request)
    {
        // 1. Get the aggregated data
        $exportData = $this->getStaffLedgerData($request);
        
        $monthName = $exportData['monthName'];
        $year = $exportData['year'];
        $data = $exportData['data']; // This is your ledger data
        
        $filename = "Staff_Ledger_Report_{$monthName}_{$year}.pdf";

        // 2. Load a new Blade view specifically for the ledger
        $pdf = PDF::loadView('superadmin.staff_ledger_report', compact('data', 'monthName', 'year'))
            // Ledger reports often benefit from A4 landscape
            ->setPaper('a4', 'landscape'); 
            
        // 3. Stream the PDF to the browser
        return $pdf->stream($filename);
    }
}
