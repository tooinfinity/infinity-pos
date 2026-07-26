<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\UpdateUserLastActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureUserIsActive
{
    public function __construct(
        private UpdateUserLastActivity $touchActivity,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $fresh = User::query()
            ->withTrashed()
            ->whereKey($user->getKey())
            ->select(['id', 'is_active', 'last_activity_at', 'deleted_at'])
            ->first();

        if ($fresh === null || $fresh->deleted_at !== null) {
            $this->signOut($request);

            return to_route('login');
        }

        if (! $fresh->is_active) {
            $this->signOut($request);

            return to_route('login');
        }

        $configured = config('pos.session.idle_minutes');
        $idleMinutes = is_int($configured) ? $configured : 0;

        if ($idleMinutes > 0 && $fresh->last_activity_at !== null) {
            $threshold = CarbonImmutable::now()->subMinutes($idleMinutes);

            if ($fresh->last_activity_at->lessThan($threshold)) {
                $this->signOut($request);

                return to_route('login');
            }
        }

        $response = $next($request);

        $this->touchActivity->handle($fresh);

        return $response;
    }

    private function signOut(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
