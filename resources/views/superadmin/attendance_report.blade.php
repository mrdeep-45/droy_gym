<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report - {{ $monthName }} {{ $year }}</title>
    <style>
        /* PDF Global and Table Styling */
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 7px; /* Very small font for A4 landscape */
        }
        
        .report-header {
            width: 100%;
            height: 50px; /* Define a fixed height for the header area */
            margin-bottom: 5px;
        }

        .logo-container {
            float: left;
            width: 10%; /* Reduced logo width */
            text-align: left;
        }
        .logo-container img { 
            max-width: 60px; /* Smaller logo */
            height: auto;
        }
        
        .title-container {
            float: left;
            width: 90%; /* Increased title width */
            text-align: center;
        }
        .title-container h1 {
            font-size: 16px; margin: 0; padding: 0;
        }
        .title-container h2 {
            font-size: 12px; margin: 2px 0 0 0; padding: 0;
        }
        .clearfix::after {
            content: ""; clear: both; display: table;
        }
        
        /* Table Structure */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; /* Ensures column widths are respected */
        }
        
        th, td { 
            border: 1px solid #000; 
            padding: 1px 0px; /* Minimal padding */
            text-align: center; 
            height: 12px; /* Fixed height for date cells */
        }
        
        th { 
            background-color: #1E88E5; 
            color: #FFFFFF;
            font-weight: bold; 
            font-size: 7px;
        }
        
        /* Specific column width adjustments */
        table thead th:first-child,
        table tbody td:first-child {
            width: 15%; /* Wider Staff Name column */
            text-align: left;
            padding-left: 5px;
            font-weight: bold;
        }

        /* Default date columns (the rest) need minimal width. 
           We rely on A4 landscape and small font for the rest of the columns to fit. */

        /* Coloring the symbols */
        .present { color: green; font-weight: bold; }
        .absent { color: red; font-weight: bold; }
        .leave { color: #FF9800; font-weight: bold; }
    </style>
</head>
<body>

    <div class="report-header clearfix">
        <!-- <div class="logo-container">
            @if(file_exists($logoPath))
                {{-- Use base64 encoding for better DomPDF compatibility --}}
                @php
                    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $data_content = file_get_contents($logoPath);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data_content);
                @endphp
                <img src="{{ $base64 }}" alt="Company Logo">
            @else
                <p style="font-size: 8px; margin-top: 10px;">[Logo Missing]</p>
            @endif
        </div> -->
        <div class="title-container">
            <h1>ATTENDANCE REPORT</h1>
            <h2>{{ $monthName }} {{ $year }}</h2>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                @foreach ($header as $title)
                    <th>{{ $title }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    @foreach ($row as $key => $status)
                        @php
                            $class = '';
                            if ($key !== 'Staff Name') {
                                if ($status === '✔') {
                                    $class = 'present';
                                } elseif ($status === '✖') {
                                    $class = 'absent';
                                } elseif (substr($status, 0, 2) === 'L ') {
                                    $class = 'leave';
                                }
                            }
                        @endphp
                        <td class="{{ $class }}">
                            {{ $status }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>