<?php

namespace App\Http\Middleware;

use App\Services\PartnerContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerContextMiddleware
{
    public function __construct(
        protected PartnerContextService $partnerContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip partner resolution for admin routes
        if ($request->is('admin/*') || $request->is('admin')) {
            return $next($request);
        }

        // Resolve partner from request domain
        $partner = $this->partnerContext->resolveWithFallback();

        // Handle partner not found
        if (!$partner && !config('partner.allow_missing_partner', false)) {
            abort(404, 'Partner not found for this domain.');
        }

        return $next($request);
    }
}
