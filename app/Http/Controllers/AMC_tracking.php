<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AMC_tracking extends Controller
{
    //
    public function index()
    {
        $page_title = 'AMC Tracking';
        $page_name = 'AMC Tracking';
        return view('company/sales_service/amc_tracking', compact('page_title', 'page_name'));
    }
}
