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
            return redirect()
                ->route('dashboard')
                ->with('error', 'Portal admins cannot access company operations.');
        }

        if ($context->requiresBranchSelection($routeName)) {
            return redirect()
                ->route('branch-context.select')
                ->with('error', 'Please select a branch to continue.');
        }

        return $next($request);
    }
}
