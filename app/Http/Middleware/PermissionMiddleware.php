namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission, $guard = null)
    {
        Log::info('PermissionMiddleware: Request URI - ' . $request->path()); // Add this line

        // Temporarily bypass permission check for logout route by checking URI
        if ($request->is('logout')) {
            Log::info('PermissionMiddleware: Bypassing for logout route.'); // Add this line
            return $next($request);
        }

        if (Auth::guard($guard)->guest()) {
            Log::warning('PermissionMiddleware: Guest user, aborting 403.'); // Add this line
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $permissions = is_array($permission)
            ? $permission
            : explode('|', $permission);

        /** @var \App\Models\User $user */
        $user = Auth::guard($guard)->user();

        foreach ($permissions as $p) {
            if ($user->can($p)) {
                Log::info('PermissionMiddleware: User has permission ' . $p); // Add this line
                return $next($request);
            }
        }

        Log::warning('PermissionMiddleware: User lacks required permission, aborting 403.'); // Add this line
        abort(403, 'THIS ACTION IS UNAUTHORIZED.');
    }
}