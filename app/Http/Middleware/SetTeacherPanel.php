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
        // Ensure Filament panel registry is booted
        $manager = Filament::getFacadeRoot();
        
        // Get the teacher panel and set it as current
        $panel = Filament::getPanel('teacher');
        if ($panel) {
            $manager->setCurrentPanel($panel);
        }

        return $next($request);
    }
}
