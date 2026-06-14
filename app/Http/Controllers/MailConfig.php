<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MailConfig extends Controller
{
    //
    public function index()
    {
        $page_title = 'Mail Config';
        $page_name = 'Mail Config';
        $data = DB::table('mail_config')->where('id', 1)->first();
        if ($data && !empty($data->MAIL_PASSWORD)) {
            $data->MAIL_PASSWORD = $data->MAIL_PASSWORD;
        }

        return view('company.setting.mail_config', compact('page_title', 'page_name', 'data'));
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'MAIL_MAILER' => 'nullable|string',
            'MAIL_HOST' => 'nullable|string',
            'MAIL_PORT' => 'nullable|integer',
            'MAIL_USERNAME' => 'nullable|string',
            'MAIL_PASSWORD' => 'nullable|string',
            'MAIL_ENCRYPTION' => 'nullable|string',
            'MAIL_FROM_ADDRESS' => 'nullable|string',
            'MAIL_FROM_NAME' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
        ]);

        if (!empty($data['MAIL_PASSWORD'])) {
            $data['MAIL_PASSWORD'] = $data['MAIL_PASSWORD'];
        }


        $existing = DB::table('mail_config')->where('id', 1)->first();

        if ($existing) {
            DB::table('mail_config')->where('id', 1)->update($data);
            $message = 'Mail Config updated successfully';
        } else {
            $data['id'] = 1;
            DB::table('mail_config')->insert($data);
            $message = 'Mail Config inserted successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);

    }
}
