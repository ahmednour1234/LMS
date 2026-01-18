<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTeacherPanel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set the teacher panel as current before Filament middleware runs
        $panel = Filament::getPanel('teacher');
        if ($panel) {
            Filament::setCurrentPanel($panel);
        }

        return $next($request);
    }
}
