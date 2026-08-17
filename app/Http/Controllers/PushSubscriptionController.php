<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Persists browser Push API subscriptions so the platform can send native
 * push notifications for match invitations, bet results and tournament
 * start alerts (see App\Notifications\*).
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            endpoint: $data['endpoint'],
            key: $data['keys']['p256dh'],
            token: $data['keys']['auth'],
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = $request->input('endpoint');

        if ($endpoint) {
            $request->user()->deletePushSubscription($endpoint);
        }

        return response()->json(['status' => 'unsubscribed']);
    }
}
