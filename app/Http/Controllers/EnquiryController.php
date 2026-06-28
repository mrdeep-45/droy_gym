<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EnquiryModel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class EnquiryController extends Controller
{
    //
     public function index()
    {
        $page_title = 'Enquiries';
        $page_name  = 'Enquiry';

        return view('company.master.enquiry', compact('page_title', 'page_name'));
    }

    public function storeOrUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'full_name'    => 'required',
                'phone'        => 'required',
                'enquiry_date' => 'required|date',
                'source'       => 'required'
            ]);

            $enquiry_id = $request->enquiry_id;

            $data = [
                'full_name'      => $request->full_name,
                'phone'          => $request->phone,
                'email'          => $request->email,
                'enquiry_date'   => $request->enquiry_date,
                'source'         => $request->source,
                'status'         => $request->status ?? 'Pending',
                'follow_up_date' => $request->follow_up_date,
                'remarks'        => $request->remarks,
            ];

            if ($enquiry_id) {
                $enquiry = EnquiryModel::find($enquiry_id);
                if (!$enquiry) {
                    return response()->json(['status' => 'error', 'message' => 'Enquiry not found.'], 404);
                }
                $data['updated_by'] = getUpdatedBy();
                $data['updated_at'] = now();
                $enquiry->update($data);
                $message = 'Enquiry updated successfully.';
            } else {
                $data['created_by'] = getCreatedBy();
                $data['created_at'] = now();
                EnquiryModel::create($data);
                $message = 'Enquiry logged successfully.';
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
            $query = EnquiryModel::select([
                'id', 'full_name', 'phone', 'email', 'enquiry_date',
                'source', 'status', 'follow_up_date', 'remarks'
            ])->orderBy('enquiry_date', 'desc');

            return DataTables::of($query)
                ->addColumn('status_badge', function ($row) {
                    $color = $row->status == 'Converted' ? 'success' : ($row->status == 'Lost' ? 'danger' : 'warning');
                    return '<span class="badge bg-' . $color . '">' . $row->status . '</span>';
                })
                ->addColumn('follow_up_alert', function ($row) {
                    if ($row->follow_up_date && $row->status == 'Pending') {
                        $isToday = Carbon::parse($row->follow_up_date)->isToday();
                        $isPast = Carbon::parse($row->follow_up_date)->isPast();
                        if ($isToday) return '<span class="badge bg-info">' . $row->follow_up_date . ' (Today)</span>';
                        if ($isPast) return '<span class="badge bg-danger">' . $row->follow_up_date . ' (Overdue)</span>';
                        return $row->follow_up_date;
                    }
                    return $row->follow_up_date ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-primary edit-enquiry" data-id="' . $row->id . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-enquiry" data-id="' . $row->id . '">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    ';
                })
                ->addIndexColumn()
                ->rawColumns(['status_badge', 'follow_up_alert', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $enquiry = EnquiryModel::find($id);
            if (!$enquiry) {
                return response()->json(['status' => 'error', 'message' => 'Enquiry not found'], 404);
            }
            return response()->json(['status' => 'success', 'data' => $enquiry]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            $enquiry = EnquiryModel::find($request->enquiry_id);
            if (!$enquiry) {
                return response()->json(['status' => 'error', 'message' => 'Enquiry not found.']);
            }
            $enquiry->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Enquiry removed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
