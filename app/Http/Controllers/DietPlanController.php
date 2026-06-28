<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DietWorkoutPlanModel;
use App\Models\MemberModel;
use App\Models\TrainerModel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class DietPlanController extends Controller
{
    //
    private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    private $meals = ['Breakfast', 'Lunch', 'Pre-Workout', 'Dinner'];

    public function index()
    {
        $page_title = 'Diet & Workout Plans';
        $page_name  = 'Diet/Workout';

        $members  = MemberModel::where('status', 'Active')->select('id', 'full_name', 'member_number')->get();
        $trainers = TrainerModel::where('status', 0)->select('trainer_id', 'trainer_name')->get();

        return view('company.master.dietplan', compact(
            'page_title', 'page_name', 'members', 'trainers'
        ))->with(['days' => $this->days, 'meals' => $this->meals]);
    }
    public function storeOrUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'member_id'  => 'required',
                'trainer_id' => 'required',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date'
            ]);

            $plan_id = $request->plan_record_id;

            $workout = [];
            foreach ($this->days as $day) {
                $workout[$day] = $request->input('workout_' . $day, '');
            }

            $diet = [];
            foreach ($this->meals as $meal) {
                $diet[$meal] = $request->input('diet_' . str_replace('-', '_', $meal), '');
            }

            $data = [
                'member_id'       => $request->member_id,
                'trainer_id'      => $request->trainer_id,
                'start_date'      => $request->start_date,
                'end_date'        => $request->end_date,
                'workout_details' => json_encode($workout),
                'diet_details'    => json_encode($diet),
            ];

            if ($plan_id) {
                $plan = DietWorkoutPlanModel::find($plan_id);
                if (!$plan) {
                    return response()->json(['status' => 'error', 'message' => 'Plan not found.'], 404);
                }
                $data['updated_by'] = getUpdatedBy();
                $data['updated_at'] = now();
                $plan->update($data);
                $message = 'Diet & workout plan updated successfully.';
            } else {
                $data['created_by'] = getCreatedBy();
                $data['created_at'] = now();
                DietWorkoutPlanModel::create($data);
                $message = 'Diet & workout plan assigned successfully.';
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getList()
    {
        try {
            $query = DietWorkoutPlanModel::with(['member', 'trainer'])
                ->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addColumn('member_name', fn($row) => $row->member->full_name ?? '-')
                ->addColumn('trainer_name', fn($row) => $row->trainer->trainer_name ?? '-')
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-primary edit-dietplan" data-id="' . $row->id . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-dietplan" data-id="' . $row->id . '">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    ';
                })
                ->addIndexColumn()
                ->rawColumns(['action'])
                ->make(true);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $plan = DietWorkoutPlanModel::find($id);
            if (!$plan) {
                return response()->json(['status' => 'error', 'message' => 'Plan not found'], 404);
            }
            return response()->json(['status' => 'success', 'data' => $plan]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            $plan = DietWorkoutPlanModel::find($request->plan_record_id);
            if (!$plan) {
                return response()->json(['status' => 'error', 'message' => 'Plan not found.']);
            }
            $plan->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Plan removed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
