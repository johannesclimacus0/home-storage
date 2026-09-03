<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhookSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('telegraph.webhook.secret');
        $actual = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (
            !is_string($expected)
            || $expected === ''
            || !is_string($actual)
            || !hash_equals($expected, $actual)
        ) {
            abort(403);
        }

        return $next($request);
    }
}
