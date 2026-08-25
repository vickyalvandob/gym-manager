export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    currentGym: Gym | null;
    role: GymRole | null;
    roleLabel: string | null;
    permissions: Partial<Record<GymPermission, boolean>>;
};

export type GymRole = 'owner' | 'admin' | 'trainer';

export type GymPermission =
    | 'manage_gym'
    | 'manage_users'
    | 'operate_front_desk'
    | 'access_trainer_workspace'
    | 'view_reports';

export type Gym = {
    id: number;
    name: string;
    slug: string;
    status: 'active' | 'suspended';
    timezone: string;
    currency: string;
    membership_expiry_warning_days: number;
};
