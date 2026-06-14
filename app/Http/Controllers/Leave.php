<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Staffmodel;
use Carbon\Carbon;
use App\Models\LeaveRequestHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class Leave extends Controller
{
    //
    /*
     public function index()
    {
        $page_title = 'Leave';
        $page_name = 'Leave';
        $staffId = session('staff_id');
    $user = Staffmodel::find($staffId);

    if (!$user) {
        abort(403, 'Staff not found.');
    }

      // Get current year leave balances for this staff
    $year = date('Y');
    $leaveBalance = \DB::table('leave_balances')
        ->where('staff_id', $staffId)
        ->where('year', $year)
        ->first();

    if (!$leaveBalance) {
        // if no record, default all leave balances to 0
        $leaveBalance = (object)[
            'CL' => 0,
            'PL' => 0,
            'SL' => 0,
            'LWP' => 0,
        ];
    }

    return view('staff/leave', compact('page_title', 'page_name'))
        ->with([
            'is_probation_completed' => $user->is_probation_completed,
            'is_in_notice_period' => $user->is_in_notice_period,
            'probation_months' => $user->probation_period_months,
        'hire_date' => $user->hire_date,
        'leaveBalance' => $leaveBalance,
        ]);
    }
    */

    public function index()
{
    $page_title = 'Leave';
    $page_name = 'Leave';
    $staffId = session('staff_id');

    $user = Staffmodel::find($staffId);

    if (!$user) {
        abort(403, 'Staff not found.');
    }

    $year = date('Y');
    $rawBalance = \DB::table('leave_balances')
        ->where('staff_id', $staffId)
        ->where('year', $year)
        ->first();

    $leaveBalance = (object)[
        'CL' => 0,
        'PL' => 0,
        'SL' => 0,
        'LWP' => 0,
    ];

    if ($rawBalance) {
        foreach (['CL', 'PL', 'SL', 'LWP'] as $type) {
            $allocField = strtolower($type) . '_allocated';
            $usedField = strtolower($type) . '_used';

            $leaveBalance->$type = max(0, $rawBalance->$allocField - $rawBalance->$usedField);
        }
    }
    //for card view 
     $leaveBalance12 = LeaveBalance::where('staff_id', $staffId)
        ->where('year', $year)
        ->first();

    // Default to 0s if no balance found
    $leaveStats = [];
    foreach (['CL', 'PL', 'SL', 'LWP'] as $type12) {
        $allocField12 = strtolower($type12) . '_allocated';
        $usedField12 = strtolower($type12) . '_used';

        $allocated12 = $leaveBalance12->$allocField12 ?? 0;
        $used12 = $leaveBalance12->$usedField12 ?? 0;

        $leaveStats[] = [
            'type' => $type12,
            'available' => max(0, $allocated12 - $used12),
            'used' => $used12,
        ];
    }

    return view('staff/leave', compact('page_title', 'page_name','leaveStats'))
        ->with([
            'is_probation_completed' => $user->is_probation_completed,
            'is_in_notice_period' => $user->is_in_notice_period,
            'probation_months' => $user->probation_period_months,
            'hire_date' => $user->hire_date,
            'leaveBalance' => $leaveBalance,
        ]);
}


    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type' => 'required|in:CL,PL,SL,LWP',
            'reason' => 'required|string|max:255',
            'leave_day' => 'required|in:full,half',
            'contact_info' => 'required|string|max:20',
            'total_days' => 'required|numeric|min:0.5|max:30',
            'notes' => 'nullable|string|max:500',
        ];

        $customAttributes = [
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'leave_type' => 'Leave Type',
            'reason' => 'Reason',
            'leave_day' => 'Leave Day',
            'contact_info' => 'Contact Info',
            'total_days' => 'Total Days',
        ];

        $validator = Validator::make($request->all(), $rules, [], $customAttributes);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $firstField = array_key_first($errors);
            $firstMessage = $errors[$firstField][0];

            return response()->json([
                'success' => false,
                'field' => $firstField,
                'message' => $firstMessage,
            ], 422);
        }

        

        // Duplicate check: same date range by same user
        $exists = LeaveRequest::where('staff_id', auth()->id())
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            })
            ->where('status', '!=', 'rejected')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'field' => 'start_date',
                'message' => 'You have already applied leave for this date range.',
            ], 422);
        }


       
        //$validated['staff_id'] = auth()->id();
        

        //add condition
 $validated = $validator->validated();
        $staffId = session('staff_id');
        $leaveType = $validated['leave_type'];
        $totalDays = $validated['total_days'];
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $leaveDay = $validated['leave_day'];
        $today = Carbon::today();
        // $staffId = $validated['staff_id'];
       // $leaveType = $validated['leave_type']; // CL, PL, SL, LWP
       // $totalDays = $validated['total_days'];
        $year = now()->year;

         $user = Staffmodel::find($staffId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found.',
            ], 422);
        }

        // Leave Rules

        // CL only post-probation
        if ($leaveType == 'CL' && !$user->is_probation_completed) {
            return response()->json([
                'success' => false,
                'field' => 'leave_type',
                'message' => 'Casual Leave is allowed only after probation period.',
            ], 422);
        }

        // PL requires 10 days prior notice
        /*if ($leaveType == 'PL' && $startDate->diffInDays($today, false) < 10) {
            return response()->json([
                'success' => false,
                'field' => 'start_date',
                'message' => 'Privilege Leave must be applied at least 10 days in advance.',
            ], 422);
        }
        */
        if ($leaveType == 'PL' && $today->diffInDays($startDate) < 10) {
            return response()->json([
                'success' => false,
                'field' => 'start_date',
                'message' => 'Privilege Leave must be applied at least 10 days in advance.',
            ], 422);
        }


        // No paid leave during notice period
        if ($user->is_in_notice_period && in_array($leaveType, ['CL', 'PL', 'SL'])) {
            return response()->json([
                'success' => false,
                'field' => 'leave_type',
                'message' => 'No paid leave is allowed during notice period.',
            ], 422);
        }

        // Disallow full day leave today
        if ($startDate->isSameDay($today) && $leaveDay === 'full') {
            return response()->json([
                'success' => false,
                'field' => 'leave_day',
                'message' => 'Full day leave is not allowed for today. Please apply for half-day leave.',
            ], 422);
        }

          // Get leave balance for current year
        $leaveBalance = LeaveBalance::where('staff_id', $staffId)
            ->where('year', $year)
            ->first();

        if (!$leaveBalance) {
            return response()->json([
                'success' => false,
                'field' => 'leave_type',
                'message' => 'Leave balance not initialized. Contact HR.',
            ], 422);
        }

         // Check balance
         /* old logic cl,pl,sl,lwp
        $availableDays = $leaveBalance->$leaveType; 

        if ($availableDays < $totalDays) {
            return response()->json([
                'success' => false,
                'field' => 'total_days',
                'message' => "Insufficient $leaveType balance. You have only $availableDays day(s) left.",
            ], 422);
        }
        */
         $leaveKey = strtolower($leaveType); // Ensure case matches DB column names
$allocated = $leaveBalance->{$leaveKey . '_allocated'};
$used = $leaveBalance->{$leaveKey . '_used'};
$availableDays = $allocated - $used;

if ($availableDays < $totalDays) {
    return response()->json([
        'success' => false,
        'field' => 'total_days',
        'message' => "Insufficient $leaveType balance. You have only $availableDays day(s) left.",
    ], 422);
}


/*
         // Check leave type balance
        // CL max 6 days/year, no carry forward check here, just balance
        // PL max 12 days/year, balance including carry forward should be managed in DB
        if ($leaveType == 'CL' && $leaveBalance->CL < $totalDays) {
            return response()->json([
                'success' => false,
                'field' => 'total_days',
                'message' => "Insufficient Casual Leave (CL) balance. You have only {$leaveBalance->CL} day(s) left.",
            ], 422);
        }
        if ($leaveType == 'PL' && $leaveBalance->PL < $totalDays) {
            return response()->json([
                'success' => false,
                'field' => 'total_days',
                'message' => "Insufficient Privilege Leave (PL) balance. You have only {$leaveBalance->PL} day(s) left.",
            ], 422);
        }

        // Optional: Annual maximum limit enforcement
if ($leaveType == 'CL' && $totalDays + $used > 6) {
    return response()->json([
        'success' => false,
        'field' => 'total_days',
        'message' => 'Casual Leave (CL) cannot exceed 6 days in a year.',
    ], 422);
}

if ($leaveType == 'PL' && $totalDays + $used > 12) {
    return response()->json([
        'success' => false,
        'field' => 'total_days',
        'message' => 'Privilege Leave (PL) cannot exceed 12 days in a year.',
    ], 422);
}
*/
        
        $validated['staff_id'] = session('staff_id'); //  Works with your current session setup

        $validated['status'] = 'Pending';
        $validated['created_at'] = now();

        LeaveRequest::create($validated);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while submitting leave.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    
/*
public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type' => 'required|in:CL,PL,Optional',
            'reason' => 'required|string|max:255',
            'leave_day' => 'required|in:full,half',
            'contact_info' => 'required|string|max:20',
            'total_days' => 'required|numeric|min:0.5|max:30',
            'notes' => 'nullable|string|max:500',
        ];

        $customAttributes = [
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'leave_type' => 'Leave Type',
            'reason' => 'Reason',
            'leave_day' => 'Leave Day',
            'contact_info' => 'Contact Info',
            'total_days' => 'Total Days',
        ];

        $validator = Validator::make($request->all(), $rules, [], $customAttributes);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $firstField = array_key_first($errors);
            $firstMessage = $errors[$firstField][0];

            return response()->json([
                'success' => false,
                'field' => $firstField,
                'message' => $firstMessage,
            ], 422);
        }

        $validated = $validator->validated();

        $validated['staff_id'] = session('staff_id');
        $validated['status'] = 'submitted';
        $validated['created_at'] = now();

        // Prevent duplicate leaves
        $exists = LeaveRequest::where('staff_id', $validated['staff_id'])
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            })
            ->where('status', '!=', 'rejected')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'field' => 'start_date',
                'message' => 'You have already applied leave for this date range.',
            ], 422);
        }

        $user = Staffmodel::find($validated['staff_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found.',
            ], 404);
        }

        $leaveType = $validated['leave_type'];
        $totalDays = $validated['total_days'];
        $year = now()->year;

        // Check leave balance
        $balance = LeaveBalance::where('staff_id', $validated['staff_id'])
            ->where('leave_type', $leaveType)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            return response()->json([
                'success' => false,
                'field' => 'leave_type',
                'message' => 'Leave balance not initialized. Contact HR.',
            ], 422);
        }

        if ($balance->remaining < $totalDays) {
            return response()->json([
                'success' => false,
                'field' => 'total_days',
                'message' => 'Insufficient leave balance. You have only ' . $balance->remaining . ' days left.',
            ], 422);
        }

        $start = Carbon::parse($validated['start_date']);
        $today = now();
        $daysNotice = $start->diffInDays($today);

        switch ($leaveType) {
            case 'CL':
                if (strtolower($user->employment_category) === 'probationary') {
                    return response()->json([
                        'success' => false,
                        'field' => 'leave_type',
                        'message' => 'Casual Leave is allowed only after probation period.',
                    ], 422);
                }
                break;

            case 'PL':
                if ($daysNotice < 10) {
                    return response()->json([
                        'success' => false,
                        'field' => 'start_date',
                        'message' => 'Privilege Leave must be applied at least 10 days in advance.',
                    ], 422);
                }
                break;

            case 'Optional':
                // Add festival validation here if needed
                break;
        }

        // No paid leave during notice period (if such field exists and is boolean)
        if (!empty($user->is_in_notice_period) && $user->is_in_notice_period == 1) {
            return response()->json([
                'success' => false,
                'field' => 'leave_type',
                'message' => 'No paid leave is allowed during notice period.',
            ], 422);
        }

        LeaveRequest::create($validated);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while submitting leave.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

*/
public function list(Request $request)
{
    $staffId = session('staff_id');

    $query = LeaveRequest::where('staff_id', $staffId)
        ->orderByDesc('created_at');

    return DataTables::of($query)
        ->addIndexColumn()
       ->addColumn('leave_duration', function ($row) {
    $start = Carbon::parse($row->start_date)->format('d-m-Y');
    $end = Carbon::parse($row->end_date)->format('d-m-Y');

    return $start === $end ? $start : "$start to $end";
})

        ->addColumn('reason', function ($row) {
            return $row->reason ?? '-'; // 
        })
        ->addColumn('notes', function ($row) {
            return $row->notes ?? '-'; // 
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
        ->editColumn('created_at', function ($row) {
            return $row->created_at->format('d-m-Y h:i A');
        })
        ->addColumn('action', function ($row) {
            return '<button class="btn btn-sm btn-danger">Delete</button>';
        })
        ->rawColumns(['action','status'])
        ->make(true);
}




}
