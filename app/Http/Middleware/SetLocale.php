<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public const SUPPORTED = ['ar', 'fr', 'en'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? Settings::get('locale.default', 'ar');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'ar';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
