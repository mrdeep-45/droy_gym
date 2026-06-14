<?php

namespace App\Http\Controllers;

use App\Models\HsnGstMapModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Hsn_Gst extends Controller
{
    public function index()
    {
        $page_title = "HSN GST";
        return view('company/master/mapping/hsn_gst', compact('page_title'));
    }

    public function store(Request $request)
    {
        $isUpdate = $request->has('id');

        try {
            if ($isUpdate) {
                // Update existing record (only single entry)
                $hsnGst = HsnGstMapModel::findOrFail($request->id);

                $hsnGst->update([
                    'hsn_no' => $request->hsn, // This should be a single value
                    'gst_no' => $request->gst,
                    'pro_cat' => $request->product_category,
                    'remarks' => $request->remark,
                    'updated_by' => getCreatedBy(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'HSN/GST record updated successfully!'
                ]);
            } else {
                // Insert multiple records (arrays of values)
                $hsnCodes = $request->hsn;
                $gstRates = $request->gst;
                $productCategories = $request->product_category;
                $remarks = $request->remark;

                foreach ($hsnCodes as $index => $hsn) {
                    HsnGstMapModel::create([
                        'hsn_no' => $hsn,
                        'gst_no' => $gstRates[$index] ?? null,
                        'pro_cat' => $productCategories[$index] ?? null,
                        'remarks' => $remarks[$index] ?? null,
                        'status' => 0,
                        'created_by' => getCreatedBy(),
                        'updated_by' => getCreatedBy(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'HSN/GST records added successfully!'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage()
            ], 500);
        }
    }




    public function list()
    {
        $hsn_gst = HsnGstMapModel::select(['hg_id', 'hsn_no', 'gst_no', 'pro_cat', 'remarks'])
            ->orderBy('hg_id', 'desc')
            ->get();

        return DataTables::of($hsn_gst)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '
                <div class="d-flex justify-content-center">
                    <button class="btn btn-sm btn-primary me-2 edit-btn" 
                        data-id="' . $row->hg_id . '"
                        data-hsn="' . $row->hsn_no . '"
                        data-gst="' . $row->gst_no . '"
                        data-category="' . $row->pro_cat . '"
                        data-remark="' . $row->remarks . '">
                        <i class="bi bi-pencil"></i> 
                    </button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row->hg_id . '" data-hsn="' . $row->hsn_no . '"><i class="bi bi-trash"></i></button>

                </div>
            ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function destroy(Request $request)
    {
        try {
            $hsn = HsnGstMapModel::findOrFail($request->id); // No decryption
            $hsn->delete();

            return response()->json(['success' => true, 'message' => 'HSN/GST record deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }
}
