<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(string $event, Model $subject, array $properties = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'gym_id' => $this->gymContext->gymId(),
            'user_id' => $this->request->user()?->getKey(),
            'event' => $event,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'properties' => $properties,
            'ip_address' => $this->request->ip(),
        ]);
    }
}
