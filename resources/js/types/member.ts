export type MemberStatus = 'active' | 'inactive';
export type MemberGender = 'male' | 'female';
export type MemberMembershipStatus = 'upcoming' | 'active' | 'expired';
export type MembershipDurationUnit = 'day' | 'week' | 'month' | 'year';

export type SelectOption = {
    value: string;
    label: string;
};

export type MemberSummary = {
    id: number;
    member_number: string;
    name: string;
    phone: string;
    email: string | null;
    photo_url: string | null;
    status: MemberStatus;
    status_label: string;
    membership: MemberMembership | null;
    created_at: string | null;
};

export type MemberMembership = {
    id: number;
    membership_plan_id: number;
    renewed_from_id: number | null;
    plan_name: string;
    duration: number;
    duration_unit: MembershipDurationUnit;
    duration_unit_label: string;
    duration_label: string;
    price: string;
    start_date: string;
    end_date: string;
    status: MemberMembershipStatus;
    status_label: string;
    days_remaining: number | null;
    is_expiring_soon: boolean;
    payment: MembershipPayment | null;
    created_at: string | null;
};

export type MembershipPlanOption = {
    id: number;
    name: string;
    duration: number;
    duration_unit: MembershipDurationUnit;
    duration_label: string;
    price: string;
};

export type MembershipDefaults = {
    assign_start_date: string;
    renewal_source_id: number | null;
    renewal_plan_id: number | null;
    renewal_start_date: string;
};

export type MemberDetail = MemberSummary & {
    gender: MemberGender | null;
    gender_label: string | null;
    birth_date: string | null;
    address: string | null;
    emergency_contact: string | null;
    notes: string | null;
    updated_at: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedMembers = {
    data: MemberSummary[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

export type PaginatedMemberMemberships = {
    data: MemberMembership[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};
import type { MembershipPayment } from './payment';
