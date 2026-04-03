<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            // طلبات الويب: توجيه لصفحة الدخول. طلبات JSON/API: 401.
            if ($request->expectsJson()) {
                abort(401, 'غير مصرح.');
            }

            return redirect()->guest(route('login'));
        }

        // التحقق من الدور باستخدام العلاقة المحلية لتفادي ربط التنفيذ بحزمة واحدة فقط.
        $hasRole = $user->roles()
            ->whereIn('name', $roles)
            ->exists();

        if (! $hasRole) {
            abort(403, 'لا تملك صلاحية الوصول.');
        }

        return $next($request);
    }
}
