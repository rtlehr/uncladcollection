export type StatusBadgeTone =
    | 'neutral'
    | 'primary'
    | 'success'
    | 'warning'
    | 'danger'
    | 'info';

export interface StatusBadgeConfig {
    label: string;
    tone: StatusBadgeTone;
}

const STATUS_BADGES: Record<string, StatusBadgeConfig> = {
    active: { label: 'Active', tone: 'success' },
    inactive: { label: 'Inactive', tone: 'neutral' },
    disabled: { label: 'Disabled', tone: 'danger' },
    draft: { label: 'Draft', tone: 'neutral' },
    published: { label: 'Published', tone: 'success' },
    scheduled: { label: 'Scheduled', tone: 'info' },
    featured: { label: 'Featured', tone: 'primary' },
    pending: { label: 'Pending', tone: 'warning' },
    approved: { label: 'Approved', tone: 'success' },
    hidden: { label: 'Hidden', tone: 'neutral' },
    spam: { label: 'Spam', tone: 'danger' },
    deleted: { label: 'Deleted', tone: 'danger' },
    reviewed: { label: 'Reviewed', tone: 'info' },
    dismissed: { label: 'Dismissed', tone: 'neutral' },
    paid: { label: 'Paid', tone: 'success' },
    failed: { label: 'Failed', tone: 'danger' },
    canceled: { label: 'Canceled', tone: 'neutral' },
    cancelled: { label: 'Cancelled', tone: 'neutral' },
    refunded: { label: 'Refunded', tone: 'info' },
    partially_refunded: { label: 'Partially Refunded', tone: 'warning' },
    purchased: { label: 'Purchased', tone: 'success' },
    downloadable: { label: 'Downloadable', tone: 'info' },
    ai: { label: 'AI', tone: 'info' },
    ai_generated: { label: 'AI Generated', tone: 'info' },
    member: { label: 'Member', tone: 'neutral' },
    editor: { label: 'Editor', tone: 'info' },
    moderator: { label: 'Moderator', tone: 'warning' },
    admin: { label: 'Admin', tone: 'primary' },
};

function humanizeStatus(status: string): string {
    return status
        .replace(/[-_]+/g, ' ')
        .trim()
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

export function getStatusBadgeConfig(status: string): StatusBadgeConfig {
    const normalizedStatus = status.trim().toLowerCase();

    return STATUS_BADGES[normalizedStatus] ?? {
        label: humanizeStatus(normalizedStatus),
        tone: 'neutral',
    };
}
