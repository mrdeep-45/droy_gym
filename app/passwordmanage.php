<?php

use Illuminate\Support\Facades\Crypt;

if (!function_exists('enli_encrypt')) {
    function enli_encrypt($value)
    {
        $secret = config('services.enlipassword.secret');

        return $secret . '::' . Crypt::encryptString($value);
    }
}

if (!function_exists('enli_decrypt')) {
    function enli_decrypt($value)
    {
        $secret = config('services.enlipassword.secret');



        if (str_starts_with($value, $secret . '::')) {
            $value = substr($value, strlen($secret . '::'));
        }
     
        return Crypt::decryptString($value);
    }
}
