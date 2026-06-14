<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class Customer extends Controller
{
    public function index()
    {
        $page_title = "Customer";
        $page_name = "Customer";

        return view("company/lead/customer", compact('page_title', 'page_name'));
    }
    public function list()
    {
        $customers = CustomerModel::select(['*'])
            ->orderBy('customer_id', 'desc')
            ->where('status', 0)
            ->get();

        $permissions = checkPermissions(get_index_route($this));
        $canUpdate = $permissions['canUpdate'] ?? false;
        $canDelete = $permissions['canDelete'] ?? false;

        foreach ($customers as $customer) {
            $customer->type_status = $customer->customer_type;
            $customer->contact_details = $customer->contact_person . '<br>' . $customer->phone . '<br>' . $customer->email;
            $customer->customer_name = '<a data-id="' . encrypt($customer->customer_id) . '" class=""><span class="text-primary view-customer">' . $customer->company_name . '</span></a>';
            $customer->location = $customer->address . '<br>' . get_country_name($customer->country_id) . ' - ' . get_state_name($customer->state_id) . ' - ' . get_city_name($customer->city_id);
            $customer->created_by_name = get_createdby_name($customer->created_by) . '<br>' . Carbon::parse($customer->created_at)->format('d-m-Y');

            $actions = '<button type="button" class="btn btn-info btn-sm view-customer" data-id="' . encrypt($customer->customer_id) . '">View</button>';

        
            $customer->action = $actions;
        }

        return DataTables::of($customers)
            ->addIndexColumn()
            ->rawColumns(['contact_details', 'customer_name', 'location', 'created_by_name', 'created_at', 'action'])
            ->make(true);
    }
    public function customer_data($id)
    {
        try {
            $decryptedId = decrypt($id);
            $customer = CustomerModel::where('customer_id', $decryptedId)
                ->where('status', 0)
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $response = [
                'success' => true,
                'data' => [
                    'customer_id' => $customer->customer_id,
                    'lead_id' => $customer->lead_id,
                    'deal_id' => $customer->deal_id,
                    'company_name' => $customer->company_name,
                    'customer_type' => $customer->customer_type,
                    'contact_person' => $customer->contact_person,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'alt_phone' => $customer->alt_phone,
                    'business_registration_no' => $customer->business_registration_no,
                    'tax_id' => $customer->tax_id,
                    'gst_no' => $customer->gst_no,
                    'pan_no' => $customer->pan_no,
                    'gst_registration_type' => $customer->gst_registration_type,
                    'billing_address' => $customer->billing_address,
                    'shipping_address' => $customer->shipping_address,
                    'is_same_as_billing' => $customer->is_same_as_billing,
                    'payment_terms' => $customer->payment_terms,
                    'account_status' => $customer->account_status,
                    'country_id' => $customer->country_id,
                    'state_id' => $customer->state_id,
                    'city_id' => $customer->city_id,
                    'address' => $customer->address,
                    'notes' => $customer->notes,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                    'created_by' => $customer->created_by,
                    'updated_by' => $customer->updated_by,
                    'country_name' => get_country_name($customer->country_id),
                    'state_name' => get_state_name($customer->state_id),
                    'city_name' => get_city_name($customer->city_id),
                    'created_by_name' => get_createdby_name($customer->created_by),
                    'created_at_formatted' => Carbon::parse($customer->created_at)->format('d-m-Y'),
                    'location' => $customer->address
                ]
            ];

            return response()->json($response);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid customer ID'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customer: ' . $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'customer_id' => 'required|exists:tbl_customer,customer_id',
                'company_name' => 'required|string|max:255',
                'customer_type' => 'required|string|max:50',
                'contact_person' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'alt_phone' => 'nullable|string|max:20',
                'business_registration_no' => 'nullable|string|max:100',
                'tax_id' => 'nullable|string|max:100',
                'gst_no' => 'nullable|string|max:100',
                'pan_no' => 'nullable|string|max:100',
                'gst_registration_type' => 'nullable|string|max:100',
                'billing_address' => 'required|string',
                'shipping_address' => 'nullable|string',
                'is_same_as_billing' => 'nullable|boolean',
                'payment_terms' => 'nullable|string|max:255',
                'account_status' => 'required|string|max:50',
                'country_id' => 'required|exists:mst_country,c_id',
                'state_id' => 'required|exists:mst_state,state_id',
                'city_id' => 'required|exists:mst_city,city_id',
                'address' => 'required|string|max:255',
                'notes' => 'nullable|string',
            ]);

            $customer = CustomerModel::findOrFail($validatedData['customer_id']);

            $isSameAsBilling = !empty($validatedData['is_same_as_billing']) ? 1 : 0;

            // Update customer data
            $customer->update([
                'company_name' => $validatedData['company_name'],
                'customer_type' => $validatedData['customer_type'],
                'contact_person' => $validatedData['contact_person'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'alt_phone' => $validatedData['alt_phone'] ?? null,
                'business_registration_no' => $validatedData['business_registration_no'] ?? null,
                'tax_id' => $validatedData['tax_id'] ?? null,
                'gst_no' => $validatedData['gst_no'] ?? null,
                'pan_no' => $validatedData['pan_no'] ?? null,
                'gst_registration_type' => $validatedData['gst_registration_type'] ?? null,
                'billing_address' => $validatedData['billing_address'],
                'shipping_address' => $isSameAsBilling ? null : ($validatedData['shipping_address'] ?? null),
                'is_same_as_billing' => $isSameAsBilling,
                'payment_terms' => $validatedData['payment_terms'] ?? null,
                'account_status' => $validatedData['account_status'],
                'country_id' => $validatedData['country_id'],
                'state_id' => $validatedData['state_id'],
                'city_id' => $validatedData['city_id'],
                'address' => $validatedData['address'],
                'notes' => $validatedData['notes'] ?? null,
                'updated_by' => getUpdatedBy(),
            ]);

            // Get updated customer data with relationships
            $updatedCustomer = CustomerModel::where('customer_id', $customer->customer_id)
                ->first()
                ->toArray();

            // Add additional fields to response
            $updatedCustomer['country_name'] = get_country_name($updatedCustomer['country_id']);
            $updatedCustomer['state_name'] = get_state_name($updatedCustomer['state_id']);
            $updatedCustomer['city_name'] = get_city_name($updatedCustomer['city_id']);
            $updatedCustomer['created_by_name'] = get_createdby_name($updatedCustomer['created_by']);
            $updatedCustomer['created_at_formatted'] = Carbon::parse($updatedCustomer['created_at'])->format('d-m-Y');

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $updatedCustomer
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating customer: ' . $e->getMessage()
            ], 500);
        }
    }
}
