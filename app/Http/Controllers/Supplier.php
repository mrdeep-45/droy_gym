<?php

namespace App\Http\Controllers;

use App\Models\CountryModel;
use App\Models\StateModel;
use App\Models\CityModel;
use App\Models\SupplierModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
class Supplier extends Controller
{
    //
    public function index()
    {
        $page_title = 'Supplier';
        $page_name = 'Supplier';
        $country = CountryModel::active()->get();

        return view('company/master/supplier/supplier', compact('page_title', 'page_name', 'country'));
    }
    public function getStatesByCountry(Request $request)
    {
        $country_id = $request->input("country_id");
        $states = StateModel::where('c_id', $country_id)
            ->where('status', 0)
            ->orderBy('state_name')
            ->get(['state_id', 'state_name', 'state_code']);

        return response()->json($states);
    }

    public function getCitiesByState(Request $request)
    {
        $stateId = $request->input("stateId");
        $city = CityModel::where('state_id', $stateId)
            ->where('status', 0)
            ->orderBy('city_name')
            ->get(['city_id', 'city_name']);

        return response()->json($city);
    }

  public function store(Request $request)
{
    DB::beginTransaction();
    try {
        $supplierId = $request->input('supplier_id');
        $rules = [
            'name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('tbl_supplier', 'email')
                    ->ignore($supplierId, 'supplier_id'),
            ],
            'contact_no' => ['required', 'regex:/^[6-9][0-9]{9}$/', 'max:10'],
            'c_id' => 'required|integer|exists:mst_country,c_id',
            'state_id' => 'required|integer|exists:mst_state,state_id',
            'state_code' => 'nullable|string|max:10',
            'city_id' => 'required|integer|exists:mst_city,city_id',
            'address' => 'required|string|max:255',
            'gst_no' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}$/'],
            'pan_no' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'cin_no' => ['nullable', 'regex:/^[A-Z]{1}[0-9]{5}[A-Z]{2}[0-9]{4}[A-Z]{3}[0-9]{6}$/'],
        ];
        $customAttributes = [
            'name' => 'Supplier Name',
            'email' => 'Email',
            'contact_no' => 'Contact No',
            'c_id' => 'Country',
            'state_id' => 'State',
            'city_id' => 'City',
        ];
        $messages = [
            'gst_no.regex' => 'GSTIN must be in format e.g. 22AAAAA0000A1Z5',
            'pan_no.regex' => 'PAN No. must be in format e.g. AAAAA1234A',
            'cin_no.regex' => 'CIN must be in format e.g. U12345MH2000PLC123456',
        ];
        $validator = Validator::make($request->all(), $rules, $messages, $customAttributes);
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
        if ($supplierId) {
            $validated['updated_by'] = getCreatedBy();
            $validated['updated_at'] = now();
            SupplierModel::where('supplier_id', $supplierId)->update($validated);
            $message = 'Supplier updated successfully.';
        } else {
            $validated['created_by'] = getCreatedBy();
            $validated['created_at'] = now();
            SupplierModel::create($validated);
            $message = 'Supplier created successfully.';
        }
        DB::commit();
        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to save supplier.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



public function list()
{
    $supplier = SupplierModel::select([
            'tbl_supplier.supplier_id',
            'tbl_supplier.name',
            'tbl_supplier.email',
            'tbl_supplier.contact_no',
            'mst_country.country_name',
            'mst_state.state_name',
            'mst_city.city_name'
        ])
        ->join('mst_country', 'mst_country.c_id', '=', 'tbl_supplier.c_id')
        ->join('mst_state', 'mst_state.state_id', '=', 'tbl_supplier.state_id')
        ->join('mst_city', 'mst_city.city_id', '=', 'tbl_supplier.city_id')
        ->where('tbl_supplier.status', '0');

    return DataTables::of($supplier)
        ->addIndexColumn()
        ->filterColumn('country_name', function ($query, $keyword) {
            $query->where('mst_country.country_name', 'like', "%{$keyword}%");
        })
        ->filterColumn('state_name', function ($query, $keyword) {
            $query->where('mst_state.state_name', 'like', "%{$keyword}%");
        })
        ->filterColumn('city_name', function ($query, $keyword) {
            $query->where('mst_city.city_name', 'like', "%{$keyword}%");
        })
        ->addColumn('action', function ($supplier) {
            return '
                <div class="">
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $supplier->supplier_id . '"><i class="bx bx-edit"></i></button>
                    <button 
                        class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . $supplier->supplier_id . '" 
                        data-name="' . $supplier->name . '" 
                        data-module="supplier"
                        data-table="supplierdata"
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal"
                    >
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            ';
        })
        ->rawColumns(['action'])
        ->make(true);
}


public function get_supplier(Request $request)
{
    $id = $request->input('supplier_id');
    $supplier = SupplierModel::select([
        'tbl_supplier.supplier_id',
        'tbl_supplier.name',
        'tbl_supplier.email',
        'tbl_supplier.contact_no',
        'tbl_supplier.c_id',
        'tbl_supplier.state_id',
        'tbl_supplier.city_id',
        'tbl_supplier.address',
        'tbl_supplier.gst_no',
        'tbl_supplier.pan_no',
        'tbl_supplier.cin_no',
        'mst_state.state_code'
    ])
    ->join('mst_state', 'mst_state.state_id', '=', 'tbl_supplier.state_id')
    ->join('mst_country', 'mst_country.c_id', '=', 'tbl_supplier.c_id')
    ->where('supplier_id', $id)
    ->first();

    if ($supplier) {
        return response()->json(['status' => true, 'data' => $supplier]);
    } else {
        return response()->json(['status' => false, 'message' => 'Supplier not found']);
    }
}

}
