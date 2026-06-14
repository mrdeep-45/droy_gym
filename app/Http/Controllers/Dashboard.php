<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendancemodel;
use App\Models\LeaveRequest;
use App\Models\ForgotOutRequestmodel;
use App\Models\ForgotOutRequestHistory;
use App\Models\Staffmodel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Dashboard extends Controller
{
    //
    /*
    public function getStaffAttendanceSummary(Request $request)
{
    if (session('login_type') !== 'Staff') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $staffId = session('staff_id');

    // Default to current month if not provided
    $monthParam = $request->input('month', now()->format('Y-m'));

    try {
        $monthStart = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        $monthEnd = Carbon::createFromFormat('Y-m', $monthParam)->endOfMonth();
    } catch (\Exception $e) {
        return response()->json(['error' => 'Invalid month format'], 400);
    }

    $staff = DB::table('mst_staff')->where('staff_id', $staffId)->first();
    $weekOffDay = $staff->week_off_day;

    $dates = [];
    $period = new \DatePeriod($monthStart, new \DateInterval('P1D'), $monthEnd->copy()->addDay());
    foreach ($period as $date) {
        $dates[] = $date->format('Y-m-d');
    }

    $attendanceRecords = DB::table('attendances')
        ->where('staff_id', $staffId)
        ->whereBetween('created_at', [$monthStart, $monthEnd])
        ->get()
        ->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('Y-m-d');
        });

    $stats = [
        'present' => 0,
        'late' => 0,
        'absent' => 0,
        'week_off' => 0,
    ];

    foreach ($dates as $date) {
        $day = Carbon::parse($date)->format('l');

        if ($day === $weekOffDay) {
            $stats['week_off']++;
            continue;
        }

        if (isset($attendanceRecords[$date])) {
            $stats['present']++;
            $record = $attendanceRecords[$date][0];
            $timeIn = Carbon::parse($record->time_in)->format('H:i:s');
            if ($timeIn > '10:30:00') {
                $stats['late']++;
            }
        } else {
            $stats['absent']++;
        }
    }

    return response()->json($stats);
}
*/
 public function dashboard()
    {
        $page_title = 'Staff Dashboard';
        $page_name = 'Staff Dashboard';
        return view('staff/dashboard', compact('page_title', 'page_name'));
    }
public function getStaffAttendanceSummary(Request $request)
{
    if (session('login_type') !== 'Staff') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $staffId = session('staff_id');
    $month = $request->input('month', now()->format('Y-m'));

    try {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        return response()->json(['error' => 'Invalid month format'], 400);
    }

     $staff = Staffmodel::where('staff_id', $staffId)->first();
    if (!$staff) {
        return response()->json(['error' => 'Staff not found'], 404);
    }

    // Parse staff's configured week off days
    $weekOffDays = $staff->week_off_day 
        ? array_map('trim', explode(',', $staff->week_off_day))
        : [];

    $attendance = Attendancemodel::where('staff_id', $staffId)
        ->whereBetween('time_in', [$monthStart, $monthEnd])
        ->get()
        ->groupBy(function ($item) {
            return Carbon::parse($item->time_in)->format('Y-m-d');
        });

   // $reportingTime = Carbon::createFromTime(10, 30, 0);

    $present = $absent = $late = $halfDay = $leave = 0;
    $presentDates = $absentDates = $lateDates = $halfDayDates = $weekOffDates = $leaveDates = [];

    $daysInMonth = $monthStart->daysInMonth;
 $today = Carbon::today();
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $date = Carbon::createFromFormat('Y-m-d', $monthStart->format('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT));
        $dateStr = $date->format('Y-m-d');
        $dayName = $date->format('l');

        // Check if it's a configured week off
        if (in_array($dayName, $weekOffDays)) {
            $weekOffDates[] = $dateStr;
            continue; // Skip further processing for week offs
        }

           if ($date->gte($today)) {
            continue;
        }

        $record = isset($attendance[$dateStr]) ? $attendance[$dateStr]->first() : null;
        /*
        if ($record && $record->time_in && $record->time_out) {
            //  Full presence
            $present++;
            $presentDates[] = $dateStr;

            $timeIn = Carbon::parse($record->time_in);
            $timeOut = Carbon::parse($record->time_out);

            if ($timeIn->gt($reportingTime)) {
                $late++;
                $lateDates[] = $dateStr;
            }

            if ($timeOut->diffInHours($timeIn) < 4) {
                $halfDay++;
                $halfDayDates[] = $dateStr;
            }
        } else {
            //  Absent logic
            $absent++;
            $absentDates[] = $dateStr;
        }
        */
        if ($record && $record->time_in && $record->time_out && $date->lt(Carbon::today())) {
            //  Count only if it's not today if we remove date then show today
           $present++;
            $presentDates[] = $dateStr;

            $timeIn = Carbon::parse($record->time_in);
            $timeOut = Carbon::parse($record->time_out);

            $reportingTime = Carbon::parse($record->time_in)->copy()->setTime(10, 30, 0);

            if ($timeIn->gt($reportingTime)) {
                $late++;
                $lateDates[] = $dateStr;
            }

            if ($timeOut->diffInHours($timeIn) < 4) {
                $halfDay++;
                $halfDayDates[] = $dateStr;
            }
        }
        elseif ($record && is_null($record->time_in) && is_null($record->time_out) && $date->lt(Carbon::today())) {
    

        $absent++;
        $absentDates[] = $dateStr;
    
}
elseif ($date->lt(Carbon::today())) {
    

    // Count it as absent even if on leave
    $absent++;
    $absentDates[] = $dateStr;
}




    }

    return response()->json([
        'present' => $present,
        'absent' => $absent,
        'late' => $late,
        'half_day' => $halfDay,
        'leave' => $leave, //  add this
        'week_off' =>count($weekOffDates), // We are not calculating it anymore, just placeholder
        'dates' => [
            'present' => $presentDates,
            'absent' => $absentDates,
            'late' => $lateDates,
            'half_day' => $halfDayDates,
            'leave' => $leaveDates, // add this
            'week_off' =>  $weekOffDates, // placeholder
        ]
    ]);
}
/*
public function viewDetails(Request $request)
{
    if (session('login_type') !== 'Staff') {
        return abort(403, 'Unauthorized');
    }

    $staffId = session('staff_id');
    $month = $request->input('month', now()->format('Y-m'));
    $type = $request->input('type'); // present, absent, late, week-offs

    try {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        return abort(400, 'Invalid month');
    }

    $staff = Staffmodel::find($staffId);
    $weekOffDay = $staff->week_off_day;

    $attendance = Attendancemodel::where('staff_id', $staffId)
        ->whereBetween('time_in', [$monthStart, $monthEnd])
        ->get()
        ->groupBy(function ($item) {
            return Carbon::parse($item->time_in)->format('Y-m-d');
        });

    $reportingTime = Carbon::createFromTime(10, 30, 0);
    $data = [];

    for ($i = 1; $i <= $monthStart->daysInMonth; $i++) {
        $date = Carbon::createFromFormat('Y-m-d', $monthStart->format('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT));
        $dateStr = $date->format('Y-m-d');
        $dayName = $date->format('l');

        $status = 'Absent';

        if (isset($attendance[$dateStr])) {
            $record = $attendance[$dateStr]->first();

            if ($record->time_in && $record->time_out) {
                $status = 'Present';

                if (Carbon::parse($record->time_in)->gt($reportingTime)) {
                    $status = 'Late';
                }
            } elseif ($record->time_in && !$record->time_out) {
                $status = 'Absent';
            }
        } elseif ($dayName === $weekOffDay) {
            $status = 'Week Off';
        }

        if (
            ($type === 'present' && $status === 'Present') ||
            ($type === 'absent' && $status === 'Absent') ||
            ($type === 'late' && $status === 'Late') ||
            ($type === 'week-offs' && $status === 'Week Off')
        ) {
            $data[] = [
    'date' => $dateStr,
    'day' => $dayName,
    'status' => $status,
    'time_in' => isset($record) && $record->time_in ? Carbon::parse($record->time_in)->format('H:i:s') : '--:--',
    'time_out' => isset($record) && $record->time_out ? Carbon::parse($record->time_out)->format('H:i:s') : '--:--',
    

];

        }
    }
$page_title = ucfirst($type) . ' Days - ' . Carbon::createFromFormat('Y-m', $month)->format('F Y');
return view('staff.details', compact('data', 'type', 'month', 'page_title'));

    //return view('staff.details', compact('data', 'type', 'month'));
}
*/
public function viewDetails(Request $request)
{
    if (session('login_type') !== 'Staff') {
        return abort(403, 'Unauthorized');
    }

    $staffId = session('staff_id');
    $month = $request->input('month', now()->format('Y-m'));
    $type = $request->input('type'); // present, absent, late, week-offs

    try {
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        return abort(400, 'Invalid month');
    }

    $staff = Staffmodel::find($staffId);
    //$weekOffDay = $staff->week_off_day;
    $weekOffDaysArray = $staff->week_off_day 
    ? array_map('trim', explode(',', $staff->week_off_day))
    : [];

    $attendance = Attendancemodel::where('staff_id', $staffId)
        ->whereBetween('time_in', [$monthStart, $monthEnd])
        ->get()
        ->groupBy(function ($item) {
            return Carbon::parse($item->time_in)->format('Y-m-d');
        });

    //$reportingTime = Carbon::createFromTime(10, 30, 0);
   

    $data = [];
    $today = Carbon::today();

    for ($i = 1; $i <= $monthStart->daysInMonth; $i++) {
        $date = Carbon::createFromFormat('Y-m-d', $monthStart->format('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT));
        $dateStr = $date->format('Y-m-d');
        $dayName = $date->format('l');

        //  Skip today and future dates
        // if ($date->isToday() || $date->gt(Carbon::today())) {
        //     continue;
        // }
        
         if ($date->gte($today)) {
            continue;
        }
        

        $status = 'Absent';
        $record = isset($attendance[$dateStr]) ? $attendance[$dateStr]->first() : null;

        // if (in_array($dayName, $weekOffDaysArray)) {
        //     // ✅ FIX 2: Check for week off using the array.
        //     $status = 'Week Off';
        // } 

        if ($record) {
            if ($record->time_in && $record->time_out ) {
                //$status = 'Present';

                $reportingTime = Carbon::parse($record->time_in)->copy()->setTime(10, 30, 0);
               // $reportingTime = Carbon::createFromTime(10, 30, 0);

                if (Carbon::parse($record->time_in)->gt($reportingTime)) {
                    $status = 'Late';

                }else {
            $status = 'Present';
        }
            } elseif ($record->time_in && !$record->time_out) {
                $status = 'Absent';
            }
             elseif (is_null($record->time_in) && is_null($record->time_out)) {
                 $status = 'Absent'; // New: completely blank attendance
            } elseif($record->status=='leave' && $record->date==$dateStr && $record->time_in){
                    $status = 'Absent';
                } 

        // } elseif ($dayName === $weekOffDay) {
        //     $status = 'Week Off';
        // }
        } elseif (in_array($dayName, $weekOffDaysArray)) {
                $status = 'Week Off';
            }

        if (
            ($type === 'present' && $status === 'Present') ||
            ($type === 'absent' && $status === 'Absent') ||
            ($type === 'late' && $status === 'Late') ||
            ($type === 'week_off' && $status === 'Week Off')
        ) {
           // $isForgotTimeOut = $record && $record->time_in && !$record->time_out;
            $isForgotTimeOut = $record && $record->time_in && !$record->time_out && $record->status !== 'leave';


            $existingRequest = null;
            $latestHistory = null;
             $historyRecords = [];
           // $status = 'Absent';

           
            if ($isForgotTimeOut) {
                $existingRequest = ForgotOutRequestmodel::where('staff_id', $staffId)
                    ->where('date', $dateStr)
                    ->latest()
                    ->first();

             

if ($existingRequest) {
    $historyRecords = ForgotOutRequestHistory::where('request_id', $existingRequest->id)
    ->whereIn('status', ['approved', 'rejected']) // Only admin responses
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($record) {
            return [
                'status' => $record->status,
                'remark' => $record->remark,
                'created_at' => $record->created_at->format('d-m-Y H:i'),
            ];
        })
        ->toArray();
}

            }
         
           $data[] = [
    'date' => $date->format('d-m-Y'),
    'day' => $dayName,
    'attendance_status' => $status,
    
    'time_in' => $record && $record->time_in ? Carbon::parse($record->time_in)->format('H:i:s') : '--:--',
    'time_out' => $record && $record->time_out ? Carbon::parse($record->time_out)->format('H:i:s') : '--:--',
    'forgot_time_out' => $isForgotTimeOut ?? false,
    'request_status' => $existingRequest->status ?? null,
    'remark' => $latestHistory->remark ?? null, // you can keep this or remove
    'request_id' => $existingRequest->id ?? null,
    'history' => $historyRecords ?? [], //  full history here
];

        }
    }

    $page_title = ucfirst($type) . ' Days - ' . Carbon::createFromFormat('Y-m', $month)->format('F Y');

    return view('staff.details', compact('data', 'type', 'month', 'page_title'));
}
/*
public function submitForgotTimeout(Request $request)
{
    if (session('login_type') !== 'Staff') {
        return abort(403, 'Unauthorized');
    }

    $request->validate([
        'date' => 'required|date_format:d-m-Y',
        'description' => 'nullable|string|max:500'
    ]);

    $staffId = session('staff_id');
    $date = Carbon::createFromFormat('d-m-Y', $request->input('date'))->format('Y-m-d');

    $exists = ForgotOutRequestmodel::where('staff_id', $staffId)
        ->where('date', $date)
        ->where('status', 'pending')
        ->exists();

    if ($exists) {
        return back()->with('error', 'Request already submitted for this date.');
    }
    $attendance = Attendancemodel::where('staff_id', $staffId)
    ->whereDate('time_in', $date)
    ->whereNull('time_out') // specifically for missed timeout
    ->first();

    ForgotOutRequestmodel::create([
        'staff_id' => $staffId,
        'attendance_id' => $attendance->id ?? null,
        'date' => $date,
        'description' => $request->input('description'),
        'status' => 'pending',
    ]);

    return back()->with('success', 'Your request has been submitted for admin review.');
}
*/
public function submitForgotTimeout(Request $request)
{
    if (session('login_type') !== 'Staff') {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    $request->validate([
        'date' => 'required|date_format:d-m-Y',
        'description' => 'required|string|max:500'
    ]);

    $staffId = session('staff_id');
    $date = Carbon::createFromFormat('d-m-Y', $request->input('date'))->format('Y-m-d');

    // Get existing request if any
    $existing = ForgotOutRequestmodel::where('staff_id', $staffId)
        ->where('date', $date)
        ->latest()
        ->first();

    // Block if pending or approved already
    if ($existing && in_array($existing->status, ['pending', 'approved'])) {
        return response()->json([
            'success' => false,
            'message' => 'Request already submitted and under review or approved.'
        ], 409);
    }

    // Get attendance (optional foreign key)
    $attendance = Attendancemodel::where('staff_id', $staffId)
        ->whereDate('time_in', $date)
        ->whereNull('time_out')
        ->first();

    if ($existing && $existing->status === 'rejected') {
        // Re-request: update same record
        $existing->update([
            'status' => 'pending',
            'description' => $request->input('description'),
        ]);

        // Log new history
        ForgotOutRequestHistory::create([
            'request_id' => $existing->id,
            'status' => 'resubmitted',
            'remark' => $request->input('description'), // or "Re-requested by staff"
        ]);
    } else {
        // New request
        $newRequest = ForgotOutRequestmodel::create([
            'staff_id' => $staffId,
            'attendance_id' => $attendance->id ?? null,
            'date' => $date,
            'description' => $request->input('description'),
            'status' => 'pending',
        ]);

        ForgotOutRequestHistory::create([
            'request_id' => $newRequest->id,
            'status' => 'submitted',
            'remark' => $request->input('description'),
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Your request has been submitted for admin review.'
    ]);
}










}
