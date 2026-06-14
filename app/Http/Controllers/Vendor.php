<?php

namespace App\Http\Controllers;
use App\Models\CountryModel;
use App\Models\StateModel;
use App\Models\CityModel;
use App\Models\TransportModel;
use App\Models\VehicleTypeModel;
use App\Models\VendorModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
class Vendor extends Controller
{
    //
    public function index()
    {
        $page_title = 'Transporter';
        $page_name = 'Transporter';
        $country = CountryModel::active()->get();
        $transport = TransportModel::active()->get();
        $vehicle_type = VehicleTypeModel::active()->get();
        return view('company/master/vendor/vendor', compact('page_title', 'page_name', 'country','transport','vehicle_type'));
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
        $isUpdate = !empty($request->vendor_id);
        $rules = [
            'vendor_code'      => [
                'required',
                'string',
                'max:100',
                Rule::unique('tbl_vendor', 'vendor_code')
                    ->ignore($request->vendor_id, 'vendor_id')
            ],
            'vendor_name'      => 'required|string|max:255',
            'contact_person'   => 'required|string|max:255',
            'contact_no'       => 'required|digits:10|regex:/^[6-9][0-9]{9}$/',
            'email'            => [
                'required',
                'email',
                'max:255',
                Rule::unique('tbl_vendor', 'email')
                    ->ignore($request->vendor_id, 'vendor_id')
            ],
            'c_id'             => 'required|exists:mst_country,c_id',
            'state_id'         => 'required|exists:mst_state,state_id',
            'city_id'          => 'required|exists:mst_city,city_id',
            'address'          => 'required|string|max:500',
            'pincode'          => 'nullable|digits:6',
            'gst_no'           => 'nullable|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            'pan_no'           => 'nullable|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'transport_id'     => 'required|exists:tbl_transport,transport_id',
            'vehicle_type_id'  => 'required|exists:tbl_vehicle_type,vehicle_type_id',
            'attachment'       => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
        $messages = [
            'contact_no.regex' => 'The contact number must start with 6-9 and be 10 digits.',
            'gst_no.regex'     => 'The GSTIN format is invalid. Example: 22AAAAA0000A1Z5',
            'pan_no.regex'     => 'The PAN format is invalid. Example: AAAAA1234A',
        ];
        $customAttributes = [
            'vendor_code' => 'Vendor Code',
            'vendor_name' => 'Vendor Name',
            'contact_person' => 'Contact Person',
            'email' => 'Email',
            'c_id' => 'Country',
            'state_id' => 'State',
            'city_id' => 'City',
            'transport_id' => 'Transport',
            'vehicle_type_id' => 'Vehicle Type',
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
        if ($isUpdate) {
            $validated['updated_by'] = getCreatedBy();
            $validated['updated_at'] = now();
        } else {
            $validated['created_by'] = getCreatedBy();
            $validated['created_at'] = now();
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('assets/uploads/vendor_attach');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $filename);
            $validated['attachment'] = '/vendor_attach/' . $filename;
            if ($isUpdate) {
                $vendor = VendorModel::findOrFail($request->vendor_id);
                if (!empty($vendor->attachment) && file_exists(public_path('assets/uploads' . $vendor->attachment))) {
                    @unlink(public_path('assets/uploads' . $vendor->attachment));
                }
            }
        }
        if ($isUpdate) {
            VendorModel::where('vendor_id', $request->vendor_id)->update($validated);
            $message = 'Transporter updated successfully.';
        } else {
            VendorModel::create($validated);
            $message = 'Transporter created successfully.';
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
            'message' => 'Operation failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function list()
{
    $vendor = VendorModel::select([
            'tbl_vendor.vendor_id',
            'tbl_vendor.vendor_code as code',
            'tbl_vendor.vendor_name as name',
            'tbl_vendor.contact_no as contact_no',
            'tbl_vendor.email as email',
            'tbl_transport.transport as transport',
            'tbl_vehicle_type.vehicle_type as vehicle_type'
        ])
        ->join('tbl_transport', 'tbl_transport.transport_id', '=', 'tbl_vendor.transport_id')
        ->join('tbl_vehicle_type', 'tbl_vehicle_type.vehicle_type_id', '=', 'tbl_vendor.vehicle_type_id')
        ->where("tbl_vendor.status",'0');

    return DataTables::of($vendor)
        ->addIndexColumn()
        ->addColumn('action', function ($vendor) {
            return '
                <div class="">
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $vendor->vendor_id . '"><i class="bx bx-edit"></i></button>
                    <button 
                            class="btn btn-sm btn-danger delete-btn" 
                            data-id="'.$vendor->vendor_id .'" 
                            data-name="'.$vendor->code.'" 
                            data-module="vendor"
                            data-table="vendordata"
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

public function get_vendor(Request $request)
{
    $vendor = VendorModel::where('vendor_id', $request->vendor_id)->first();

    if ($vendor) {
        return response()->json([
            'status' => true,
            'data' => $vendor
        ]);
    }

    return response()->json([
        'status' => false,
        'message' => 'Vendor not found'
    ], 404);
}

}
