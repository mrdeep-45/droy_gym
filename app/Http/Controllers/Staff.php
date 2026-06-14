<?php

namespace App\Http\Controllers;

use App\Models\RoleModel;
use App\Models\Staffmodel;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Staff extends Controller
{
    public function index()
    {
        $page_title = 'Staff';
        $page_name = 'Staff';
        $role = RoleModel::where('status', 0)->orderBy('role_name', 'asc')->get();
        return view('company/master/staff/staff', compact('page_title', 'page_name', 'role'));
    }
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'salute' => 'required|in:Mr,Mrs',
                'staff_name' => 'required|max:100',
                'staff_email' => 'required|email|unique:mst_staff,staff_email',
                'phone' => 'required|digits_between:10,15',
                'address' => 'required|max:255',
                'position' => 'required|max:100',
                'department' => 'required|max:100',
                'salary' => 'required|numeric|min:0',
                'hire_date' => 'required|date',
                'probation_period_months' => 'required|integer|min:1',
                'face_data' => 'required|json',
                'employment_category' => 'required|string|max:100',
                'week_off_day' => 'required|string|max:20',
                'shift_type' => 'required|string|max:50',
                'shift_timing' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            //automatic count
            $hireDate = Carbon::parse($request->hire_date);
            $probationPeriodMonths = (int)$request->probation_period_months;
       // $isProbationCompleted = $hireDate->diffInMonths(now()) >= 3;
        $isProbationCompleted = $hireDate->diffInMonths(now()) >= $probationPeriodMonths;

            // Create the staff record
            $staff = Staffmodel::create([
                'salute' => $request->salute,
                'role_id' => $request->role_id,
                'staff_name' => $request->staff_name,
                'staff_email' => $request->staff_email,
                'password' => md5(123456),
                'phone' => $request->phone,
                'address' => $request->address,
                'position' => $request->position,
                'department' => $request->department,
                'employment_category' => $request->employment_category,
                'salary' => $request->salary,
                'face_data' => $request->face_data,
                'face_image' => $request->face_image,

                'hire_date' => Carbon::parse($request->hire_date)->format('Y-m-d'),
                'probation_period_months' => $probationPeriodMonths,
                'is_probation_completed' => $isProbationCompleted ? 1 : 0,
                'week_off_day' => $request->week_off_day,
                'shift_type' => $request->shift_type,
                'shift_timing' => $request->shift_timing,
                'created_by' => getCreatedBy(),
            ]);

            if (!$staff) {
                throw new \Exception('Failed to create staff record');
            }

             //  Insert default leave balance for the new staff
        LeaveBalance::create([
            'staff_id' => $staff->staff_id,
            'CL' => 0,
            'PL' => 0,
            'SL' => 0,
            'LWP' => 0,
            'cl_allocated' => 0,
            'pl_allocated' => 0,
            'sl_allocated' => 0,
            'lwp_allocated' => 0,
            'cl_used' => 0,
            'pl_used' => 0,
            'sl_used' => 0,
            'lwp_used' => 0,
            'leave_type' => 'CL,PL,SL,LWP', // or use your logic for default leave type
            'earned' => 0,
            'used' => 0,
            'carried_forward' => 0,
            'year' => now()->year, // or Carbon::parse($staff->hire_date)->year
            'created_at' => now(),
            'updated_at' => now(),
        ]);

            return response()->json([
                'status' => true,
                'message' => 'Staff created successfully',
                'data' => $staff
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while processing your request',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }
    public function list()
    {
        $staff = Staffmodel::select(['staff_id', 'salute', 'staff_name', 'staff_email', 'phone', 'position', 'department', 'hire_date', 'role_id'])->orderByDesc('created_at');;

        $permissions = checkPermissions(get_index_route($this));
        $canUpdate = $permissions['canUpdate'] ?? false;
        $canDelete = $permissions['canDelete'] ?? false;


        return DataTables::of($staff)
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
            ->editColumn('hire_date', function ($row) {
                return date('d-m-Y', strtotime($row->hire_date));
            })
            ->editColumn('salute', function ($row) {
                return $row->salute . '. ' . $row->staff_name . '<br><b>' . get_role_name($row->role_id) . '</b>';
            })
            ->rawColumns(['action', 'salute'])
            ->make(true);
    }
    public function update(Request $request)
    {

        try {
            $id=$request->staff_id;
            
            $validator = Validator::make($request->all(), [
                'salute' => 'required|in:Mr,Mrs',
                'staff_name' => 'required|max:100',
                'staff_email' => 'required|email|unique:mst_staff,staff_email,' . $id . ',staff_id',
                'phone' => 'required|digits_between:10,15',
                'address' => 'required|max:255',
                'position' => 'required|max:100',
                'department' => 'required|max:100',
                'salary' => 'required|numeric|min:0',
                'hire_date' => 'required|date',
                'face_data' => 'required|json',
                'employment_category' => 'required|string|max:100',
                'week_off_day' => 'required|string|max:20',
                'shift_type' => 'required|string|max:50',
                'shift_timing' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $staff = Staffmodel::findOrFail($id);

            $staff->update([
                'salute' => $request->salute,
                'role_id' => $request->role_id,
                'staff_name' => $request->staff_name,
                'staff_email' => $request->staff_email,
                'phone' => $request->phone,
                'address' => $request->address,
                'position' => $request->position,
                'department' => $request->department,
                'employment_category' => $request->employment_category,
                'salary' => $request->salary,
                'face_data' => $request->face_data,
                'face_image' => $request->face_image,

                'hire_date' => Carbon::parse($request->hire_date)->format('Y-m-d'),
                'week_off_day' => $request->week_off_day,
                'shift_type' => $request->shift_type,
                'shift_timing' => $request->shift_timing,
                'updated_by' => getCreatedBy(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Staff updated successfully',
                'data' => $staff
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    public function get_staff(Request $request)
    {
            $id = $request->input('staff_id');

            $staff = Staffmodel::select([
                'staff_id',
                'salute',
                'role_id',
                'staff_name',
                'staff_email',
                'phone',
                'address',
                'position',
                'department',
                'employment_category',
                'salary',
                'face_data',
                'face_image',
                'hire_date',
                'week_off_day',
                'shift_type',
                'shift_timing'
            ])
            ->where('staff_id', $id)
            ->first();

            if ($staff) {
                return response()->json(['status' => true, 'data' => $staff]);
            } else {
                return response()->json(['status' => false, 'message' => 'Staff not found']);
            }
    }



    public function markAttendance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'face_data' => 'required|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get all staff with face data
            $staffMembers = Staffmodel::whereNotNull('face_data')->get();
            $inputDescriptor = json_decode($request->face_data, true);

            $bestMatch = null;
            $bestDistance = 0.6; // Threshold for face recognition (lower is more strict)

            foreach ($staffMembers as $staff) {
                $storedDescriptor = json_decode($staff->face_data, true);

                // Calculate Euclidean distance between descriptors
                $distance = $this->getEuclideanDistance($inputDescriptor, $storedDescriptor);

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch = $staff;
                }
            }

            if (!$bestMatch) {
                return response()->json([
                    'status' => false,
                    'message' => 'No matching staff member found'
                ], 404);
            }

            // Check if staff already has attendance for today
            $existingAttendance = Attendance::where('staff_id', $bestMatch->id)
                ->whereDate('time_in', today())
                ->first();

            if ($existingAttendance) {
                // Update time_out if exists (for checkout)
                if (!$existingAttendance->time_out) {
                    $existingAttendance->update(['time_out' => now()]);
                    $message = 'Check-out recorded successfully';
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Attendance already completed for today'
                    ], 400);
                }
            } else {
                // Create new attendance record (check-in)
                Attendance::create([
                    'staff_id' => $bestMatch->id,
                    'time_in' => now(),
                    'status' => 'present'
                ]);
                $message = 'Check-in recorded successfully';
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'staff' => $bestMatch
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function getEuclideanDistance(array $descriptor1, array $descriptor2)
    {
        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $sum += pow($descriptor1[$i] - $descriptor2[$i], 2);
        }
        return sqrt($sum);
    }
}
