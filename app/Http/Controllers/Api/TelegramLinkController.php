<?php

namespace App\Http\Controllers\Api;

use App\Actions\Telegram\CreateTelegramLinkAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramLinkController extends Controller
{
    public function store(
        Request $request,
        CreateTelegramLinkAction $action
    ): JsonResponse {
        return response()->json([
            'link' => $action->handle($request->user()),
            'expires_in' => 600,
        ]);
    }
}
