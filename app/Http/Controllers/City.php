<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CountryModel;
use App\Models\StateModel;
use App\Models\CityModel;
class City extends Controller
{
    //
    public function index()
    {
        $page_title = 'City';
        $page_name = 'city';
         $countrys = CountryModel::where('status', 0)
            ->orderBy('country_name', 'asc')
            ->get();
         $states = StateModel::where('status', 0)
            ->orderBy('state_name', 'asc')
            ->get();
        
        //return view('countries.index', compact('page_title', 'page_name'));
         return view('company/master/city/city', compact('page_title', 'page_name','countrys','states'));
    }

    //list code
    public function getCities()
{
    try {
        $cities = CityModel::with(['country', 'state'])
            ->where('status', 0)
            ->get();

        $grouped = $cities->groupBy(function ($item) {
            return $item->c_id . '-' . $item->state_id;
        });

        $formatted = [];

        foreach ($grouped as $group) {
            $first = $group->first();
            $formatted[] = [
                'c_id' => $first->c_id,
                'state_id' => $first->state_id, // ✅ Add this line
                'country_name' => $first->country->country_name ?? 'N/A',
                'state_name' => $first->state->state_name ?? 'N/A',
                'city_name' => $group->pluck('city_name')->toArray(),
                'city_id' => $group->pluck('city_id')->toArray(),
            ];
        }

        return response()->json(['status' => 'success', 'data' => $formatted]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

//store
/*
public function storeOrUpdateCities(Request $request)
{
    try {
        $cId = $request->c_id;
        $stateId = $request->state_id;
        $cityNames = $request->city_name ?? [];

        $errors = [];

        foreach ($cityNames as $index => $name) {
            $name = trim($name);
            if ($name === '') {
                $errors["city_name.$index"] = 'City name is required.';
            }

            if ($name) {
                $exists = CityModel::where('c_id', $cId)
                    ->where('state_id', $stateId)
                    ->where('city_name', $name)
                    ->where('status', 0)
                    ->exists();

                if ($exists) {
                    $errors["city_name.$index"] = 'City already exists.';
                }
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $errors
            ], 422);
        }

        foreach ($cityNames as $name) {
            CityModel::create([
                'c_id' => $cId,
                'state_id' => $stateId,
                'city_name' => $name,
                'status' => 0,
                'created_by' => getCreatedBy(),
                'created_at' => now()
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cities saved successfully.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}
*/
public function storeOrUpdateCities(Request $request)
{
    try {
        $cId = $request->c_id;
        $stateId = $request->state_id;
        $cityNames = $request->city_name ?? [];
        $cityIds = $request->city_id ?? []; // Add this

        $errors = [];

        foreach ($cityNames as $index => $name) {
            $name = trim($name);
            $cityId = $cityIds[$index] ?? null;

            if ($name === '') {
                $errors["city_name.$index"] = 'City name is required.';
            }

            if ($name) {
                $query = CityModel::where('c_id', $cId)
                    ->where('state_id', $stateId)
                    ->where('city_name', $name)
                    ->where('status', 0);

                // Exclude current city when editing
                if (!empty($cityId)) {
                    $query->where('city_id', '!=', $cityId);
                }

                if ($query->exists()) {
                    $errors["city_name.$index"] = 'City already exists.';
                }
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $errors
            ], 422);
        }

        foreach ($cityNames as $index => $name) {
            $cityId = $cityIds[$index] ?? null;

            if ($cityId) {
                // Update existing city
                CityModel::where('city_id', $cityId)->update([
                    'c_id' => $cId,
                    'state_id' => $stateId,
                    'city_name' => $name,
                    'updated_by' => getUpdatedBy(),
                    'updated_at' => now()
                ]);
            } else {
                // Insert new city
                CityModel::create([
                    'c_id' => $cId,
                    'state_id' => $stateId,
                    'city_name' => $name,
                    'status' => 0,
                    'created_by' => getCreatedBy(),
                    'created_at' => now()
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cities saved successfully.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}

/*public function edit($id)
{
    try {
        $cities = CityModel::where('c_id', $id)
            ->where('status', 0)
            ->get(['city_id', 'city_name', 'c_id', 'state_id']);

        if ($cities->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No cities found for this country'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'c_id' => $id,
                'state_id' => $cities->first()->state_id,
                'cities' => $cities
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching cities: ' . $e->getMessage()
        ], 500);
    }
}*/
public function edit(Request $request)
{
    try {
        $cId = $request->c_id;
        $stateId = $request->state_id;

        $cities = CityModel::where('c_id', $cId)
            ->where('state_id', $stateId)
            ->where('status', 0)
            ->get(['city_id', 'city_name', 'c_id', 'state_id']);

        if ($cities->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No cities found for the selected country and state.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'c_id' => $cId,
                'state_id' => $stateId,
                'cities' => $cities
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error fetching cities: ' . $e->getMessage()
        ], 500);
    }
}


public function destroy($id)
{
    try {
        $updated = CityModel::where('city_id', $id)
            ->where('status', 0)
            ->update([
                'status' => 1,
                'updated_at' => now(),
                'updated_by' => getUpdatedBy(),
            ]);

        if ($updated) {
            return response()->json([
                'status' => 'success',
                'message' => 'City removed from the list.'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'City not found or already removed.'
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to update city: ' . $e->getMessage()
        ], 500);
    }
}
public function deleteByState($id)
{
    try {
        $deleted = CityModel::where('state_id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => $deleted ? 'All cities deleted successfully.' : 'No cities found to delete.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error deleting cities: ' . $e->getMessage()
        ], 500);
    }
}
public function deleteByCountryAndState(Request $request)
{
    $cId = $request->c_id;
    $stateId = $request->state_id;

    try {
        $deleted = CityModel::where('c_id', $cId)
            ->where('state_id', $stateId)
            ->update([
                'status' => 1,
                'updated_by' => getUpdatedBy(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => $deleted ? 'Cities deleted successfully.' : 'No matching cities found.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error deleting cities: ' . $e->getMessage()
        ], 500);
    }
}





}
