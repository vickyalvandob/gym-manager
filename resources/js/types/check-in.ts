import type { MemberStatus, PaginationLink } from './member';

export type CheckInEligibility = {
    can_check_in: boolean;
    reason: string | null;
    latest_check_in_at: string | null;
    next_allowed_at: string | null;
};

export type CheckInMembershipSummary = {
    id: number;
    plan_name: string;
    end_date: string;
};

export type CheckInMemberSearchResult = {
    id: number;
    member_number: string;
    name: string;
    phone: string;
    photo_url: string | null;
    status: MemberStatus;
    status_label: string;
    membership: CheckInMembershipSummary | null;
    eligibility: CheckInEligibility;
};

export type CheckInListItem = {
    id: number;
    checked_in_at: string;
    member: {
        id: number;
        member_number: string;
        name: string;
        phone: string;
        photo_url: string | null;
    };
    membership: CheckInMembershipSummary;
    created_by: {
        id: number;
        name: string;
    } | null;
};

export type PaginatedCheckIns = {
    data: CheckInListItem[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
};

export type CheckInFilters = {
    member_search: string;
    history_search: string;
    date_from: string;
    date_to: string;
    per_page: number;
};

export type CheckInIndexProps = {
    memberSearchResults: CheckInMemberSearchResult[];
    recentCheckIns: CheckInListItem[];
    history: PaginatedCheckIns;
    todayCount: number;
    filters: CheckInFilters;
    duplicateWindowMinutes: number;
};
