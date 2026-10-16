<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserAgentRotator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProxyController extends Controller
{
    public function handle(Request $request): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $targetUrl = $request->header('X-Target-Url') ?? $request->input('target_url');

        if (!$targetUrl) {
            return response()->json(['error' => 'Target URL parameter is completely missing.'], 400);
        }

        // Automatically prepend https:// if they omitted the protocol (e.g., "google.com")
        if (!preg_match('/^https?:\/\//i', $targetUrl)) {
            $targetUrl = 'https://' . $targetUrl;
        }

        // Now validate the fully qualified URL
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => "The provided URL format ($targetUrl) is invalid."], 400);
        }

        // 2. Setup High-Performance, Low-Memory Stream Guzzle Client
        $client = new Client([
            'stream'          => true,  // Prevents Guzzle from loading entire target body into RAM
            'allow_redirects' => config('proxy.allow_redirects', true),
            'verify'          => config('proxy.verify_ssl', false),
            'timeout'         => config('proxy.timeout', 30.0),
            'decode_content'  => config('proxy.decode_content', false),
            'http_errors'     => false,
        ]);

        // 3. Prepare Client Custom Headers & Inject Random User Agent
        $headers = collect($request->headers->all())
            ->mapWithKeys(fn($value, $key) => [$key => $value[0]])
            ->except(config('proxy.headers_to_strip', ['host', 'x-target-url', 'content-length']))
            ->toArray();

        $headers['User-Agent'] = UserAgentRotator::getRandom();

        $requestOptions = [
            'headers' => $headers,
            'body'    => $request->getContent(), // Handle raw inputs (JSON, XML, forms)
        ];

        $proxyChain = config('proxy.chain', []);
        $proxyEnabled = config('proxy.enabled', false) && is_array($proxyChain) && count($proxyChain) > 0;

        if ($proxyEnabled) {
            $requestOptions['proxy'] = $proxyChain[array_rand($proxyChain)];
        }

        try {
            return $this->streamProxyResponse($client, $request->getMethod(), $targetUrl, $requestOptions);
        } catch (GuzzleException $e) {
            if ($proxyEnabled && config('proxy.fallback_to_direct', true)) {
                unset($requestOptions['proxy']);

                try {
                    return $this->streamProxyResponse($client, $request->getMethod(), $targetUrl, $requestOptions);
                } catch (GuzzleException $fallbackException) {
                    return response()->json([
                        'error'   => 'Proxy connection failure.',
                        'details' => sprintf("Proxy chain failed and direct fallback also failed: %s | %s", $e->getMessage(), $fallbackException->getMessage()),
                    ], 502);
                }
            }

            return response()->json([
                'error'   => 'Proxy connection failure.',
                'details' => $e->getMessage(),
            ], 502);
        }
    }

    private function streamProxyResponse(Client $client, string $method, string $targetUrl, array $options): StreamedResponse
    {
        $upstreamResponse = $client->request($method, $targetUrl, $options);

        return response()->stream(
            function () use ($upstreamResponse) {
                $body = $upstreamResponse->getBody();

                while (!$body->eof()) {
                    echo $body->read(8192);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            },
            $upstreamResponse->getStatusCode(),
            $this->formatResponseHeaders($upstreamResponse->getHeaders())
        );
    }

    /**
     * Sanitize and extract single array items for Laravel headers
     */
    private function formatResponseHeaders(array $headers): array
    {
        return collect($headers)
            ->except(['Transfer-Encoding', 'Connection', 'Content-Length']) // Let host engine handle transmission headers
            ->map(fn($value) => is_array($value) ? end($value) : $value)
            ->toArray();
    }
}
