<?php

namespace App\Http\Middleware;

use App\Models\BotApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bot API auth: `Authorization: Bearer mgb_…` (or `X-Bot-Token`). Usage: bot.token:products:read
 */
class AuthenticateBotToken
{
    public const ATTR = 'bot_api_token';

    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $token = BotApiToken::findActive($request->bearerToken() ?: $request->header('X-Bot-Token'));
        if (! $token) {
            return response()->json(['message' => 'Invalid or revoked token.'], 401);
        }
        if ($ability && ! $token->allows($ability)) {
            return response()->json(['message' => "This token lacks the '{$ability}' ability."], 403);
        }

        // Throttled write: at most once a minute per token.
        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $request->attributes->set(self::ATTR, $token);

        return $next($request);
    }
}
