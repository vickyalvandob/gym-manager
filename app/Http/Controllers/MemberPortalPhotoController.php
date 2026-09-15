<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberPortalPhotoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Member $member): StreamedResponse
    {
        Gate::authorize('viewPortal', $member);

        abort_unless(
            is_string($member->photo) && Storage::disk('local')->exists($member->photo),
            404,
        );

        return Storage::disk('local')->response(
            $member->photo,
            null,
            ['Cache-Control' => 'private, max-age=300'],
        );
    }
}
