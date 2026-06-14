<?php

namespace App\Http\Controllers;

use App\Models\Attendancemodel;
use App\Models\RoleModel;
use App\Models\Staffmodel;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Attendance extends Controller
{
    public function index()
    {
        $page_title = 'Attendance';
        $page_name = 'Attendance';
        $role = RoleModel::where('status', 0)->orderBy('role_name', 'asc')->get();
        return view('company/master/staff/attendance', compact('page_title', 'page_name', 'role'));
    }
    public function getRegisteredCount()
    {
        $count = Staffmodel::whereNotNull('face_data')->count();
        return response()->json([
            'status' => true,
            'count' => $count
        ]);
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
            $staffMembers = Staffmodel::whereNotNull('face_data')->get();
            $inputDescriptor = json_decode($request->face_data, true);
            $bestMatch = null;
            $bestDistance = 0.6;
            foreach ($staffMembers as $staff) {
                $storedDescriptor = json_decode($staff->face_data, true);
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
            $existingAttendance = Attendancemodel::where('staff_id', $bestMatch->staff_id)
                ->whereDate('time_in', today())
                ->first();
            if ($existingAttendance) {
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
                Attendancemodel::create([
                    'staff_id' => $bestMatch->staff_id,
                    'time_in' => now(),
                    'status' => 'present'
                ]);
                $message = 'Check-in recorded successfully';
            }
            return response()->json([
                'status' => true,
                'message' => $message,
                'staff' => $bestMatch,
                'distance' => $bestDistance
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
