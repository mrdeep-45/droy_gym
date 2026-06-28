<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceModel;
use App\Models\MemberModel;
use App\Models\SubscriptionModel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    //
    public function index()
    {
        $page_title = 'Attendance';
        $page_name  = 'Attendance';

        return view('company.master.attendance', compact('page_title', 'page_name'));
    }
    // Scan / type a member_number to check in. If already checked in today with no
    // check_out, this checks them out instead (toggle behaviour for a single scanner).
    public function checkIn(Request $request)
    {
        try {
            $request->validate(['member_number' => 'required']);

            $member = MemberModel::where('member_number', $request->member_number)->first();

            if (!$member) {
                return response()->json(['status' => 'error', 'message' => 'Member not found. Check the ID.'], 404);
            }

            // Block expired / inactive members - the "Plan Expired - Access Denied" flow
            $activeSub = SubscriptionModel::where('member_id', $member->id)
                ->where('status', 'Active')
                ->where('end_date', '>=', Carbon::today())
                ->exists();

            if (!$activeSub) {
                return response()->json([
                    'status'  => 'denied',
                    'message' => 'Plan Expired - Access Denied'
                ], 403);
            }

            $today = Carbon::today();
            $existing = AttendanceModel::where('member_id', $member->id)
                ->whereDate('check_in', $today)
                ->whereNull('check_out')
                ->first();

            if ($existing) {
                $existing->update(['check_out' => now()]);
                return response()->json([
                    'status'  => 'success',
                    'type'    => 'check_out',
                    'message' => $member->full_name . ' checked out successfully.'
                ]);
            }

             AttendanceModel::create([
                'member_id' => $member->id,
                'check_in'  => now()
            ]);

            return response()->json([
                'status'  => 'success',
                'type'    => 'check_in',
                'message' => $member->full_name . ' checked in successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function checkOut(Request $request)
    {
        try {
            $attendance = AttendanceModel::find($request->attendance_id);
            if (!$attendance) {
                return response()->json(['status' => 'error', 'message' => 'Record not found.'], 404);
            }
            $attendance->update(['check_out' => now()]);
            return response()->json(['status' => 'success', 'message' => 'Checked out successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getList()
    {
        try {
            $query = AttendanceModel::with('member')
                ->orderBy('check_in', 'desc');

            return DataTables::of($query)
                ->addColumn('member_name', fn($row) => $row->member->full_name ?? '-')
                ->addColumn('member_number', fn($row) => $row->member->member_number ?? '-')
                ->addColumn('duration', function ($row) {
                    if ($row->check_out) {
                        $diff = Carbon::parse($row->check_in)->diff(Carbon::parse($row->check_out));
                        return $diff->format('%H:%I:%S');
                    }
                    return '<span class="badge bg-success">Still inside</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-danger delete-attendance" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>';
                })
                ->addIndexColumn()
                ->rawColumns(['duration', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            $attendance = AttendanceModel::find($request->attendance_id);
            if (!$attendance) {
                return response()->json(['status' => 'error', 'message' => 'Record not found.']);
            }
            $attendance->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Attendance record removed.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

}
