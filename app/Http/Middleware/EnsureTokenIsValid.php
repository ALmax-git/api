<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {


        if (!get_client_namespace($request->header('Xsource'))) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid namespace.',
                'data' => ['xnamespace' => $request->header('Xsource')],
            ], 419);
        }

        return $next($request);
    }
}
