<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompanyIsValid
{
    public function handle(Request $request, Closure $next)
    {
        $session_id = $request->session()->get('company_id') ?? $request->session()->get('staff_id');

        if (!$session_id) {
            return redirect('/');
        }
        $response = $next($request);
        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')->header('Pragma', 'no-cache')->header('Expires', '0');
    }
}
