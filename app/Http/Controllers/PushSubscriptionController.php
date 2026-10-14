<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PushSubscriptionController extends Controller
{
    private const ALLOWED_PUSH_DOMAINS = [
        'fcm.googleapis.com',
        'android.googleapis.com',
        'push.services.mozilla.com',
        'push.prod.webservices.mozgcp.net',
        'push.apple.com',
        'notify.windows.com',
    ];

    public function store(Request $request): JsonResponse
    {
        if (blank(config('webpush.vapid.subject'))
            || blank(config('webpush.vapid.public_key'))
            || blank(config('webpush.vapid.private_key'))) {
            return response()->json(['message' => 'Push notifications are not configured on this server.'], 503);
        }

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:1024'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]+$/', 'max:200'],
            'keys.auth' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]+$/', 'max:200'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        abort_unless($this->isAllowedEndpoint($data['endpoint']), 422, 'The push endpoint is not supported.');

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aes128gcm'
        );

        return response()->json(['message' => 'Push notifications are enabled on this device.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:1024'],
        ]);

        abort_unless($this->isAllowedEndpoint($data['endpoint']), 422, 'The push endpoint is not supported.');

        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['message' => 'Push notifications are disabled on this device.']);
    }

    private function isAllowedEndpoint(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        $host = isset($parts['host']) ? Str::lower(rtrim($parts['host'], '.')) : '';

        if (($parts['scheme'] ?? null) !== 'https'
            || (isset($parts['port']) && $parts['port'] !== 443)
            || $host === '') {
            return false;
        }

        foreach (self::ALLOWED_PUSH_DOMAINS as $domain) {
            if ($host === $domain || Str::endsWith($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }
}
