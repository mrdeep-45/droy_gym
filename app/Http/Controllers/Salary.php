<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staffmodel;
use App\Models\Salarymodel;
use App\Models\Attendancemodel;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class Salary extends Controller
{
    //
      public function attendanceview()
        {
            $page_title = 'Attendance View';
            $page_name = 'Attendance View';
            $staffList = Staffmodel::all();
            
            return view('superadmin.attendance_view', compact('page_title', 'page_name', 'staffList'));
        }
    //     public function getStaffSalary(Request $request)
    // {
    //     $staff = Staffmodel::find($request->staff_id);
    //     return response()->json([
    //         'salary' => $staff->salary ?? 0,
    //         'staff_name' => $staff->staff_name,
    //         'staff_email' => $staff->staff_email,
    //         'hire_date' => $staff->hire_date,
    //         'phone' => $staff->phone,
    //         'address' => $staff->address,
    //         'position' => $staff->position ?? 'N/A',
    //         'department' => $staff->department ?? 'N/A',
    //     ]);
    // }

    public function getStaffSalary(Request $request)
    {
        // The request should ideally include month and year to calculate the preview
        $staffId = $request->staff_id;
        $month = $request->month ?? now()->month; // Fallback to current month if not provided
        $year = $request->year ?? now()->year;   // Fallback to current year if not provided

        $staff = Staffmodel::find($staffId);

        if (!$staff) {
            return response()->json(['error' => 'Staff not found'], 404);
        }

        $baseSalary = $staff->salary ?? 0;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $daysInMonth = $startDate->daysInMonth;

        // --- Start Attendance Calculation (Similar to getSalaryData/calculate) ---
        $attendances = Attendancemodel::where('staff_id', $staffId)
            ->whereBetween('time_in', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->time_in)->format('Y-m-d'));

        $present = $absent = $leave = $lwp = $halfDay = $late = $weekOffs = 0;

        $weekOffDays = $staff->week_off_day 
            ? array_map('trim', explode(',', $staff->week_off_day))
            : [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::create($year, $month, $i);
            $dateStr = $date->format('Y-m-d');
            $dayName = $date->format('l');

            $record = $attendances[$dateStr][0] ?? null;

            if (in_array($dayName, $weekOffDays)) {
                $weekOffs++;
            }
            
            // This is complex, simplified logic for preview:
            // Check if there is a record (present or leave) or if the day has passed without a record (absent)
            if ($record && $record->time_in && $record->time_out && $date->lt(Carbon::today())) {
                $present++;
            } elseif ($record && $record->status === 'leave') {
                $leave++;
                if (isset($record->leave_type) && $record->leave_type === 'LWP') {
                    $lwp++;
                }
            } elseif ($date->lt(Carbon::today()) && !in_array($dayName, $weekOffDays)) {
                $absent++;
            }
        }
        // --- End Attendance Calculation ---
        
        // --- Start Salary Calculation ---
        $workingDays = $daysInMonth - $weekOffs - max(0, $leave - $lwp);
        $workingDays = max($workingDays, 1);
        $perDaySalary = $staff->salary / $workingDays;
        
        $totalDeductions = ($absent + $lwp) * $perDaySalary;
        $finalSalary = max(0, $baseSalary - $totalDeductions);
        $presentDays = $workingDays - $absent;
        // --- End Salary Calculation ---


        // Return rich data structure
        return response()->json([
            'staff_name' => $staff->staff_name,
            'staff_email' => $staff->staff_email,
            'hire_date' => $staff->hire_date,
            'phone' => $staff->phone,
            'position' => $staff->position ?? 'N/A',
            'department' => $staff->department ?? 'N/A',
            // Salary and Attendance Data for the Preview
            'total_working_days' => $workingDays,
            'base_salary' => $baseSalary,
            'present_days' => $presentDays,
            'absent_days' => $absent,
            'leave_days' => $leave,
            'lwp_days' => $lwp,
            'week_offs' => $weekOffs,
            'daily_salary' => round($perDaySalary, 2),
            'deductions' => round($totalDeductions, 2),
            'final_salary' => round($finalSalary, 2),
            'month' => $month, // Pass back month and year
            'year' => $year,
        ]);
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:mst_staff,staff_id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        $staffId = $request->staff_id;
        $month = $request->month;
        $year = $request->year;

        $staff = Staffmodel::find($staffId);
        $baseSalary = $staff->salary ?? 0;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $daysInMonth = $startDate->daysInMonth;
        $perDaySalary = $baseSalary / $daysInMonth;

        $attendances = Attendancemodel::where('staff_id', $staffId)
            ->whereBetween('time_in', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->time_in)->format('Y-m-d');
            });

        $present = $absent = $late = $halfDay = $leave = $lwp = 0;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::createFromFormat('Y-m-d', $startDate->format('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT));
            $dateStr = $date->format('Y-m-d');

            $record = isset($attendances[$dateStr]) ? $attendances[$dateStr]->first() : null;

            if ($record && $record->time_in && $record->time_out) {
                $present++;
                $timeIn = Carbon::parse($record->time_in);
                $reportingTime = $timeIn->copy()->setTime(10, 30, 0);

                if ($timeIn->gt($reportingTime)) {
                    $late++;
                }

                if ($record->time_out && $timeIn->diffInHours(Carbon::parse($record->time_out)) < 4) {
                    $halfDay++;
                }
            } elseif ($record && $record->status === 'leave') {
                $leave++;
                if ($record->leave_type == 'LWP') {
                    $lwp++;
                }
            } else {
                $absent++;
            }
        }

        $deductions = ($late * 0.5 + $absent + $lwp) * $perDaySalary;
        $finalSalary = $baseSalary - $deductions;

            // Store in DB
   
    $salary = Salarymodel::updateOrCreate([
    'staff_id' => $staffId,
    'month' => $month,
    'year' => $year
    ], [
        'base_salary' => $baseSalary,
        'deductions' => $deductions,
        'final_salary' => $finalSalary,
        'late_days' => $late,
        'absent_days' => $absent,
        'leave_days' => $leave,
        'lwp_days' => $lwp,
        'half_days' => $halfDay,
        'present_days' => $present,
        'week_offs' => $daysInMonth - ($present + $absent + $leave), // optional logic
    ]);


        return response()->json([
        'message' => 'Salary stored successfully.',
        'salary_slip' => [
            'staff_name' => $staff->staff_name,
            'month' => $month,
            'year' => $year,
            'base_salary' => round($baseSalary, 2),
            'deductions' => round($deductions, 2),
            'final_salary' => round($finalSalary, 2),
            'late_days' => $late,
            'absent_days' => $absent,
            'leave_days' => $leave,
            'lwp_days' => $lwp,
            'half_days' => $halfDay,
        ]
    ]);
    }

    public function getSalaryData(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;
        $staffId = $request->staff_id;

        $query = Staffmodel::query();
        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $staffList = $query->get();
        $results = [];

        foreach ($staffList as $staff) {
            $roleName = $staff->role->role_name ?? 'N/A'; 
    //$row = [
    // 'staff_name' => $staff->staff_name . ' (' . ucfirst($roleName) . ')'
    //];
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $daysInMonth = $startDate->daysInMonth;
            $weekOffs = 0;

            $paidSlip = Salarymodel::where('staff_id', $staff->staff_id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $salaryStatus = $paidSlip ? 'Paid' : 'Pending';
        $calculation = $this->calculateStaffSalaryDetails($staff, $month, $year);

            $attendance = Attendancemodel::where('staff_id', $staff->staff_id)
                ->whereBetween('time_in', [$startDate, $endDate])
                ->get()
                ->groupBy(fn($item) => Carbon::parse($item->time_in)->format('Y-m-d'));

            $present = $absent = $leave = $lwp = $halfDay = $late = 0;
            // Process week off days
        $weekOffDays = $staff->week_off_day 
        ? array_map('trim', explode(',', $staff->week_off_day))
        : [];

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = Carbon::create($year, $month, $i);
                $dateStr = $date->format('Y-m-d');
                $dayName = $date->format('l');

            

                $record = $attendance[$dateStr][0] ?? null;

                if ($date->gt(Carbon::today()->endOfDay())) {
                    continue;
                }

                if (in_array($dayName, $weekOffDays)) {
                    $weekOffs++;
                }
                
                if ($record && $record->time_in && $record->time_out && $date->lt(Carbon::today())) {
                        // Present day
                        $present++;

                        $timeIn = Carbon::parse($record->time_in);
                        $timeOut = Carbon::parse($record->time_out);
                        $reportingTime = $timeIn->copy()->setTime(10, 30, 0);

                        // Late if time_in > 10:30 AM
                        if ($timeIn->gt($reportingTime)) {
                            $late++;
                        }

                        // Half day if working hours < 4
                    // if ($timeOut->diffInHours($timeIn) < 4) {
                        //  $halfDay++;
                    // }
                }
    //             elseif ($record && is_null($record->time_in) && is_null($record->time_out) && $date->lt(Carbon::today())) {
        

    //         $absent++;
            
        
    // }
                elseif ($record && $record->status === 'leave') {
                    // Leave day
                    $leave++;

                    if (isset($record->leave_type) && $record->leave_type === 'LWP') {
                        $lwp++;
                    }
                }
                else {
                    // Absent day (no record or no time_in/out and date < today)
                    if ($date->lt(Carbon::today())) {
                        $absent++;
                    }
                }
                
                /*
                if ($record) {
                    if ($record && $record->time_in && $record->time_out && $date->lt(Carbon::today()) && $record->status === 'present') {
                        $present++;
                        if ($record->late_status === 'Half Day') {
                            $halfDay++;
                        }
                    } elseif ($record->status === 'leave') {
                        $leave++;
                        if ($record->leave_type === 'LWP') {
                            $lwp++;
                        }
                    } else {
                        $absent++;
                    }
                } else {
                    $absent++;
                }
                */
                
            }
            


    /*      
    //\Log::info("Days in Month: $daysInMonth, WeekOffs: $weekOffs, Leaves: $leave, LWP: $lwp, Present: $present, Absent: $absent, HalfDay: $halfDay");

    $workingDays = $daysInMonth - $weekOffs - ($leave - $lwp);

    $workingDays = max($workingDays, 1);

    $perDaySalary = $staff->salary / $workingDays;

    $deductions = ($absent + $lwp) * $perDaySalary;
    $finalSalary = $staff->salary - $deductions;

    //\Log::info("PerDaySalary: $perDaySalary, Deductions: $deductions, Final Salary: $finalSalary");
    */
    // Calculate working days excluding leaves (excluding LWP because they are deducted)
    $workingDays = $daysInMonth - $weekOffs - ($leave - $lwp);

    // Don't force to 1; leave it as 0 if all days are out
    //$perDaySalary = $workingDays > 0 ? $staff->salary / $workingDays : 0;
    $perDaySalary = $staff->salary / $workingDays;

    // Calculate exact deductions
    $deductions = ($absent + $lwp) * $perDaySalary;

    // Avoid negative salaries
    $finalSalary = max(0, $staff->salary - $deductions);

        

            // $results[] = [
            //     'staff_id' => $staff->staff_id,
            //     'staff_name' => $staff->staff_name.' (' . ucfirst($roleName) . ')',
            //     'present' => $present,
            //     'absent' => $absent,
            //     'leave' => $leave,
            //     'week_offs' => 0, //by default 0 or $weekoffs
            //     'salary' => $staff->salary, //by default 0 or $weekoffs
            //     'final_salary' => round($finalSalary, 2),
            //     'salary_status' => $salaryStatus,
            // ];
                $results[] = [
                'staff_id' => $staff->staff_id,
                'staff_name' => $staff->staff_name.' (' . ucfirst($roleName) . ')',
                'present' => $calculation['present'],
                'absent' => $calculation['absent'],
                'leave' => $calculation['leave'],
                'week_offs' => $calculation['week_offs'], 
                'salary' => $calculation['base_salary'], 
                'final_salary' => $calculation['final_salary'],
                'salary_status' => $salaryStatus,
            ];
        }

        return DataTables::of($results)->make(true);
    }
    public function getSalarySlip(Request $request)
    {
        $salary = Salarymodel::where([
            'staff_id' => $request->staff_id,
            'month' => $request->month,
            'year' => $request->year,
        ])->first();

        if (!$salary) {
            return response()->json(['error' => 'No record found'], 404);
        }

        $staff = Staffmodel::find($request->staff_id);

        $date = Carbon::createFromDate($salary->year, $salary->month, 1);
        $daysInMonth = $date->daysInMonth;

        $weekOffs = $salary->week_offs ?? 0;
        $leave = $salary->leave_days ?? 0;
        $lwp = $salary->lwp_days ?? 0;
        $absent = $salary->absent_days ?? 0;

        // Calculate working days excluding leave (except LWP)
        $workingDays = $daysInMonth - $weekOffs - max(0, $leave - $lwp);
        $workingDays = max($workingDays, 1);

        $dailySalary = $salary->base_salary / $workingDays;

        $deductions = ($absent + $lwp) * $dailySalary;
        $finalSalary = max(0, $salary->base_salary - $deductions);
        $presentDays = $workingDays - $absent;

        return response()->json([
            'staff_name' => $staff->staff_name,
            'base_salary' => $salary->base_salary,
            'total_working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absent,
            'leave_days' => $leave,
            'lwp_days' => $lwp,
            'week_offs' => $weekOffs,
            'daily_salary' => round($dailySalary, 2),
            'deductions' => round($deductions, 2),
            'absent_deduction' => round(($absent + $lwp) * $dailySalary, 2),
            'final_salary' => round($finalSalary, 2),
            'half_days' => $salary->half_days,
        ]);
    }


    public function downloadSlip(Request $request)
    {
        $salary = Salarymodel::where([
            'staff_id' => $request->staff_id,
            'month' => $request->month,
            'year' => $request->year,
        ])->firstOrFail();

        $staff = Staffmodel::findOrFail($request->staff_id);

        $date = Carbon::createFromDate($salary->year, $salary->month, 1);
        $daysInMonth = $date->daysInMonth;

        $weekOffs = $salary->week_offs ?? 0;
        $leave = $salary->leave_days ?? 0;
        $lwp = $salary->lwp_days ?? 0;
        $absent = $salary->absent_days ?? 0;

        // Salary calculation
        $workingDays = $daysInMonth - $weekOffs - max(0, $leave - $lwp);
        $workingDays = max($workingDays, 1);

        $dailySalary = $salary->base_salary / $workingDays;

    // $absentDeduction = ($absent + $lwp) * $dailySalary;
    // $deductions = $absentDeduction; // Add more deductions if needed
    $absentDeduction = $absent * $dailySalary;
    $lwpDeduction = $lwp * $dailySalary;

    // Optional: add extra deduction logic here
    $otherDeductions = 0;
    $totalDeductions = $absentDeduction + $lwpDeduction + $otherDeductions;
    $finalSalary = max(0, $salary->base_salary - $totalDeductions);
        $presentDays = $workingDays - $absent;

        // Set calculated values for use in view
        $salary->total_working_days = $workingDays;
        $salary->present_days = $presentDays;
        $salary->daily_salary = $dailySalary;
    $salary->absent_day_deduction = $absentDeduction;
    $salary->lwp_day_deduction = $lwpDeduction;
    $salary->other_deductions = $otherDeductions;
        $salary->deductions = $totalDeductions;
    $salary->final_salary = $finalSalary;
        $salary->total_earnings = $salary->base_salary;

        // Optional: Save to DB
        // $salary->save();

        $filename = 'Salary_Slip_' . $staff->staff_name . '_' . $salary->month . '_' . $salary->year . '.pdf';

        $pdf = Pdf::loadView('company/pdf/salary_slip', compact('salary', 'staff'))
                ->setPaper('[0, 0, 595.28, 200]', 'portrait');

        return $pdf->stream($filename);
    }
    // Inside the Salary controller class

// New method to download the UNPAID (preview) slip
    public function downloadPreviewSlip(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:mst_staff,staff_id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        // Use the logic from getStaffSalary/getSalaryData to calculate the projected values
        // We'll call getStaffSalary to get the calculated data structure
        $calculatedData = $this->getStaffSalary($request)->getData();

        if (isset($calculatedData->error)) {
            abort(404, 'Salary calculation data not available.');
        }

        $staff = Staffmodel::findOrFail($request->staff_id);
        $payDate = Carbon::createFromDate($request->year, $request->month, 1)->endOfMonth();

        // Create a mock Salarymodel object for the PDF view
        // The structure needs to match what your 'company/pdf/salary_slip' view expects.
        $salary = (object) [
            'staff_id' => $request->staff_id,
            'month' => $request->month,
            'year' => $request->year,
            'base_salary' => $calculatedData->base_salary,
            'deductions' => $calculatedData->deductions,
            'final_salary' => $calculatedData->final_salary,
            
            // Attendance Data (projected)
            'total_working_days' => $calculatedData->total_working_days,
            'present_days' => $calculatedData->present_days,
            'absent_days' => $calculatedData->absent_days,
            'leave_days' => $calculatedData->leave_days,
            'lwp_days' => $calculatedData->lwp_days,
            'week_offs' => $calculatedData->week_offs,
            'half_days' => $calculatedData->half_days ?? 0,
            'created_at' => $payDate->toDateTimeString(),
            // Deductions breakdown (assuming deductions is just Absent/LWP for preview)
            'daily_salary' => $calculatedData->daily_salary,
            'absent_day_deduction' => round(($calculatedData->absent_days + $calculatedData->lwp_days) * $calculatedData->daily_salary, 2),
            'lwp_day_deduction' => 0, // You might need to adjust this based on your exact calculation
            'other_deductions' => 0,
            'total_earnings' => $calculatedData->base_salary,
            'paid_at' => 'N/A (Preview)', // Indicate it's not actually paid
        ];

        $filename = 'Projected_Salary_Slip_' . $staff->staff_name . '_' . $request->month . '_' . $request->year . '.pdf';

        $pdf = Pdf::loadView('company/pdf/salary_slip', compact('salary', 'staff'))
                ->setPaper('[0, 0, 595.28, 200]', 'portrait');

        return $pdf->stream($filename);
    }
    private function calculateStaffSalaryDetails($staff, $month, $year) 
    {
        $staffId = $staff->staff_id;
        $baseSalary = $staff->salary ?? 0;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        // --- ATTENDANCE CALCULATION ---
        $attendances = Attendancemodel::where('staff_id', $staffId)
            ->whereBetween('time_in', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->time_in)->format('Y-m-d'));

        $present = $absent = $leave = $lwp = $late = $weekOffs = 0;
        $weekOffDays = $staff->week_off_day ? array_map('trim', explode(',', $staff->week_off_day)) : [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::create($year, $month, $i);
            $dateStr = $date->format('Y-m-d');
            $dayName = $date->format('l');

            // Skip future dates for accurate present/absent counts
            if ($date->gt(Carbon::today()->endOfDay())) {
                continue;
            }

            $record = $attendances[$dateStr][0] ?? null;

            if (in_array($dayName, $weekOffDays)) {
                $weekOffs++;
                continue;
            }

            if ($record && $record->time_in && $record->time_out) {
                $present++;
            } elseif ($record && $record->status === 'leave') {
                $leave++;
                if (isset($record->leave_type) && $record->leave_type === 'LWP') {
                    $lwp++;
                }
            } else {
                // Absent: Must be a mandatory workday that has passed without a record
                $absent++; 
            }
        }
        // --- END ATTENDANCE CALCULATION ---

        // --- SALARY CALCULATION ---
        // Working days are all days minus week offs and paid leaves (i.e., leave - lwp)
        $workingDaysForCalculation = $daysInMonth - $weekOffs - max(0, $leave - $lwp);
        $workingDaysForCalculation = max($workingDaysForCalculation, 1);
        $perDaySalary = $baseSalary / $workingDaysForCalculation;

        // Deduct based on absent days + LWP days
        $totalDeductions = ($absent + $lwp) * $perDaySalary;
        $finalSalary = max(0, $baseSalary - $totalDeductions);

        // Present days shown in UI is just the attendance count
        $presentDaysCount = $present;

        return [
            'base_salary' => $baseSalary,
            'present' => $presentDaysCount,
            'absent' => $absent,
            'leave' => $leave,
            'lwp' => $lwp,
            'week_offs' => $weekOffs,
            'working_days' => $workingDaysForCalculation,
            'daily_salary' => round($perDaySalary, 2),
            'deductions' => round($totalDeductions, 2),
            'final_salary' => round($finalSalary, 2),
            // Add other data like late/half_day if needed
        ];
    }


}
