<?php

namespace App\Http\Controllers;

use App\Models\DealModel;
use App\Models\LeadmanageModel;
use App\Models\ProductModel;
use App\Models\QuotationItemModal;
use App\Models\QuotationModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Mail;

class Quotation extends Controller
{
    protected $actual_path;
    public function __construct()
    {
        $this->actual_path = config('app.actual_url') . '/uploads/';
    }
    public function index()
    {
        $page_title = 'Quotation';
        $page_name = 'Quotation';

        $source = DB::table('mst_source')->select('source_id', 'source_name')->where('status', 0)->get();
        $service = DB::table('mst_service_manage')->select('service_id', 'service_name', 'service_desc')->where('status', 0)->get();

        return view('company/lead/quotation', compact('page_title', 'page_name', 'source', 'service'));
    }

    public function get_deal_info($id)
    {
        $deal = DealModel::findOrFail($id);
        $lead = LeadmanageModel::where('lead_id', $deal->lead_id)->first();

        return response()->json([
            'deal' => $deal,
            'lead' => $lead,
        ]);
    }
    public function get_products()
    {
        $products = ProductModel::active()
            ->select('product_id', 'prod_name', 'hsn_code', 'gst')
            ->get();

        foreach ($products as $product) {
            $gstMapping = DB::table('tbl_hsn_gst_mapping')
                ->where('hsn_no', $product->hsn_code)
                ->first();

            $product->gst_percentage = $gstMapping->gst_no ?? 0;
        }

        return response()->json(['products' => $products]);
    }

    public function pdf_open()
    {
        $pdf = Pdf::loadView('pdf_sample');
        return $pdf->stream('sample.pdf');
    }
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'deal_id' => 'required|exists:tbl_deal,deal_id',
            'lead_id' => 'required|exists:lead_manage,lead_id',
            'subject' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
            'quotation_terms' => 'required|string',
            'quotation_type' => 'required|in:Export,Domestic',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:tbl_product,product_id',
            'products.*.qty' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.total' => 'required|numeric|min:0',
            'products.*.gstpercentage' => 'required|numeric|min:0', // Added validation for gstpercentage
            'products.*.gst' => 'required|numeric|min:0', // Added validation for gst
            'grand_total' => 'required|numeric|min:0',
            'grand_total_converted' => 'required|numeric|min:0',
        ]);


        try {
            // Create the quotation
            $quotation = QuotationModel::create([
                'quotation_no' => QuotationModel::quat_gen($validated['quotation_type']),
                'lead_id' => $validated['lead_id'],
                'deal_id' => $validated['deal_id'],
                'customer_name' => LeadmanageModel::find($validated['lead_id'])->company_name,
                'quotation_type' => $validated['quotation_type'],
                'quotation_date' => now(),
                'terms' => $validated['quotation_terms'],
                'currency' => $validated['currency'],
                'exchange_rate' => $validated['exchange_rate'],
                'grand_total' => $validated['grand_total'],
                'grand_total_converted' => $validated['grand_total_converted'],
                'created_by' => getCreatedBy(),
                'updated_by' => getCreatedBy(),
            ]);

            // Create quotation items
            foreach ($validated['products'] as $product) {
                QuotationItemModal::create([
                    'quotation_id' => $quotation->quotation_id,
                    'product_id' => $product['product_id'],
                    'qty' => $product['qty'],
                    'unit_price' => $product['unit_price'],
                    'unit_price_converted' => $product['unit_price'] * $validated['exchange_rate'],
                    'total' => $product['total'],
                    'gst_percent' => $product['gstpercentage'], // Use per-product gstpercentage
                    'gst_amount' => $product['gst'], // Use per-product gst
                    'total_converted' => $product['total'] * $validated['exchange_rate'],
                    'created_by' => getCreatedBy(),
                    'updated_by' => getCreatedBy(),
                ]);
            }
            sendDocumentEmail($quotation, $validated, 'quotation');

            return response()->json([
                'success' => true,
                'message' => 'Quotation created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null,
            ], 500);
        }
    }



    public function list()
    {
        $query = QuotationModel::with(['items'])->select('tbl_quotation.*')->orderBy('quotation_id', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('product_details', function ($quotation) {
                $products = [];
                $isINR = $quotation->currency == 'INR';
                $currencySymbol = $quotation->currency == 'INR' ? '₹' : $quotation->currency;

                foreach ($quotation->items as $item) {
                    $product = get_product_details($item->product_id);

                    $line = "<strong>{$product['prod_name']}</strong><br>";
                    $line .= "Qty: {$item->qty}<br>";
                    $line .= "Unit Price: " . $currencySymbol . " " . ($isINR ? $item->unit_price : $item->unit_price_converted) . "<br>";
                    $line .= "Total: " . $currencySymbol . " " . ($isINR ? $item->total : $item->total_converted);

                    $products[] = "<div style=''>{$line}</div>";
                }

                return implode('', $products);
            })
            ->addColumn('total', function ($quotation) {
                $isINR = $quotation->currency == 'INR';
                $currencySymbol = $quotation->currency == 'INR' ? '₹' : $quotation->currency;
                $total = $isINR ? $quotation->items->sum('total') : $quotation->items->sum('total_converted');
                return $currencySymbol . " " . $total;
            })
            ->addColumn('action', function ($quotation) {
                $viewBtn = '<a href="' . route('quotations.revised', encrypt($quotation->quotation_id)) . '" class="btn btn-sm btn-primary d-none">Revised</a>';
                $sendBtn = '<a target="_blank" href="' . route('quotations.pdf', encrypt($quotation->quotation_id)) . '" class="btn btn-sm btn-success ml-1">PDF</a>';

                $lead = LeadManageModel::where('lead_id', $quotation->lead_id)->first();
                $email = $lead ? $lead->email : '';

                $sendSent = '<button type="button" class="btn btn-sm btn-warning ml-1 email-quotation" data-id="' . $quotation->quotation_id . '" data-email="' . $email . '">Email</button>';

                return $viewBtn . ' ' . $sendBtn . ' ' . $sendSent;
            })
            ->rawColumns(['product_details', 'action'])
            ->make(true);
    }
    // public function quotation_pdf($id)
    // {
    //     $quotation_id = decrypt($id);
    //     $quotation = QuotationModel::with(['items', 'lead'])->findOrFail($quotation_id);

    //     // Initialize variables for calculations
    //     $subtotal = 0;
    //     $gst_total = 0;
    //     $grand_total = 0;
    //     $gst_percent_sum = 0;
    //     $item_count = $quotation->items->count();
    //     $isINR = $quotation->currency == 'INR';
        
    //     $isLocalState = $quotation->lead && $quotation->lead->state_id == '12';

    //     // Calculate totals and GST
    //     foreach ($quotation->items as $item) {
    //         $unit_price = $isINR ? $item->unit_price : $item->unit_price_converted;
    //         $item_total = $isINR ? $item->total : $item->total_converted;
    //         $subtotal += $unit_price * $item->qty;
    //         $gst_total += $item->gst_amount;
    //         $gst_percent_sum += $item->gst_percent;
    //         $grand_total += $item_total;
    //     }

    //     // Calculate average GST percentage (if items exist)
    //     $quotation->gst_percent = $item_count > 0 ? $gst_percent_sum / $item_count : 0;

    //     // Assign calculated totals to quotation object
    //     $quotation->subtotal = $subtotal;
    //     $quotation->gst_total = $gst_total;
    //     $quotation->grand_total = $grand_total;
    //     $quotation->is_local_state = $isLocalState;

    //     // Split GST based on state_id
    //     if ($isLocalState) {
    //         $quotation->cgst_amount = $gst_total / 2;
    //         $quotation->sgst_amount = $gst_total / 2;
    //         $quotation->igst_amount = 0;
    //         $quotation->cgst_percent = $quotation->gst_percent / 2; // Split GST percentage
    //         $quotation->sgst_percent = $quotation->gst_percent / 2; // Split GST percentage
    //         $quotation->igst_percent = 0;
    //     } else {
    //         $quotation->cgst_amount = 0;
    //         $quotation->sgst_amount = 0;
    //         $quotation->igst_amount = $gst_total;
    //         $quotation->cgst_percent = 0;
    //         $quotation->sgst_percent = 0;
    //         $quotation->igst_percent = $quotation->gst_percent;
    //     }

    //     $quotation_no = str_replace(['/', '\\'], '_', $quotation->quotation_no);
    //     $customer_name = str_replace(['/', '\\'], '_', $quotation->customer_name);
    //     $filename = 'quotation_' . $quotation_no . '_' . $customer_name . '.pdf';

    //     $pdf = Pdf::loadView('company/pdf/quotation_pdf', compact('quotation'))
    //         ->setPaper('a4', 'portrait');

    //     return $pdf->stream($filename);
    // }

    public function quotation_pdf($id)
    {
        $quotation_id = decrypt($id);
        $quotation = prepareDocumentData($quotation_id, QuotationModel::class);

        $quotation_no = str_replace(['/', '\\'], '_', $quotation->quotation_no);
        $customer_name = str_replace(['/', '\\'], '_', $quotation->customer_name);
        $filename = 'quotation_' . $quotation_no . '_' . $customer_name . '.pdf';

        $pdf = Pdf::loadView('company/pdf/quotation_pdf', compact('quotation'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    public function quotation_revised($id)
    {
        dd($id);
    }

    public function send_email_direct(Request $request)
    {
        $rules = [
            'emails' => 'required|array',
            'emails.*' => 'email',
            'type' => 'required|in:quotation,invoice',
        ];

        if ($request->type === 'quotation') {
            $rules['quotation_id'] = 'required|exists:tbl_quotation,quotation_id';
        }

        $request->validate($rules);

        try {
            if ($request->type === 'quotation') {
                $document = QuotationModel::findOrFail($request->quotation_id);
                $subject = 'Quotation - ' . $document->quotation_no;
            } 
            // elseif ($request->type === 'invoice') {
            //     $document = InvoiceModel::findOrFail($request->quotation_id); // using same field name
            //     $subject = 'Invoice - ' . $document->invoice_no;
            // }

            $validated = [
                'subject' => $subject,
                'email' => $request->emails,
                'message' => 'Please find the attached ' . $request->type . '.',
            ];

            sendDocumentEmail($document, $validated, $request->type);
            \Log::info('Emails attempted for sending:', $validated['email']);

            return response()->json([
                'success' => true,
                'message' => ucfirst($request->type) . ' email sent successfully to ' . count($request->emails) . ' recipients.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send ' . $request->type . ' email.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function getGstByHsn(Request $request)
    {
        $hsnNo = $request->input('hsn_no');

        if (!$hsnNo) {
            return response()->json(['success' => false, 'message' => 'HSN number is required.']);
        }

        $gstRecord = DB::table('tbl_hsn_gst_mapping')
            ->where('hsn_no', $hsnNo)
            ->first();

        if ($gstRecord) {
            return response()->json(['success' => true, 'gst_no' => $gstRecord->gst_no]);
        } else {
            return response()->json(['success' => false, 'message' => 'GST not found for given HSN.']);
        }
    }


    public function show($id)
    {
        $quotation = QuotationModel::with(['items.product', 'lead', 'deal'])->findOrFail($id); // Assuming relationships for product and deal are defined

        return response()->json([
            'success' => true,
            'data' => [
                'deal_name' => $quotation->deal->deal_name ?? 'N/A',
                'amount' => $quotation->deal->amount ?? 0,
                'closing_date' => $quotation->deal->closing_date ?? null,
                'lead_name' => $quotation->lead->name ?? 'N/A',
                'lead_email' => $quotation->lead->email ?? 'N/A',
                'lead_phone' => $quotation->lead->phone ?? 'N/A',
                'lead_state' => $quotation->lead->state_id ?? '',
                'quotation_type' => $quotation->quotation_type,
                'currency' => $quotation->currency,
                'exchange_rate' => $quotation->exchange_rate,
                'gst_exemption' => $quotation->gst_exemption ?? false,
                'subject' => $quotation->subject ?? '',
                'email' => $quotation->email ?? '',
                'message' => $quotation->message ?? '',
                'terms' => $quotation->terms ?? '',
                'products' => $quotation->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? '',
                        'hsn_code' => $item->product->hsn_code ?? '',
                        'qty' => $item->qty,
                        'unit_price' => $item->unit_price,
                        'gst_percent' => $item->gst_percent,
                        'gst_amount' => $item->gst_amount,
                        'total' => $item->total,
                    ];
                })
            ]
        ]);
    }
}
