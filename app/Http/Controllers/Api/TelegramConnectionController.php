<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramConnectionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $connection = $request->user()
            ->telegramConnection()
            ->with('chat')
            ->first();

        return response()->json([
            'connected' => $connection !== null,
            'linked_at' => $connection?->linked_at?->toIso8601String(),
            'chat_name' => $connection?->chat->name,
        ]);
    }
}
