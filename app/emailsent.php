<?php

use App\Models\QuotationModel;

use Illuminate\Support\Facades\Mail;

if (!function_exists('prepareDocumentData')) {
    function prepareDocumentData($document_id, $model)
    {

        $withRelations = ['items', 'lead'];
        if (method_exists($model, 'address')) {
            $withRelations[] = 'address';
        }

        $document = $model::with($withRelations)->findOrFail($document_id);

        // $document = $model::with(['items', 'address', 'lead'])->findOrFail($document_id);

        $owner_state = get_company_details_one()->state;

        $subtotal = 0;
        $gst_total = 0;
        $grand_total = 0;
        $gst_percent_sum = 0;
        $item_count = $document->items->count();
        $isINR = $document->currency === 'INR';

        $billingState = $document->address ? $document->address->state : null;

        $isLocalState = $billingState && $billingState == $owner_state;

        foreach ($document->items as $item) {
            $unit_price = $isINR ? $item->unit_price : $item->unit_price_converted;
            $item_total = $isINR ? $item->total : $item->total_converted;

            $subtotal += $unit_price * $item->qty;
            $gst_total += $item->gst_amount;
            $gst_percent_sum += $item->gst_percent;
            $grand_total += $item_total;
        }

        $document->gst_percent = $item_count > 0 ? $gst_percent_sum / $item_count : 0;
        $document->subtotal = $subtotal;
        $document->gst_total = $gst_total;
        $document->grand_total = $grand_total;
        $document->is_local_state = $isLocalState;

        if ($isLocalState) {
            $document->cgst_amount = $gst_total / 2;
            $document->sgst_amount = $gst_total / 2;
            $document->igst_amount = 0;
            $document->cgst_percent = $document->gst_percent / 2;
            $document->sgst_percent = $document->gst_percent / 2;
            $document->igst_percent = 0;
        } else {
            $document->cgst_amount = 0;
            $document->sgst_amount = 0;
            $document->igst_amount = $gst_total;
            $document->cgst_percent = 0;
            $document->sgst_percent = 0;
            $document->igst_percent = $document->gst_percent;
        }

        return $document;
    }
}

if (!function_exists('sendDocumentEmail')) {
    function sendDocumentEmail($document, $validated, $type = 'quotation')
    {
        try {
            if ($type === 'quotation') {
                $document = prepareDocumentData($document->quotation_id, QuotationModel::class);
                $pdfLink = route('quotations.pdf', ['id' => encrypt($document->quotation_id)]);
                $emailView = 'emails.quotation';
                $documentType = 'quotation';
            }

            // elseif ($type === 'invoice') {
            //     $document = prepareDocumentData($document->invoice_id, \App\Models\InvoiceModel::class);
            //     $pdfLink = route('invoice.pdf', ['id' => encrypt($document->invoice_id)]);
            //     $emailView = 'emails.invoice';
            //     $documentType = 'invoice';
            // } 
            else {
                throw new \Exception('Unsupported document type for email sending.');
            }

            $emailData = [
                'subject' => $validated['subject'],
                'emails' => is_array($validated['email']) ? $validated['email'] : array_map('trim', explode(',', $validated['email'])),
                'email_message' => $validated['message'],
                'document' => $document,
                'pdf_link' => $pdfLink,
                'formatted_date' => now()->format('Y-m-d'),
                'type' => $type,
                'documentType' => $documentType,
            ];

            foreach ($emailData['emails'] as $email) {
                $email = trim($email);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                \Log::info('EMAIL DATA:', $emailData);
                \Log::info("Attempting to send email to: {$email}", [
        'subject' => $emailData['subject'],
        'from'    => config('mail.from.address'),
        'name'    => config('mail.from.name'),
        'pdf_link'=> $emailData['pdf_link'],
    ]);
                get_email_config();
                

                Mail::send($emailView, $emailData, function ($message) use ($emailData, $email) {
                    $message->from(config('mail.from.address'), config('mail.from.name'))
                        ->to($email)
                        ->subject($emailData['subject']);
                });
                
            }


            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
