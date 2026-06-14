<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OpenLead extends Controller
{
    public function openLeadList(Request $request)
    {
        $leads = DB::table('open_lead')->select('id', 'indiamart_data', 'created_date');

        return DataTables::of($leads)
            ->addIndexColumn()
            ->addColumn('query_info', function ($row) {
                $d = json_decode($row->indiamart_data, true)['RESPONSE'] ?? [];
                return '
                <b>Category:</b> ' . ($d['QUERY_MCAT_NAME'] ?? '') . '<br>
                <b>Product:</b> ' . ($d['QUERY_PRODUCT_NAME'] ?? '') . '<br>
                <b>Type:</b> ' . ($d['QUERY_TYPE'] ?? '') . '<br>
                <b>Time:</b> ' . ($d['QUERY_TIME'] ?? '') . '<br>
                <b>Duration:</b> ' . ($d['CALL_DURATION'] ?? '') . '<br>
                <b>Unique ID:</b> ' . ($d['UNIQUE_QUERY_ID'] ?? '') . '
            ';
            })
            ->addColumn('QUERY_MESSAGE', function ($row) {
                $d = json_decode($row->indiamart_data, true)['RESPONSE'] ?? [];
                return nl2br($d['QUERY_MESSAGE'] ?? '');
            })
            ->addColumn('sender_info', function ($row) {
                $d = json_decode($row->indiamart_data, true)['RESPONSE'] ?? [];
                return '
                <b>Name:</b> ' . ($d['SENDER_NAME'] ?? '') . '<br>
                <b>Company:</b> ' . ($d['SENDER_COMPANY'] ?? '-') . '<br>
                <b>Address:</b> ' . ($d['SENDER_ADDRESS'] ?? '') . '<br>
                <b>City:</b> ' . ($d['SENDER_CITY'] ?? '') . '<br>
                <b>State:</b> ' . ($d['SENDER_STATE'] ?? '') . '<br>
                <b>Country:</b> ' . ($d['SENDER_COUNTRY_ISO'] ?? '') . '
            ';
            })
            ->addColumn('sender_contact', function ($row) {
                $d = json_decode($row->indiamart_data, true)['RESPONSE'] ?? [];
                return '
                <b>Email:</b> ' . ($d['SENDER_EMAIL'] ?? '') . '<br>
                <b>Alt Email:</b> ' . ($d['SENDER_EMAIL_ALT'] ?? '-') . '<br>
                <b>Mobile:</b> ' . ($d['SENDER_MOBILE'] ?? '') . '<br>
                <b>Alt Mobile:</b> ' . ($d['SENDER_MOBILE_ALT'] ?? '-') . '<br>
                <b>Phone:</b> ' . ($d['SENDER_PHONE'] ?? '') . '<br>
                <b>Alt Phone:</b> ' . ($d['SENDER_PHONE_ALT'] ?? '-') . '
            ';
            })
            ->addColumn('receiver_info', function ($row) {
                $d = json_decode($row->indiamart_data, true)['RESPONSE'] ?? [];
                $url = $d['RECEIVER_CATALOG'] ?? '';
                return '
                <b>Catalog:</b> ' . ($url ? '<a href="' . $url . '" target="_blank">View</a>' : '-') . '<br>
                <b>Mobile:</b> ' . ($d['RECEIVER_MOBILE'] ?? '-') . '
            ';
            })
            ->addColumn('SUBJECT', function ($row) {
                $d = json_decode($row->indiamart_data, true)['RESPONSE'] ?? [];
                return $d['SUBJECT'] ?? '';
            })
            ->addColumn('Action', function ($row) {
                return '<button class="avatar avatar-md br-4 bg-success-transparent btn btn-sm ms-auto pickLead"
                        title="Pick the lead ?" data-id="' . $row->id . '">
                        <i class="fe fe-check"></i>
                    </button>';
            })
            ->rawColumns(['query_info', 'QUERY_MESSAGE', 'sender_info', 'sender_contact', 'receiver_info', 'Action'])
            ->make(true);
    }
}
