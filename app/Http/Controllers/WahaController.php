<?php

namespace App\Http\Controllers;

use App\Services\Waha\WahaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WahaController
{
    public function status(WahaClient $waha): JsonResponse
    {
        return response()->json($waha->status());
    }

    public function sendText(Request $request, WahaClient $waha): JsonResponse
    {
        $payload = $request->validate([
            'chatId' => ['required', 'string'],
            'text' => ['required', 'string'],
            'session' => ['nullable', 'string'],
        ]);

        return response()->json($waha->sendText(
            $payload['chatId'],
            $payload['text'],
            $payload['session'] ?? null,
        ));
    }
}
