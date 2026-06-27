<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionModel;
use App\Models\MemberModel;
use App\Models\Planmodel;
use App\Models\TrainerModel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    //
    public function index()
    {
        $page_title = 'Member Subscription';
        $page_name  = 'Subscription';

        $members = MemberModel::where('status', 'Active')->select('id', 'full_name', 'member_number')->get();
        $plans   = Planmodel::where('status', 0)->select('plan_id', 'plan_name', 'duration', 'price')->get();
        $trainers = TrainerModel::where('status', 0)->select('trainer_id', 'trainer_name')->get();

        return view('company.master.subscription', compact('page_title', 'page_name', 'members', 'plans', 'trainers'));
    }

     // Auto-calculate end date based on plan duration text like "1 Month", "3 Months", "1 Year"
    private function calculateEndDate($startDate, $durationText)
    {
        $start = Carbon::parse($startDate);
        $durationText = strtolower(trim($durationText));

        preg_match('/(\d+)/', $durationText, $numMatch);
        $num = isset($numMatch[1]) ? (int) $numMatch[1] : 1;

        if (str_contains($durationText, 'year')) {
            return $start->copy()->addYears($num)->format('Y-m-d');
        } elseif (str_contains($durationText, 'week')) {
            return $start->copy()->addWeeks($num)->format('Y-m-d');
        } elseif (str_contains($durationText, 'day')) {
            return $start->copy()->addDays($num)->format('Y-m-d');
        }
        // default to months
        return $start->copy()->addMonths($num)->format('Y-m-d');
    }
     public function planDetails($id)
    {
        $plan = Planmodel::where('plan_id', $id)->first();
        if (!$plan) {
            return response()->json(['status' => 'error', 'message' => 'Plan not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $plan]);
    }
     public function storeOrUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'member_id'  => 'required',
                'plan_id'    => 'required',
                'start_date' => 'required|date',
                'amount_payable' => 'required|numeric'
            ]);

            $subscription_id = $request->subscription_id;
            $plan = Planmodel::where('plan_id', $request->plan_id)->first();

            if (!$plan) {
                return response()->json(['errors' => ['plan_id' => ['Selected plan not found.']]], 422);
            }

            $endDate = $this->calculateEndDate($request->start_date, $plan->duration);

            $data = [
                'member_id'      => $request->member_id,
                'plan_id'        => $request->plan_id,
                'trainer_id'     => $request->trainer_id ?: null,
                'start_date'     => $request->start_date,
                'end_date'       => $endDate,
                'amount_payable' => $request->amount_payable,
                'status'         => $request->status ?? 'Active',
            ];

            if ($subscription_id) {
                $subscription = SubscriptionModel::find($subscription_id);
                if (!$subscription) {
                    return response()->json(['status' => 'error', 'message' => 'Subscription not found.'], 404);
                }
                $data['updated_by'] = getUpdatedBy();
                $data['updated_at'] = now();
                $subscription->update($data);
                $message = 'Subscription updated successfully.';
            } else {
                $data['created_by'] = getCreatedBy();
                $data['created_at'] = now();
                SubscriptionModel::create($data);

                // When a new subscription is created, member becomes Active
                MemberModel::where('id', $request->member_id)->update(['status' => 'Active']);

                $message = 'Subscription created successfully.';
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => $message, 'end_date' => $endDate]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getList()
    {
        try {
            $query = SubscriptionModel::with(['member', 'plan', 'trainer'])
                ->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addColumn('member_name', fn($row) => $row->member->full_name ?? '-')
                ->addColumn('plan_name', fn($row) => $row->plan->plan_name ?? '-')
                ->addColumn('trainer_name', fn($row) => $row->trainer->trainer_name ?? '-')
                ->addColumn('status_badge', function ($row) {
                    $color = $row->status == 'Active' ? 'success' : ($row->status == 'Expired' ? 'danger' : 'secondary');
                    return '<span class="badge bg-' . $color . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-primary edit-subscription" data-id="' . $row->id . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-subscription" data-id="' . $row->id . '">
                            <i class="fas fa-trash"></i> Cancel
                        </button>
                    ';
                })
                ->addIndexColumn()
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
     public function edit($id)
    {
        try {
            $subscription = SubscriptionModel::find($id);
            if (!$subscription) {
                return response()->json(['status' => 'error', 'message' => 'Subscription not found'], 404);
            }
            return response()->json(['status' => 'success', 'data' => $subscription]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
     public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            $subscription = SubscriptionModel::find($request->subscription_id);
            if (!$subscription) {
                return response()->json(['status' => 'error', 'message' => 'Subscription not found.']);
            }
            $subscription->update([
                'status'     => 'Canceled',
                'updated_by' => getUpdatedBy(),
                'updated_at' => now()
            ]);
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Subscription canceled successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


}
