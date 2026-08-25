<?php

namespace App\Support;

use App\Models\CheckIn;

class CheckInData
{
    /**
     * @return array<string, mixed>
     */
    public function make(CheckIn $checkIn): array
    {
        return [
            'id' => $checkIn->getKey(),
            'checked_in_at' => $checkIn->checked_in_at->toIso8601String(),
            'member' => [
                'id' => $checkIn->member->getKey(),
                'member_number' => $checkIn->member->member_number,
                'name' => $checkIn->member->name,
                'phone' => $checkIn->member->phone,
                'photo_url' => is_string($checkIn->member->photo)
                    ? route('members.photo', $checkIn->member->getKey())
                    : null,
            ],
            'membership' => [
                'id' => $checkIn->memberMembership->getKey(),
                'plan_name' => $checkIn->memberMembership->plan_name,
                'end_date' => $checkIn->memberMembership->end_date->toDateString(),
            ],
            'created_by' => $checkIn->createdBy === null
                ? null
                : [
                    'id' => $checkIn->createdBy->getKey(),
                    'name' => $checkIn->createdBy->name,
                ],
        ];
    }
}
