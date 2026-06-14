<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Country1 extends Controller
{
    //
    public function index()
    {
        $page_title = 'Country ';
        $page_name = 'country';
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company/master/country/country', compact('page_title', 'page_name'));
    }
     public function list(Request $request)
    {
        if ($request->ajax()) {
            $query = Country::query();

            // For DataTables server-side search/filter
            if ($search = $request->input('search.value')) {
                $query->where('country_name', 'like', '%' . $search . '%');
            }

            $totalRecords = $query->count();

            // Ordering
            $orderColumnIndex = $request->input('order.0.column');
            $orderColumnName = $request->input("columns.$orderColumnIndex.name") ?? 'created_at';
            $orderDir = $request->input('order.0.dir', 'desc');

            $query->orderBy($orderColumnName, $orderDir);

            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);

            $countries = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($countries as $key => $country) {
                $data[] = [
                    'DT_RowIndex' => $start + $key + 1,
                    'country_name' => $country->country_name,
                    'status' => $country->status ? 'Active' : 'Inactive',
                    'created_at' => $country->created_at->format('Y-m-d'),
                    'action' => view('country.partials.action_buttons', compact('country'))->render(),
                ];
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
            ]);
        }

        // If not AJAX, show some fallback or redirect
        abort(404);
    }

    /**
     * Store a newly created country in database
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_name' => 'required|string|max:255|unique:countries,country_name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'field' => 'country_name',
                'message' => $validator->errors()->first('country_name'),
            ], 422);
        }

        $country = new Country();
        $country->country_name = $request->input('country_name');
        $country->status = 1; // default active
        $country->save();

        return response()->json([
            'message' => 'Country created successfully!',
            'country' => $country,
        ]);
    }

}
