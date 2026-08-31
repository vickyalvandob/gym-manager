export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    is_platform_admin: boolean;
    is_active: boolean;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    isPlatformAdmin: boolean;
    currentGym: Gym | null;
    role: GymRole | null;
    roleLabel: string | null;
    permissions: Partial<Record<GymPermission, boolean>>;
    subscription: {
        status: string;
        status_label: string;
        grants_access: boolean;
        is_subscriber: boolean;
        has_pending_payment: boolean;
    } | null;
    availableGyms: Array<{
        id: number;
        name: string;
        status: 'active' | 'suspended';
        role: GymRole;
    }>;
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
    logo_url: string | null;
    status: 'active' | 'suspended';
    timezone: string;
    currency: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    membership_expiry_warning_days: number;
    count_pt_no_show_as_used_session: boolean;
};
