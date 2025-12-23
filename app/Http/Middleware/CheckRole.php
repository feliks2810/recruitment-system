<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            // Redirect them to the home page or show an error
            return redirect('/');
        }

        return $next($request);
    }
}