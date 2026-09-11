<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HandleLegacyRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && Schema::hasTable('redirects')) {
            $path = '/'.ltrim(rawurldecode($request->getPathInfo()), '/');
            $redirect = Redirect::query()->where('old_path', $path)->where('is_active', true)->first();
            if ($redirect && $redirect->new_path !== $path) {
                $redirect->increment('hits');
                $redirect->forceFill(['last_hit_at' => now()])->saveQuietly();

                return redirect($redirect->new_path, $redirect->status_code);
            }
        }

        return $next($request);
    }
}
