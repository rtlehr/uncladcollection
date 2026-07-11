import type { PaginationLink } from '@/types/common';

export interface AdminCommentUser {
    id: number;
    name: string;
    username?: string | null;
    email?: string | null;
    avatar_url?: string | null;
}

export interface AdminCommentable {
    id: number;
    title?: string;
    slug?: string;
}

export interface AdminCommentRecord {
    id: number;
    body: string;
    status: string;
    depth: number;
    likes_count: number;
    reports_count: number;
    is_pinned: boolean;
    created_at: string;
    user: AdminCommentUser | null;
    commentable: AdminCommentable | null;
}

export interface AdminCommentFilters {
    search: string;
    status: string;
    filter: string;
}

export interface PaginatedAdminComments {
    data: AdminCommentRecord[];
    links: PaginationLink[];
    meta?: unknown;
    from?: number | null;
    to?: number | null;
    total?: number | null;
}

export interface AdminReportedComment {
    id: number;
    body: string;
    status: string;
    user?: AdminCommentUser | null;
}

export interface AdminCommentReport {
    id: number;
    reason: string | null;
    details: string | null;
    status: string;
    created_at: string;
    user?: AdminCommentUser | null;
    reviewer?: AdminCommentUser | null;
    comment?: AdminReportedComment | null;
}

export interface AdminCommentReportFilters {
    status: string;
}

export interface PaginatedAdminCommentReports {
    data: AdminCommentReport[];
    links: PaginationLink[];
    meta?: unknown;
    from?: number | null;
    to?: number | null;
    total?: number | null;
}
