<!DOCTYPE html>
<html>
<head>
    <title>Staff Ledger Report - {{ $monthName }} {{ $year }}</title>
    <style>
        /* Basic Styles for PDF Generation (Dompdf) */
        body {
            font-family: DejaVu Sans, sans-serif; /* Recommended font for Dompdf to support all characters */
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            word-wrap: break-word; /* Ensure text wraps in narrow columns */
        }
        th {
            background-color: #1E88E5; 
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            color: #FFFFFF;
        }
        td {
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .money {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Staff Ledger Report</h1>
        <p>{{ $monthName }} {{ $year }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 18%">Staff Name (Role)</th>
                <th style="width: 9%">Base Salary</th>
                
                <th style="width: 6%">Total Days</th>
                <th style="width: 6%">Present Days</th>
                <th style="width: 6%">Leave Days</th>
                <th style="width: 6%">Absent Days</th>
                <th style="width: 6%">Late Marks</th>
                <th style="width: 6%">Week Off </th>
                <th style="width: 6%">Lwp Leave </th>
                <th style="width: 6%">Half Days</th>
                
                <th style="width: 10%">Per Day Salary</th>
                <th style="width: 10%">Absent Deduction</th>
                <th style="width: 17%">**Net Payable**</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $staff)
                <tr>
                    <td>{{ $staff['staff_name'] }} ({{ $staff['role'] }})</td>
                    <td class="text-right">{{ number_format($staff['base_salary'], 2) }}</td>
                    
                    <td class="text-center">{{ $staff['total_days_month'] }}</td>
                    <td class="text-center">{{ $staff['present_days'] }}</td>
                    <td class="text-center">{{ $staff['leave_days'] }}</td>
                    <td class="text-center">{{ $staff['absent_days'] }}</td>
                    <td class="text-center">{{ $staff['late_mark_count'] }}</td>
                    <td class="text-center">{{ $staff['week_offs'] }}</td>
                    <td class="text-center">{{ $staff['lwp_days'] }}</td>
                    <td class="text-center">{{ $staff['half_day_count'] }}</td>
                    
                    <td class="text-right">{{ number_format($staff['per_day_salary'], 2) }}</td>
                    <td class="text-right">{{ number_format($staff['deduction_absent'], 2) }}</td>
                    <td class="text-right money">{{ number_format($staff['net_payable'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No staff ledger data found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="position: absolute; bottom: 20px; width: 100%; text-align: center; font-size: 8px;">
        Report Generated on {{ Carbon\Carbon::now()->format('Y-m-d H:i:s') }}
    </div>

</body>
</html>