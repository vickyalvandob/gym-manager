<?php

namespace App\Http\Controllers;

use App\Support\GymContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GymLogoController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(): StreamedResponse
    {
        $gym = $this->gymContext->gym();
        Gate::authorize('view', $gym);

        abort_unless(
            is_string($gym->logo) && Storage::disk('local')->exists($gym->logo),
            404,
        );

        return Storage::disk('local')->response(
            $gym->logo,
            null,
            ['Cache-Control' => 'private, max-age=300'],
        );
    }
}
