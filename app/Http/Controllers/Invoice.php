<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Invoice extends Controller
{
    //

     public function index()
    {
        $page_title = 'Invoice';
        $page_name = 'Invoice';
        return view('company/invoice/invoice', compact('page_title', 'page_name'));
    }
}
