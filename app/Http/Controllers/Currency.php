<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class Currency extends Controller
{
    public function index()
    {
        $page_title = 'Currency Mapping';
        $page_name = 'Currency Mapping';
        return view('company/master/currency_mapping', compact('page_title', 'page_name'));
    }

    public function getData(Request $request)
    {
        $query = DB::table('currency_exchange_rates')
            ->select('*');

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = '';
                if (permissions_check('canUpdate')) {
                    $btn .= '<button class="btn btn-sm btn-primary edit-btn" data-id="' . $row->id . '">Edit</button> ';
                }
                if (permissions_check('canDelete')) {
                    $btn .= '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</button>';
                }
                if (permissions_check('canUpdate')) {
                    $btn .= '<button class="btn btn-sm btn-info ms-1 refresh-btn" data-id="' . $row->id . '" data-from="' . $row->from_currency . '" data-to="' . $row->to_currency . '">Refresh Rate</button>';
                }
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function fetchLiveRate($from, $to)
    {
        try {
            $response = Http::get("https://api.frankfurter.app/latest?from={$from}&to={$to}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'rate' => $data['rates'][$to] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch live rate'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'rate' => 'required|numeric|min:0.0001'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if the pair already exists
            $exists = DB::table('currency_exchange_rates')
                ->where('from_currency', $request->from_currency)
                ->where('to_currency', $request->to_currency)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This currency pair already exists'
                ]);
            }

            DB::table('currency_exchange_rates')->insert([
                'from_currency' => strtoupper($request->from_currency),
                'to_currency' => strtoupper($request->to_currency),
                'rate' => $request->rate,
                'is_auto_updated' => $request->has('auto_update') ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Currency exchange rate added successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function edit(Request $request)
    {
        $currency = DB::table('currency_exchange_rates')
            ->where('id', $request->id)
            ->first();

        if ($currency) {
            return response()->json([
                'success' => true,
                'data' => $currency
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Currency exchange rate not found'
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'rate' => 'required|numeric|min:0.0001',
            'id' => 'required|integer|exists:currency_exchange_rates,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if another pair with same currencies exists (excluding current record)
            $exists = DB::table('currency_exchange_rates')
                ->where('from_currency', $request->from_currency)
                ->where('to_currency', $request->to_currency)
                ->where('id', '!=', $request->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This currency pair already exists'
                ]);
            }

            DB::table('currency_exchange_rates')
                ->where('id', $request->id)
                ->update([
                    'from_currency' => strtoupper($request->from_currency),
                    'to_currency' => strtoupper($request->to_currency),
                    'rate' => $request->rate,
                    'is_auto_updated' => $request->has('auto_update') ? 1 : 0,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Currency exchange rate updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function refreshRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:currency_exchange_rates,id',
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $liveRate = $this->fetchLiveRate($request->from_currency, $request->to_currency);

        if (!$liveRate['success']) {
            return response()->json([
                'success' => false,
                'message' => $liveRate['message']
            ]);
        }

        if (is_null($liveRate['rate'])) {
            return response()->json([
                'success' => false,
                'message' => 'Could not retrieve rate for this currency pair'
            ]);
        }

        DB::table('currency_exchange_rates')
            ->where('id', $request->id)
            ->update([
                'rate' => $liveRate['rate'],
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate updated successfully',
            'new_rate' => $liveRate['rate']
        ]);
    }

    public function delete(Request $request)
    {
        try {
            DB::table('currency_exchange_rates')
                ->where('id', $request->id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Currency exchange rate deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
