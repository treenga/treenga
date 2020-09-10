<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\TeamService;
use Illuminate\Support\Facades\Auth;

class Update
{
    public function __construct(
        TeamService $teamService
    )
    {
        $this->teamService = $teamService;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $me = auth()->user();
        if ($me->version < 2) {
            $this->teamService->createPrivate($me);
            $me->version = 2;
            $me->save();
        }

        return $next($request);
    }
}
