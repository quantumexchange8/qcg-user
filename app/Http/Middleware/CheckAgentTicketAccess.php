<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TicketAgentAccessibility;
use Symfony\Component\HttpFoundation\Response;

class CheckAgentTicketAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user->role !== 'agent') {
            abort(403);
        }

        $hasAccess = TicketAgentAccessibility::where('user_id', $user->id)->exists();

        if (! $hasAccess) {
            abort(403, 'Access denied to ticket page.');
        }

        return $next($request);
    }
}
