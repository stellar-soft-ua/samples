<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role = null, $guard = null)
    {
        $authGuard = Auth::guard($guard);

        if ($authGuard->guest()) {
            throw new \Exception("User is not logged in.", 403);
        }

        if (empty($role)) {
            throw new \Exception("Unauthorized Exception", 403);
        }

        // parse roles
        $roles = is_array($role) ? $role : explode('|', $role);
        $userRoles = User::getRolesName();
        $roleName = $userRoles[$authGuard->user()->role_id];

        if (is_array($roles)) {
            foreach ($roles as $r) {
                if (in_array($r, $userRoles) && array_search($r, $userRoles) === $authGuard->user()->role_id) {
                    return $next($request);
                }
            }


            // redirections for different roles
            //FIXME: this need fix, cause switch and redirect in different roles is not right
            switch($authGuard->user()->role_id){
                case User::ROLE_SUPERADMIN:
                case User::ROLE_ADMIN:
                    if (!in_array($roleName,$roles)){
                        return redirect()->route('clients.index');
                    }

                    break;
                case User::ROLE_CLIENT:
                    if (!in_array($roleName,$roles)){
                        return redirect()->route('subscriptions.index');
                    }

                    break;

            }

            throw new \Exception("Unauthorized Exception", 403);
        }

        Log::debug(print_r($roles, 1));
        throw new \Exception("Unauthorized Exception", 403);
//        return $next($request);
    }


}
