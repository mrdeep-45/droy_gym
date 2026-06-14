<?php

namespace App\Http\Controllers;

use App\Models\SupplierModel;
use App\Models\RawmaterialModel;
use App\Models\UnitModel;
use App\Models\AltUnitModel;
use App\Models\Purchase_order_Model;
use App\Models\Purchase_items_Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class Purchase extends Controller
{
    //
    public function index()
    {
        $page_title = 'PO Generate';
        $page_name = 'PO Generate';
        $supplier = SupplierModel::active()->get();
        $raw_material = RawmaterialModel::join('tbl_gst', 'tbl_gst.gst_id', '=', 'tbl_raw_material.gst_id')
            ->select('tbl_raw_material.*', 'tbl_gst.gst_no')
            ->where('tbl_raw_material.status',0)
            ->get();
        $poNo = Purchase_order_Model::generatePoNo();
        $unit = UnitModel::active()->get();
        $altunit = AltUnitModel::active()->get();
       $counts = Purchase_order_Model::select('po_status', DB::raw('count(*) as total'))
        ->where('status', 0)
        ->groupBy('po_status')
        ->pluck('total', 'po_status');

        $open_count = $counts[0] ?? 0;
        $received_count = $counts[1] ?? 0;
        $partial_received_count = $counts[2] ?? 0;

        return view('company/po/po_generate', compact('page_title', 'page_name', 'supplier', 'raw_material', 'unit', 'altunit', 'poNo','open_count','received_count','partial_received_count'));
    }

public function generatePoNo()
{
    $poNo = Purchase_order_Model::generatePoNo();
    return response()->json(['po_no' => $poNo]);
}
public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
            'supplier_id'      => 'required|exists:tbl_supplier,supplier_id',
            'po_date'          => 'required|date',
            'quotation_no'     => 'required|string',
            'quotation_date'   => 'required|date',
            'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
            'cgst'             => 'nullable|numeric',
            'sgst'             => 'nullable|numeric',
            'igst'             => 'nullable|numeric',
            'total_with_gst'   => 'nullable|numeric',
            'subtotal'        => 'nullable|numeric',
            'ref_name'         => 'nullable|string',
            'payment_terms'    => 'nullable|string',
            'delivery_time'    => 'nullable|string',
            'transportation'   => 'nullable|string',
        ];

        $customAttributes = [
            'supplier_id' => 'Supplier',
            'po_date' => 'PO Date',
            'quotation_no' => 'Quotation No.',
            'quotation_date' => 'Quotation Date',
        ];

        $validator = Validator::make($request->all(), $rules, [], $customAttributes);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $firstField = array_key_first($errors);
            $firstMessage = $errors[$firstField][0];

            return response()->json([
                'success' => false,
                'field' => $firstField,
                'message' => $firstMessage,
            ], 422);
        }

        $validated = $validator->validated();
        $validated['po_no'] = Purchase_order_Model::generatePoNo();
        $validated['created_by'] = getCreatedBy();
        $validated['created_at'] = now();

        if (!empty($validated['po_date'])) {
            $validated['po_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['po_date'])->format('Y-m-d');
        }
        if (!empty($validated['quotation_date'])) {
            $validated['quotation_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['quotation_date'])->format('Y-m-d');
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('assets/uploads/purchase_attach');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $filename);
            $validated['attachment'] = '/purchase_attach/' . $filename; 
        }

        $po = Purchase_order_Model::create($validated);
        $materials = json_decode($request->materials, true);

        if (is_array($materials) && count($materials)) {
            foreach ($materials as $item) {
                Purchase_items_Model::create([
                    'po_id'       => $po->po_id,
                    'po_no'       => $po->po_no,
                    'rm_id'       => $item['material_id'],
                    'unit_id'     => $item['unit_id'],
                    'gst_id'      => $item['gst_percent'],
                    'qty'         => $item['qty'],
                    'price'       => $item['price'],
                    'total'       => $item['total'],
                    'created_by'  => getCreatedBy(),
                    'created_at'  => now(),
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order created successfully.',
            'po_id' => $po->po_id,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to create purchase order.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function list()
{
    $purchase_order = Purchase_order_Model::select([
            'tbl_purchase_order.po_id',
            'tbl_purchase_order.po_no',
            'tbl_purchase_order.po_date',
            'tbl_purchase_order.quotation_no',
            'tbl_purchase_order.quotation_date',
            'tbl_purchase_order.total_with_gst',
            'tbl_purchase_order.po_status',
            'tbl_supplier.name',
        ])
        ->join('tbl_supplier', 'tbl_supplier.supplier_id', '=', 'tbl_purchase_order.supplier_id')
        ->where('tbl_purchase_order.status', '0')
        ->orderBy('tbl_purchase_order.po_id', 'desc');
    return DataTables::of($purchase_order)
        ->addIndexColumn()

        ->editColumn('po_date', function ($row) {
            return \Carbon\Carbon::parse($row->po_date)->format('d-m-Y');
        })

        ->editColumn('quotation_date', function ($row) {
            return \Carbon\Carbon::parse($row->quotation_date)->format('d-m-Y');
        })

        ->editColumn('total_with_gst', function ($row) {
            return number_format($row->total_with_gst, 2);
        })

        ->addColumn('po_status_label', function ($row) {
            switch ($row->po_status) {
                case 0:
                    return '<span class="badge bg-info">Open</span>';
                case 1:
                    return '<span class="badge bg-success">Received</span>';
                case 2:
                    return '<span class="badge bg-warning text-dark">Partial Received</span>';
                default:
                    return '<span class="badge bg-secondary">-</span>';
            }
        })

        ->addColumn('action', function ($purchase_order) {
            return '
                <div class="">
                    <a href="#" class="btn btn-sm btn-info view-btn" 
                        data-id="' . $purchase_order->po_id . '" 
                        data-bs-toggle="modal" 
                        data-bs-target="#purchaseOrderModal" 
                        title="Show Purchase Order"><i class="fe fe-eye"></i></a>
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $purchase_order->po_id . '"><i class="bx bx-edit"></i></button>
                    <button 
                        class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . $purchase_order->po_id . '" 
                        data-name="' . $purchase_order->po_no . '" 
                        data-module="purchase_order"
                        data-table="podata"
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal"
                    >
                      <i class="bx bx-trash"></i>
                    </button>
                     <a href="'. route('popdf', $purchase_order->po_id ).'" class="btn btn-sm btn-success" target="_blank"><i class="fe fe-file"></i></a>
                </div>
            ';
        })

        ->rawColumns(['action', 'po_status_label']) 
        ->make(true);
}


public function get_data(Request $request){
     $id = $request->input("id");
     $po_order = Purchase_order_Model::where("po_id",$id)->where("status",0)->first();
     $po_items = Purchase_items_Model::with(['supplier','raw_material', 'unit'])
                ->where('po_id', $id)
                ->where("status",0)
                ->get();

    return view('company/po/po_order_view_modal', compact('po_order','po_items'));
}

public function po_pdf($po_id)
{
    $po = Purchase_order_Model::join('tbl_supplier', 'tbl_purchase_order.supplier_id', '=', 'tbl_supplier.supplier_id')
    ->join('mst_state', 'mst_state.state_id', '=', 'tbl_supplier.state_id')
    ->select('tbl_purchase_order.*', 'tbl_supplier.name as supplier_name', 'tbl_supplier.email as supplier_email','tbl_supplier.address as address','tbl_supplier.contact_no','tbl_supplier.gst_no','mst_state.state_name') // add any fields you need
    ->where('tbl_purchase_order.po_id', $po_id)
    ->first();
    $items = Purchase_items_Model::join('tbl_raw_material', 'tbl_raw_material.rm_id', '=', 'tbl_purchase_items.rm_id')
        ->leftJoin('tbl_unit', 'tbl_unit.unit_id', '=', 'tbl_purchase_items.unit_id')
        ->leftJoin('tbl_alternate_unit', 'tbl_alternate_unit.alt_unit_id', '=', 'tbl_purchase_items.unit_id')
        ->where('tbl_purchase_items.po_id', $po_id)
        ->select(
            'tbl_purchase_items.*',
            'tbl_raw_material.name as raw_material',
            DB::raw('COALESCE(tbl_unit.unit, tbl_alternate_unit.unit) as unit')
        )
        ->get();

    $pdf = Pdf::loadView('company/po/po_pdf', compact('po','items')); 
    return $pdf->stream("PO_$po_id.pdf"); 
}

public function invoice_pdf()
{
    $pdf = Pdf::loadView('company/dispatch/invoice_pdf'); 
    return $pdf->stream("PO_.pdf"); 
}
}
