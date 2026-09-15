import type {
    MemberMembershipStatus,
    MemberStatus,
    PaginationLink,
} from './member';
import type { PaymentStatus, PaymentType } from './payment';
import type { PtSessionStatus } from './personal-training';

export type MemberPortalProfile = {
    id: number;
    member_number: string;
    name: string;
    gym_name: string;
};

export type MemberPortalMember = {
    id: number;
    member_number: string;
    name: string;
    phone: string;
    email: string;
    address: string | null;
    emergency_contact: string | null;
    status: MemberStatus;
    status_label: string;
    photo_url: string | null;
    gym: {
        id: number;
        name: string;
        slug: string;
        timezone: string;
        currency: string;
    };
};

export type MemberPortalMembership = {
    id: number;
    plan_name: string;
    start_date: string;
    end_date: string;
    status: MemberMembershipStatus;
    status_label: string;
    days_remaining: number | null;
    is_expiring_soon: boolean;
    payment_status: PaymentStatus | null;
    payment_status_label: string | null;
};

export type MemberPortalCheckIn = {
    id: number;
    checked_in_at: string;
    membership: { plan_name: string; end_date: string };
};

export type MemberPortalPayment = {
    id: number;
    invoice_number: string;
    type: PaymentType;
    type_label: string;
    description: string;
    amount: string;
    status: PaymentStatus;
    status_label: string;
    method_label: string | null;
    paid_at: string | null;
    created_at: string | null;
};

export type MemberPortalPtPackage = {
    id: number;
    name: string;
    total_sessions: number;
    used_sessions: number;
    scheduled_sessions: number;
    available_sessions: number;
    start_date: string;
    expires_at: string | null;
    status: 'pending' | 'active' | 'completed' | 'expired' | 'cancelled';
    status_label: string;
    payment_status: PaymentStatus;
    payment_status_label: string;
    trainer: { name: string; specialization: string | null };
};

export type MemberPortalPtSession = {
    id: number;
    scheduled_at: string;
    duration_minutes: number;
    status: PtSessionStatus;
    status_label: string;
    trainer: { name: string; specialization: string | null };
    pt_package_name: string;
};

export type MemberPortalPaginator<T> = {
    data: T[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

export type MemberPortalBaseProps = {
    member: MemberPortalMember;
    profiles: MemberPortalProfile[];
};

export type MemberPortalOverviewProps = MemberPortalBaseProps & {
    membership: MemberPortalMembership | null;
    latest_check_in_at: string | null;
    active_pt_package: MemberPortalPtPackage | null;
    qr_code_data_url: string | null;
};

export type MemberPortalCheckInsProps = MemberPortalBaseProps & {
    checkIns?: MemberPortalPaginator<MemberPortalCheckIn>;
};

export type MemberPortalPaymentsProps = MemberPortalBaseProps & {
    payments?: MemberPortalPaginator<MemberPortalPayment>;
};

export type MemberPortalPersonalTrainingProps = MemberPortalBaseProps & {
    ptPackages?: MemberPortalPaginator<MemberPortalPtPackage>;
    ptSessions?: MemberPortalPaginator<MemberPortalPtSession>;
};

export type MemberPortalSettingsProps = MemberPortalBaseProps & {
    passwordRules: string;
};
