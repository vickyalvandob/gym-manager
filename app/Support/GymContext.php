<?php

namespace App\Support;

use App\Enums\GymRole;
use App\Models\Gym;
use LogicException;

class GymContext
{
    private ?Gym $gym = null;

    private ?GymRole $role = null;

    public function set(Gym $gym, GymRole $role): void
    {
        $this->gym = $gym;
        $this->role = $role;
    }

    public function hasGym(): bool
    {
        return $this->gym !== null && $this->role !== null;
    }

    public function gym(): Gym
    {
        return $this->gym ?? throw new LogicException('No gym has been resolved for this request.');
    }

    public function gymId(): int
    {
        return $this->gym()->getKey();
    }

    public function role(): GymRole
    {
        return $this->role ?? throw new LogicException('No gym role has been resolved for this request.');
    }
}
