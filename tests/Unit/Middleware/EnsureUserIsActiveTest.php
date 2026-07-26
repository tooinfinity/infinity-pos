<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('passes requests without an authenticated user to the next middleware', function (): void {
    $request = Request::create('/dashboard');
    $expected = new Response('', 204);

    $response = resolve(EnsureUserIsActive::class)->handle(
        $request,
        fn (Request $request): Response => $expected,
    );

    expect($response)->toBe($expected);
});
