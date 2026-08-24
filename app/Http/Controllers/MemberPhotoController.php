<?php

namespace App\Http\Controllers;

use App\Support\GymContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberPhotoController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(int $member): StreamedResponse
    {
        $memberModel = $this->gymContext->gym()->members()
            ->select(['members.id', 'members.gym_id', 'members.photo'])
            ->whereKey($member)
            ->firstOrFail();

        Gate::authorize('view', $memberModel);

        abort_unless(
            is_string($memberModel->photo) && Storage::disk('local')->exists($memberModel->photo),
            404,
        );

        return Storage::disk('local')->response(
            $memberModel->photo,
            null,
            ['Cache-Control' => 'private, max-age=300'],
        );
    }
}
