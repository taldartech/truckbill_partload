<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $routeName = $request->route()?->getName() ?? '';

        if ($context->blocksOperationalRoute($routeName)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Portal admins cannot access company operations.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Portal admins cannot access company operations.');
        }

        if ($context->requiresBranchSelection($routeName)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please select a branch to continue.',
                ], 409);
            }

            return redirect()
                ->route('branch-context.select')
                ->with('error', 'Please select a branch to continue.');
        }

        return $next($request);
    }
}
