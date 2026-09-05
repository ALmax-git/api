<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BotStatus;
use App\Enums\BranchStatus;
use App\Enums\FacilityStatus;
use App\Enums\FacilityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\Bot;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $namespace = $request->header('X-Namespace');
        $nodeId = $request->header('X-Node-Id');

        if (!$namespace || !$nodeId) {
            return response()->json([
                'message' => 'Missing X-Namespace or X-Node-Id.',
            ], 400);
        }

        // Ask Core-X to validate the namespace/node.
        $validation = Http::acceptJson()
            ->withHeaders([
                'X-Namespace' => $namespace,
                'X-Node-Id' => $nodeId,
            ])
            ->get(
                'https://core-x.almaxcloud.com' . '/api/v1/engine/resolve'
            );

        if (!$validation->successful()) {
            return response()->json(
                $validation->json(),
                $validation->status()
            );
        }

        return 'test';
        $route = $validation->json();

        if (!($route['valid'] ?? false)) {
            return response()->json([
                'message' => 'Namespace/node validation failed.',
            ], 403);
        }

        $domain = 'https://' . preg_replace('#^https?://#i', '', rtrim($route['domain'], '/'));

        $authResponse = Http::acceptJson()
            ->asJson()
            ->post(
                $domain . '/api/v1/auth/login',
                [
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                    'device_name' => $request->input('device_name'),
                ]
            );

        return response(
            $authResponse->body(),
            $authResponse->status()
        )->withHeaders([
            'Content-Type' =>
            $authResponse->header(json_encode(['Content-Type', 'application/json'])),
        ]);
    }
}
