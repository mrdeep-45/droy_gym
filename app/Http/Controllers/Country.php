<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CountryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;


class Country extends Controller
{
    //
     public function index()
    {
        $page_title = 'Country ';
        $page_name = 'country';
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company/master/country/country', compact('page_title', 'page_name'));
    }

    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $rules = [
        'country_name' => 'required|string|max:1000',
       
    ];
//'country_name' => 'required|string|max:1000',
    $customAttributes = [
        'name' => 'Country Name',
        
    ];

    // $messages = [
    //     'name.regex' => 'The name may only contain letters, spaces, dots (.), apostrophes (\') or hyphens (-).',
    // ];

    $validator = Validator::make($request->all(), $rules, [], $customAttributes);

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

        // Step 2: Manually check for duplicate country_name (with status = 0)
        $exists = CountryModel::where('country_name', $request->country_name)
            ->where('status', 0)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'field' => 'country_name',
                'message' => 'The country name already exists.',
            ], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = getCreatedBy();
        $validated['created_at'] = now();
        $validated['status'] = 0;

        CountryModel::create($validated);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Country created successfully.',
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create Country.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function list(Request $request)
{
    $query = CountryModel::select([
        'c_id',
        'country_name',
        'status',
        'created_at'
    ])->where('status', 0)
    ->orderByDesc('created_at'); // or use ->active() if you have a scope

    return DataTables::of($query)
        ->addIndexColumn()
        ->editColumn('status', function ($row) {
            return $row->status == 1 ? 'Inactive' : 'Active';
        })
        ->editColumn('created_at', function ($row) {
            return $row->created_at ? $row->created_at->format('d-m-Y h:i:s') : '-';
        })
        ->addColumn('action', function ($row) {
            return '
                <div class="btn-group">
                    <button class="btn btn-sm btn-primary edit-btn" data-id="' . $row->c_id . '">Edit</button>
                    <button 
                        class="btn btn-sm btn-danger delete-btn" 
                        data-id="' . $row->c_id . '" 
                        data-name="' . $row->country_name . '" 
                        data-module="country"
                        data-table="rawdata"
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal"
                        >
                        Delete
                    </button>
                </div>
            ';
        })
        ->rawColumns(['action'])
        ->make(true);
}
public function edit($id)
{
    try {
        $country = CountryModel::where('c_id', $id)->first();

        if (!$country) {
            return response()->json([
                'status' => 'error',
                'message' => 'Country not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $country
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching country data: ' . $e->getMessage()
        ], 500);
    }
}
public function update(Request $request)
{
    DB::beginTransaction();
    try {
        $rules = [
            'c_id' => 'required|exists:mst_country,c_id',
            'country_name' => 'required|string|max:1000',
        ];

        $validator = Validator::make($request->all(), $rules);

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

        $country = CountryModel::find($request->c_id);
        
        $country->country_name = $request->country_name;
        $country->updated_at = now();
        $country->save();

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Country updated successfully.']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Failed to update Country.', 'error' => $e->getMessage()], 500);
    }
}


/*public function edit($id)
{
    $country = CountryModel::find($id);

    if (!$country) {
        return response()->json(['success' => false, 'message' => 'Country not found.'], 404);
    }

    return response()->json(['success' => true, 'data' => $country]);
}

public function update(Request $request)
{
    DB::beginTransaction();
    try {
        $rules = [
            'c_id' => 'required|exists:mst_country,c_id',
            'country_name' => 'required|string|max:1000',
        ];

        $validator = Validator::make($request->all(), $rules);

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

        $country = CountryModel::find($request->c_id);
        $country->country_name = $request->country_name;
        $country->updated_at = now();
        $country->save();

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Country updated successfully.']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Failed to update Country.', 'error' => $e->getMessage()], 500);
    }
}*/

/*public function delete(Request $request)
{
    try {
        $country = CountryModel::find($request->id);
        if (!$country) {
            return response()->json(['success' => false, 'message' => 'Country not found.'], 404);
        }

        $country->status = 0;
        $country->save();

        return response()->json(['success' => true, 'message' => 'Country deleted successfully.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Failed to delete country.', 'error' => $e->getMessage()], 500);
    }
}*/
public function destroy(Request $request)
{
    $countryId = $request->c_id;

    try {
        DB::beginTransaction();

        $country = CountryModel::find($countryId);

        if (!$country) {
            return response()->json([
                'status' => 'error',
                'message' => 'Country not found.'
            ]);
        }

        // Soft delete: update status to inactive (1)
        $country->update([
            'status' => 1,
            'updated_at' => now(),
            'updated_by' => getUpdatedBy()
        ]);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Country Deleted Successfully.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update status: ' . $e->getMessage()
        ]);
    }
}



    

}
