<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PlatformActivityLogger
{
    public function __construct(private readonly Request $request) {}

    /** @param array<string, mixed> $properties */
    public function record(string $event, Model $subject, array $properties = []): PlatformActivityLog
    {
        return PlatformActivityLog::query()->create([
            'actor_id' => $this->request->user()?->getKey(),
            'event' => $event,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'properties' => $properties,
            'ip_address' => $this->request->ip(),
        ]);
    }
}
