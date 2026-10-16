<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsintController extends Controller
{
    public function search_cac(Request $request)
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2'],
        ]);

        $searchTerm = $request->input('query');

        // 2. Forward the request to the CAC public search endpoint
        // Note: Adjust the payload array structure ('searchKey', 'searchTerm', etc.) 
        // if the CAC endpoint expects a specific key name.
        $searchTerm = $request->input('query');

        try {
            // Target FastCAC's Livewire tool endpoint
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'Content-Type' => 'application/json',
                    // 'X-Livewire' => 'true',
                    'Referer' => 'https://fastcac.com/tools/cac-name-checker',
                    'Origin' => 'https://fastcac.com',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->post('https://fastcac.com/tools/cac-name-checker', [
                    $searchTerm,
                    'ALL'
                ]);

            if ($response->failed()) {
                Log::error('cac error', [$response]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reach verification gateway.',
                ], 502);
            }
            Log::error('Core-X CAC Proxy Error', [$response]);
            $resJson = $response->json();

            // Livewire responses return an array map. 
            // We look inside the response nodes to find the structured payload containing 'success' and 'data'
            $companyData = [];
            foreach ($resJson as $node) {
                if (is_array($node) && isset($node['success']) && $node['success'] === true) {
                    $companyData = $node['data'] ?? [];
                    break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Data retrieved successfully.',
                'data' => $companyData,
            ]);
        } catch (\Exception $e) {
            Log::error('Core-X CAC Proxy Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while validating the business registration.',
            ], 500);
        }
    }
}
