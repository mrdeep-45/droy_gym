<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentModel;
use App\Models\SubscriptionModel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    //
     public function index()
    {
        $page_title = 'Payments';
        $page_name  = 'Payment';

        $subscriptions = SubscriptionModel::with(['member', 'plan'])
            ->where('status', 'Active')
            ->get();

        return view('company.master.payment', compact('page_title', 'page_name', 'subscriptions'));
    }
     public function subscriptionDetails($id)
    {
        $sub = SubscriptionModel::with(['member', 'plan'])->find($id);
        if (!$sub) {
            return response()->json(['status' => 'error', 'message' => 'Subscription not found'], 404);
        }

        $totalPaid = PaymentModel::where('subscription_id', $id)->sum('amount_paid');
        $balance = $sub->amount_payable - $totalPaid;

        return response()->json([
            'status' => 'success',
            'data' => [
                'member_name'    => $sub->member->full_name ?? '-',
                'plan_name'      => $sub->plan->plan_name ?? '-',
                'amount_payable' => $sub->amount_payable,
                'total_paid'     => $totalPaid,
                'balance'        => $balance
            ]
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'subscription_id' => 'required',
                'amount_paid'     => 'required|numeric|min:0.01',
                'payment_date'    => 'required|date',
                'payment_method'  => 'required'
            ]);

            $payment_id = $request->payment_id;

            $data = [
                'subscription_id' => $request->subscription_id,
                'amount_paid'      => $request->amount_paid,
                'payment_date'     => $request->payment_date,
                'payment_method'   => $request->payment_method,
                'transaction_id'   => $request->transaction_id,
                'payment_status'   => $request->payment_status ?? 'Paid',
            ];

            if ($payment_id) {
                $payment = PaymentModel::find($payment_id);
                if (!$payment) {
                    return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
                }
                $data['updated_by'] = getUpdatedBy();
                $data['updated_at'] = now();
                $payment->update($data);
                $message = 'Payment updated successfully.';
            } else {
                $data['created_by'] = getCreatedBy();
                $data['created_at'] = now();
                PaymentModel::create($data);
                $message = 'Payment recorded successfully.';
            }

            // Recalculate subscription payment status (Partial vs fully covered)
            $sub = SubscriptionModel::find($request->subscription_id);
            if ($sub) {
                $totalPaid = PaymentModel::where('subscription_id', $sub->id)->sum('amount_paid');
                if ($totalPaid < $sub->amount_payable) {
                    // leave subscription status as-is; payment_status per-row already tracks Partial/Pending
                }
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
            $query = PaymentModel::with(['subscription.member', 'subscription.plan'])
                ->orderBy('payment_date', 'desc');

            return DataTables::of($query)
                ->addColumn('member_name', fn($row) => $row->subscription->member->full_name ?? '-')
                ->addColumn('plan_name', fn($row) => $row->subscription->plan->plan_name ?? '-')
                ->addColumn('status_badge', function ($row) {
                    $color = $row->payment_status == 'Paid' ? 'success' : ($row->payment_status == 'Partial' ? 'warning' : 'danger');
                    return '<span class="badge bg-' . $color . '">' . $row->payment_status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <a href="' . route('payment.receipt', $row->id) . '" class="btn btn-sm btn-secondary" target="_blank">
                            <i class="fas fa-file-pdf"></i> Receipt
                        </a>
                        <button class="btn btn-sm btn-primary edit-payment" data-id="' . $row->id . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-payment" data-id="' . $row->id . '">
                            <i class="fas fa-trash"></i> Delete
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
            $payment = PaymentModel::find($id);
            if (!$payment) {
                return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
            }
            return response()->json(['status' => 'success', 'data' => $payment]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            $payment = PaymentModel::find($request->payment_id);
            if (!$payment) {
                return response()->json(['status' => 'error', 'message' => 'Payment not found.']);
            }
            $payment->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Payment record removed.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Uses the pdf skill / dompdf in your real project - simple stub here
    public function downloadReceipt($id)
    {
        $payment = PaymentModel::with(['subscription.member', 'subscription.plan'])->find($id);
        if (!$payment) {
            abort(404);
        }
        // return PDF::loadView('company.receipts.payment', compact('payment'))->download('receipt.pdf');
        return view('company.receipts.payment', compact('payment'));
    }
}
