<?php

namespace App\Actions\Members;

use App\Models\Member;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UpdateMember
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Member $member, array $attributes, ?UploadedFile $photo): Member
    {
        $newPhotoPath = $photo?->store('member-photos/'.$this->gymContext->gymId(), 'local');
        $oldPhotoPath = $member->photo;

        if ($photo !== null && ! is_string($newPhotoPath)) {
            throw new RuntimeException('Foto member gagal disimpan.');
        }

        try {
            $member = DB::transaction(function () use ($member, $attributes, $newPhotoPath): Member {
                $member->fill($attributes);

                if (is_string($newPhotoPath)) {
                    $member->photo = $newPhotoPath;
                }

                $changedFields = array_keys($member->getDirty());
                $member->save();

                $this->activityLogger->record('member.updated', $member, [
                    'member_number' => $member->member_number,
                    'fields' => $changedFields,
                ]);

                return $member;
            }, 3);
        } catch (Throwable $exception) {
            if (is_string($newPhotoPath)) {
                Storage::disk('local')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if (is_string($newPhotoPath) && is_string($oldPhotoPath)) {
            Storage::disk('local')->delete($oldPhotoPath);
        }

        return $member;
    }
}
