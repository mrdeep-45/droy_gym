<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MemberModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
class MemberController extends Controller
{
    //
    public function index()
    {
        $page_title = 'Member';
        $page_name  = 'Member';

        return view('company.master.member', compact('page_title', 'page_name'));
    }

     private function generateMemberNumber()
    {
        $last = MemberModel::orderBy('id', 'desc')->first();
        $nextNumber = 1001;

        if ($last && $last->member_number) {
            $parts = explode('-', $last->member_number);
            $lastNum = (int) end($parts);
            $nextNumber = $lastNum + 1;
        }

        return 'GYM-' . $nextNumber;
    }

    public function storeOrUpdate(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'full_name'    => 'required',
                'phone'        => 'required',
                'joining_date' => 'required|date',
                'email'        => 'nullable|email'
            ]);

            $member_id = $request->member_id;

            // Phone uniqueness among active members
            $existsPhone = MemberModel::where('phone', $request->phone)
                ->where('status', '!=', 'Deleted')
                ->when($member_id, function ($q) use ($member_id) {
                    $q->where('id', '!=', $member_id);
                })
                ->exists();

            if ($existsPhone) {
                return response()->json([
                    'errors' => [
                        'phone' => ['This phone number is already registered.']
                    ]
                ], 422);
            }

            // Email uniqueness (only if provided)
            if ($request->email) {
                $existsEmail = MemberModel::where('email', $request->email)
                    ->where('status', '!=', 'Deleted')
                    ->when($member_id, function ($q) use ($member_id) {
                        $q->where('id', '!=', $member_id);
                    })
                    ->exists();

                if ($existsEmail) {
                    return response()->json([
                        'errors' => [
                            'email' => ['This email is already registered.']
                        ]
                    ], 422);
                }
            }

            $photoName = null;
            if ($request->hasFile('m_photo')) {
                $file = $request->file('m_photo');
                $photoName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/members'), $photoName);
            }

            if ($member_id) {
                $member = MemberModel::find($member_id);
                if (!$member) {
                    return response()->json(['status' => 'error', 'message' => 'Member not found.'], 404);
                }

                $data = [
                    'full_name'    => $request->full_name,
                    'email'        => $request->email,
                    'phone'        => $request->phone,
                    'gender'       => $request->gender,
                    'dob'          => $request->dob,
                    'joining_date' => $request->joining_date,
                    'status'       => $request->status ?? $member->status,
                    'updated_by'   => getUpdatedBy(),
                    'updated_at'   => now()
                ];

                if ($photoName) {
                    $data['m_photo'] = $photoName;
                }

                $member->update($data);
                $message = 'Member updated successfully.';
            } else {
                MemberModel::create([
                    'member_number' => $this->generateMemberNumber(),
                    'full_name'     => $request->full_name,
                    'email'         => $request->email,
                    'phone'         => $request->phone,
                    'gender'        => $request->gender,
                    'dob'           => $request->dob,
                    'joining_date'  => $request->joining_date,
                    'm_photo'       => $photoName,
                    'status'        => 'Active',
                    'created_by'    => getCreatedBy(),
                    'created_at'    => now()
                ]);
                $message = 'Member registered successfully.';
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
            $query = MemberModel::select([
                'id', 'member_number', 'full_name', 'email', 'phone',
                'gender', 'joining_date', 'm_photo', 'status', 'created_at'
            ])
            ->where('status', '!=', 'Deleted')
            ->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addColumn('photo', function ($row) {
                    if ($row->m_photo) {
                        return '<img src="' . asset('uploads/members/' . $row->m_photo) . '" width="40" class="rounded-circle">';
                    }
                    return '-';
                })
                ->addColumn('status_badge', function ($row) {
                    $color = $row->status == 'Active' ? 'success' : ($row->status == 'Suspended' ? 'warning' : 'secondary');
                    return '<span class="badge bg-' . $color . '">' . $row->status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-primary edit-member" data-id="' . $row->id . '">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-member" data-id="' . $row->id . '">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    ';
                })
                ->addIndexColumn()
                ->rawColumns(['photo', 'status_badge', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $member = MemberModel::find($id);

            if (!$member) {
                return response()->json(['status' => 'error', 'message' => 'Member not found'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $member]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error fetching member: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            $member = MemberModel::find($request->member_id);

            if (!$member) {
                return response()->json(['status' => 'error', 'message' => 'Member not found.']);
            }

            $member->update([
                'status'     => 'Deleted',
                'updated_by' => getUpdatedBy(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Member removed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to delete member: ' . $e->getMessage()]);
        }
    }
}
