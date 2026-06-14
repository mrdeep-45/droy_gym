<?php

namespace App\Http\Controllers;
use App\Models\CityModel;
use App\Models\CountryModel;
use App\Models\StateModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pusher\Pusher;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Session;

class LocationController extends Controller
{
    //
    public function getCountries()
{
   
    $countries = CountryModel::select('c_id AS id', 'country_name')->active()->get();
    return response()->json(['success' => true, 'data' => $countries]);
}

// 2. Fetch states by country ID - **FIX THIS**
public function getStates($countryId)
{
   
    $states = StateModel::where('c_id', $countryId)->select('state_id AS id', 'state_name')->active()->get();
    return response()->json(['success' => true, 'data' => $states]);
}

// 3. Fetch cities by state ID - **FIX THIS**
public function getCities($stateId)
{
   
    $cities = CityModel::where('state_id', $stateId)->select('city_id AS id', 'city_name')->active()->get();
    return response()->json(['success' => true, 'data' => $cities]);
}
}
