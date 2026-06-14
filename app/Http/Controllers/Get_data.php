<?php


namespace App\Http\Controllers;

use App\Models\Document_mngtModel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Get_data extends Controller
{
    //
    public function get_expiring_documents()
    {
        $today = Carbon::today();
        $oneMonthLater = $today->copy()->addMonth();

        $documents = Document_mngtModel::where('is_expiry_date', 1)
            ->where(function ($query) use ($today, $oneMonthLater) {
                $query->where('expiry_date', '<=', $oneMonthLater)
                    ->where('expiry_date', '>=', $today);
            })
            ->orWhere(function ($query) use ($today) {
                $query->where('is_expiry_date', 1)
                    ->where('expiry_date', '<', $today);
            })
            ->orderBy('expiry_date', 'asc')
            ->get();

        $documents->transform(function ($document) use ($today) {
            $expiryDate = Carbon::parse($document->expiry_date);
            $document->due_days = $expiryDate->diffInDays($today, false);
            $document->document_cat_id = encrypt($document->dc_id);
            $document->document_id = encrypt($document->dm_id);
            $document->category_name = get_document_name($document->dc_id);
            return $document;
        });

        return response()->json([
            'total_count' => $documents->count(),
            'documents' => $documents
        ]);
    }
    public function get_customer_Countries(Request $request)
    {
        $search = $request->search;
        $fetchOne = $request->fetch_one ?? false;

        $query = DB::table('mst_country')
            ->select('c_id as id', 'country_name as text');

        if ($fetchOne && $search) {
            return $query->where('c_id', $search)->first();
        }

        if ($search) {
            $query->where('country_name', 'like', '%' . $search . '%');
        }

        $results = $query->get();

        return response()->json([
            'results' => $results,
            'total_count' => $results->count()
        ]);
    }

    public function get_customer_States(Request $request)
    {
        $search = $request->search;
        $countryId = $request->country_id;
        $fetchOne = $request->fetch_one ?? false;

        $query = DB::table('mst_state')
            ->select('state_id as id', 'state_name as text')
            ->where('c_id', $countryId);

        if ($fetchOne && $search) {
            return $query->where('state_id', $search)->first();
        }

        if ($search) {
            $query->where('state_name', 'like', '%' . $search . '%');
        }

        $results = $query->get();

        return response()->json([
            'results' => $results,
            'total_count' => $results->count()
        ]);
    }

    public function get_customer_Cities(Request $request)
    {
        $search = $request->search;
        $stateId = $request->state_id;
        $fetchOne = $request->fetch_one ?? false;

        $query = DB::table('mst_city')
            ->select('city_id as id', 'city_name as text')
            ->where('state_id', $stateId);

        if ($fetchOne && $search) {
            return $query->where('city_id', $search)->first();
        }

        if ($search) {
            $query->where('city_name', 'like', '%' . $search . '%');
        }

        $results = $query->get();

        return response()->json([
            'results' => $results,
            'total_count' => $results->count()
        ]);
    }
}
