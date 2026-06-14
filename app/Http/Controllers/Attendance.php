<?php

namespace App\Http\Controllers;

use App\Models\Attendancemodel;
use App\Models\FaceAttendanceLogs;
use App\Models\ForgotOutRequestmodel;
use App\Models\RoleModel;
use App\Models\Staffmodel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // Don't forget this import
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Jenssegers\Agent\Agent;

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
        $agent = new Agent();
         $faceImage = $request->input('face_image');
        try {
            $validator = Validator::make($request->all(), [
                'face_data' => 'required|json',
                'face_image' => 'required|string',
                'attendance_type' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $saveImage = function($base64Image, $staffId, $type) {
            try {
                // Remove data URI scheme (e.g., "data:image/png;base64,")
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type_match)) {
                    $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                    $fileType = $type_match[1] === 'jpeg' ? 'jpeg' : 'png'; // Ensure a valid file extension
                } else {
                    $fileType = 'png'; // Default to png if header is missing
                }

                $imageData = base64_decode($base64Image);
                $fileName = Str::uuid() . '_' . $staffId . '_' . $type . '.' . $fileType;

                // --- THE KEY CHANGE IS HERE ---
                // 1. Define the directory path using public_path()
                $directoryPath = public_path('assets/uploads/attendance/' . date('Y/m/d')); 
                
                // 2. Ensure the directory exists (create it if not)
                if (!File::isDirectory($directoryPath)) {
                    File::makeDirectory($directoryPath, 0777, true, true);
                }

                // 3. Define the full absolute file path
                $fullPath = $directoryPath . '/' . $fileName; 
                
                // 4. Save the file directly using file_put_contents or Laravel's File::put
                File::put($fullPath, $imageData); 

                // 5. Manually construct the URL (based on public directory)
                $publicUrl = asset('assets/uploads/attendance/' . date('Y/m/d') . '/' . $fileName);
                
                return $publicUrl;
                // --- END OF KEY CHANGE ---
                
            } catch (\Exception $e) {
                \Log::error("Error saving attendance image: " . $e->getMessage());
                return null;
            }
        };

        //     $saveImage = function($base64Image, $staffId, $type) {
        //     try {
        //         // Remove data URI scheme (e.g., "data:image/png;base64,")
        //         if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type_match)) {
        //             $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        //             $fileType = $type_match[1]; // e.g., 'png'
        //         } else {
        //             $fileType = 'png'; // Default to png if header is missing
        //         }

        //         $imageData = base64_decode($base64Image);
        //         $fileName = Str::uuid() . '_' . $staffId . '_' . $type . '.' . $fileType;
        //         $path = 'attendance/' . date('Y/m/d') . '/'; // Path structure

        //         // Use a configured disk (e.g., 'public')
        //         Storage::disk('public')->put($path . $fileName, $imageData);

        //         // Return the publicly accessible URL
        //         return Storage::disk('public')->url($path . $fileName);
        //     } catch (\Exception $e) {
        //         \Log::error("Error saving attendance image: " . $e->getMessage());
        //         return null;
        //     }
        // };

            $staffMembers = Staffmodel::whereNotNull('face_data')->get();
            $inputDescriptor = json_decode($request->face_data, true);
            $bestMatch = null;
            $bestDistance = 0.6;

            foreach ($staffMembers as $staff) {
                $storedDescriptor = json_decode($staff->face_data, true);

                // Skip if face_data is invalid or not an array
                if (!is_array($storedDescriptor)) {
                    continue;
                }

                // Skip if descriptor lengths don't match
                if (count($inputDescriptor) !== count($storedDescriptor)) {
                    continue;
                }

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

            $attendanceType = $request->attendance_type;
            $existingTodayAttendance = Attendancemodel::where('staff_id', $bestMatch->staff_id)
                ->whereDate('time_in', today())
                ->first();

            $previousUnclosed = Attendancemodel::where('staff_id', $bestMatch->staff_id)
                ->whereNull('time_out')
                ->whereDate('time_in', '<', today())
                ->orderBy('time_in', 'desc')
                ->first();

            if ($attendanceType == 'out_time') {
                if (!$existingTodayAttendance) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You must check in before checking out'
                    ], 400);
                }

                if ($existingTodayAttendance->time_out) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You have already checked out today'
                    ], 400);
                }
            }

            if ($previousUnclosed && !$existingTodayAttendance) {
                $timeIn = Carbon::parse($previousUnclosed->time_in);
                $date = $timeIn->format('Y-m-d');

                $existingRequest = ForgotOutRequestmodel::where('staff_id', $bestMatch->staff_id)
                    ->whereDate('date', $date)
                    ->exists();

                if (!$existingRequest) {
                    return response()->json([
                        'status' => false,
                        'requires_forgot_request' => true,
                        'message' => 'You must submit a forgot out request for ' . $date . ' before punching in today',
                        'date' => $date,
                        'attendance_id' => $previousUnclosed->id,
                        'staff_id' => $bestMatch->staff_id,
                        'staff' => $bestMatch
                    ], 200);
                }
            }

            $lateStatus = null;
            $message = '';
             $remark = $request->has('remark') ? $request->remark : null;

            if ($attendanceType == 'in_time') {

                // BEGIN shift-based lateness/remark logic
            $shiftTiming = $bestMatch->shift_timing;
            $shiftStart = null;

            if ($shiftTiming && preg_match('/(\d{1,2}:\d{2}\s*[APMapm]{2})/', $shiftTiming, $matches)) {
                $shiftStart = Carbon::parse($matches[1]);
            } else {
                $shiftStart = Carbon::today()->setHour(10)->setMinute(0);
            }

             $allowedIn = $shiftStart->copy()->addMinutes(15);
            $current = now();
            $isLate = $current->gt($allowedIn);

            $lateCount = Attendancemodel::where('staff_id', $bestMatch->staff_id)
                ->whereMonth('time_in', $current->month)
                ->whereYear('time_in', $current->year)
                ->whereTime('time_in', '>', '10:30:00')  // ->whereTime('time_in', '>', '10:30:00')->where('late_status', 'Late')
                ->count();

                 // Check if need remark (4th late instance this month onwards)
            if ($isLate && $lateCount >= 3 && !$request->has('remark')) {
                return response()->json([
                    'status' => false,
                    'requires_remark' => true,
                    'message' => 'Already 3 late check-ins. Please provide a remark to continue.',
                    'late_count' => $lateCount,
                    'staff' => $bestMatch,
                ], 200);
            }

            // Set late status
            if ($current->lte($allowedIn)) {
                $lateStatus = 'On Time';
            } elseif ($current->gt($allowedIn) && $current->lt($shiftStart->copy()->addHours(2))) {
                $lateStatus = 'Late';
            } else {
                $lateStatus = 'Half Day';
            }

                // $reportingTime = Carbon::today()->setHour(10)->setMinute(0);
                // $bufferTime = $reportingTime->copy()->addMinutes(10);
                // $current = now();

                // if ($current->lte($bufferTime)) {
                //     $lateStatus = 'On Time';
                // } elseif ($current->gt($bufferTime) && $current->lt($reportingTime->copy()->addHours(2))) {
                //     $lateStatus = 'Late';
                // } else {
                //     $lateStatus = 'Half Day';
                // }

                $faceImageInUrl = $saveImage($faceImage, $bestMatch->staff_id, 'in');

                if (!$existingTodayAttendance) {
                    Attendancemodel::create([
                        'staff_id' => $bestMatch->staff_id,
                        'time_in' => $current,
                        'status' => 'present',
                        'late_status' => $lateStatus,
                        'remark' => $remark,
                        'face_image_in_url' => $faceImageInUrl
                    ]);
                    $message = 'Check-in recorded successfully';
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Attendance already recorded for today',
                        'existing_attendance' => $existingTodayAttendance
                    ], 400);
                }
            } elseif ($attendanceType == 'lunch_out') {
                if ($existingTodayAttendance) {
                    $existingTodayAttendance->update(['lunch_out' => now()]);
                    $message = 'Lunch Out recorded successfully';
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'No check-in found for lunch out'
                    ], 400);
                }
            } elseif ($attendanceType == 'lunch_in') {
                // You can update lunch_in column in Attendancemodel if exists
                if ($existingTodayAttendance) {
                    $existingTodayAttendance->update(['lunch_in' => now()]);
                    $message = 'Lunch In recorded successfully';
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'No check-in found for lunch in'
                    ], 400);
                }
            } elseif ($attendanceType == 'out_time') {
                if ($existingTodayAttendance) {
                    if (!$existingTodayAttendance->time_out) {
                         $faceImageOutUrl = $saveImage($faceImage, $bestMatch->staff_id, 'out');

                        $existingTodayAttendance->update(['time_out' => now(),'face_image_out_url' => $faceImageOutUrl,]);
                        $message = 'Check-out recorded successfully';
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Attendance already completed for today'
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'No check-in found for check-out'
                    ], 400);
                }
            }

            $browser = $agent->browser();
            $browserVersion = $agent->version($browser);
            $platform = $agent->platform();
            $platformVersion = $agent->version($platform);
            $device = $agent->device();

            $ipAddress = $request->ip();
            $userAgent = $request->header('User-Agent');
            $isMobile = $agent->isMobile();
            $isTablet = $agent->isTablet();
            $isDesktop = $agent->isDesktop();
            $isRobot = $agent->isRobot();
            $languages = $request->getLanguages();

            $deviceInfo = [
                'ip_address' => $ipAddress,
                'browser' => $browser . ' ' . $browserVersion,
                'platform' => $platform . ' ' . $platformVersion,
                'device' => $device,
                'user_agent' => $userAgent,
                'is_mobile' => $isMobile,
                'is_tablet' => $isTablet,
                'is_desktop' => $isDesktop,
                'is_robot' => $isRobot,
                'languages' => $languages
            ];

            FaceAttendanceLogs::create([
                'staff_id' => $bestMatch->staff_id,
                'scan_time' => now(),
                'scan_type' => $attendanceType,
                'device_id' => json_encode($deviceInfo),
                'confidence' => $bestDistance,
                'face_image_url' => $attendanceType == 'in_time' ? ($faceImageInUrl ?? null) : ($faceImageOutUrl ?? null)
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => $message,
                'staff' => $bestMatch,
                'distance' => $bestDistance
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Full working
    // public function markAttendance(Request $request)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'face_data' => 'required|json',
    //             'attendance_type' => 'required',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Validation error',
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $staffMembers = Staffmodel::whereNotNull('face_data')->get();
    //         $inputDescriptor = json_decode($request->face_data, true);
    //         $bestMatch = null;
    //         $bestDistance = 0.6;

    //         foreach ($staffMembers as $staff) {
    //             $storedDescriptor = json_decode($staff->face_data, true);
    //             $distance = $this->getEuclideanDistance($inputDescriptor, $storedDescriptor);
    //             if ($distance < $bestDistance) {
    //                 $bestDistance = $distance;
    //                 $bestMatch = $staff;
    //             }
    //         }

    //         if (!$bestMatch) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'No matching staff member found'
    //             ], 404);
    //         }

    //         $existingTodayAttendance = Attendancemodel::where('staff_id', $bestMatch->staff_id)
    //             ->whereDate('time_in', today())
    //             ->first();

    //         if ($existingTodayAttendance) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Attendance already recorded for today',
    //                 'existing_attendance' => $existingTodayAttendance
    //             ], 400);
    //         }


    //         // Check for previous unclosed attendance
    //         $previousUnclosed = Attendancemodel::where('staff_id', $bestMatch->staff_id)
    //             ->whereNull('time_out')
    //             ->whereDate('time_in', '<', today())
    //             ->orderBy('time_in', 'desc')
    //             ->first();

    //         // Check if today's attendance exists
    //         $existingAttendance = Attendancemodel::where('staff_id', $bestMatch->staff_id)
    //             ->whereDate('time_in', today())
    //             ->first();

    //         // If there's a previous unclosed attendance and no existing attendance for today
    //         if ($previousUnclosed && !$existingAttendance) {
    //             $timeIn = Carbon::parse($previousUnclosed->time_in);
    //             $date = $timeIn->format('Y-m-d');

    //             // Check if request already exists
    //             $existingRequest = ForgotOutRequestmodel::where('staff_id', $bestMatch->staff_id)
    //                 ->whereDate('date', $date)
    //                 ->exists();

    //             if (!$existingRequest) {
    //                 return response()->json([
    //                     'status' => false,
    //                     'requires_forgot_request' => true,
    //                     'message' => 'You must submit a forgot out request for ' . $date . ' before punching in today',
    //                     'date' => $date,
    //                     'attendance_id' => $previousUnclosed->id,
    //                     'staff_id' => $bestMatch->staff_id,
    //                     'staff' => $bestMatch
    //                 ], 200);
    //             }
    //         }

    //         // Handle today's attendance
    //         if ($existingAttendance) {
    //             if (!$existingAttendance->time_out) {
    //                 $existingAttendance->update(['time_out' => now()]);
    //                 $message = 'Check-out recorded successfully';
    //             } else {
    //                 return response()->json([
    //                     'status' => false,
    //                     'message' => 'Attendance already completed for today'
    //                 ], 400);
    //             }
    //         } else {
    //             Attendancemodel::create([
    //                 'staff_id' => $bestMatch->staff_id,
    //                 'time_in' => now(),
    //                 'status' => 'present'
    //             ]);
    //             $message = 'Check-in recorded successfully';
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => $message,
    //             'staff' => $bestMatch,
    //             'distance' => $bestDistance
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'An error occurred',
    //             'error' => config('app.debug') ? $e->getMessage() : null
    //         ], 500);
    //     }
    // }
    public function submitForgotOutRequest(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'attendance_id' => 'required|exists:attendances,id',
            'staff_id' => 'required|exists:mst_staff,staff_id',
            'reason' => 'required|string|max:500'
        ]);

        // Check if request already exists
        $existingRequest = ForgotOutRequestmodel::where('staff_id', $validated['staff_id'])
            ->whereDate('date', $validated['date'])
            ->exists();

        if ($existingRequest) {
            return response()->json([
                'status' => false,
                'message' => 'A request for this date already exists'
            ], 400);
        }

        // Create new request
        $forgotRequest = ForgotOutRequestmodel::create([
            'staff_id' => $validated['staff_id'],
            'attendance_id' => $validated['attendance_id'],
            'date' => $validated['date'],
            'description' => $validated['reason'],
            'status' => 'pending'
        ]);

        // Mark today's attendance after successful request submission
        $todayAttendance = Attendancemodel::firstOrCreate([
            'staff_id' => $validated['staff_id'],
            'time_in' => today()
        ], [
            'time_in' => now(),
            'status' => 'present'
        ]);

        // Get staff details
        $staff = Staffmodel::find($validated['staff_id']);

        return response()->json([
            'status' => true,
            'message' => 'Request submitted successfully',
            'attendance_marked' => true,
            'staff' => $staff,
            'attendance' => $todayAttendance
        ]);
    }
    public function approveForgotOutRequest($id)
    {
        try {
            $request = ForgotOutRequestmodel::findOrFail($id);

            // Update the original attendance record
            $attendance = Attendancemodel::find($request->attendance_id);
            $attendance->update([
                'time_out' => $request->date . ' 23:59:59', // or your business logic
                'status' => 'present'
            ]);

            // Update the request status
            $request->update(['status' => 'approved']);

            return response()->json([
                'status' => true,
                'message' => 'Request approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error approving request',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function getEuclideanDistance(array $descriptor1, array $descriptor2)
    {
        if (count($descriptor1) !== count($descriptor2)) {
            throw new \InvalidArgumentException('Descriptor arrays must be of the same length');
        }

        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $sum += pow($descriptor1[$i] - $descriptor2[$i], 2);
        }
        return sqrt($sum);
    }
}



