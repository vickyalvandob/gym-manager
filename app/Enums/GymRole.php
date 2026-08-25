<?php

namespace App\Enums;

enum GymRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Trainer = 'trainer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Front Desk',
            self::Trainer => 'Trainer',
        };
    }

    /**
     * @return array{
     *     manage_gym: bool,
     *     manage_users: bool,
     *     operate_front_desk: bool,
     *     access_trainer_workspace: bool,
     *     view_reports: bool
     * }
     */
    public function permissions(): array
    {
        return [
            'manage_gym' => $this === self::Owner,
            'manage_users' => $this === self::Owner,
            'operate_front_desk' => in_array($this, [self::Owner, self::Admin], true),
            'access_trainer_workspace' => true,
            'view_reports' => $this === self::Owner,
        ];
    }
}
