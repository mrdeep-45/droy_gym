<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Complaint extends Controller
{
    //

    public function index()
    {
        $page_title = 'Compalint Logging';
        $page_name = 'Compalint Logging';
        return view('company/sales_service/complaint_logg', compact('page_title', 'page_name'));
    }
}
