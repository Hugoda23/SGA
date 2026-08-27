<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
        );

        return response()->json(['message' => 'Suscripción registrada.'], 201);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $request->user()->deletePushSubscription($validated['endpoint']);

        return response()->json(['message' => 'Suscripción eliminada.']);
    }
}
