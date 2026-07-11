export interface AdminOrderLinkedImage {
    id: number;
    title: string;
    slug: string;
}

export interface AdminOrderItem {
    id: number;
    status: string;
    quantity: number;
    unit_price_formatted: string;
    total_price_formatted: string;
    image_title: string;
    license_name: string;
    image: AdminOrderLinkedImage | null;
}

export interface AdminOrderLicense {
    id: number;
    license_key: string;
    status: string;
    license_name: string;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    downloads_count: number;
    image: AdminOrderLinkedImage | null;
}

export interface AdminOrderUser {
    id: number;
    name: string;
    email: string;
}

export interface AdminOrderDetail {
    id: number;
    order_number: string;
    status: string;

    subtotal_formatted: string;
    total_formatted: string;

    subtotal_cents: number;
    discount_cents: number;
    tax_cents: number;
    total_cents: number;

    currency: string;

    payment_provider: string | null;
    payment_reference: string | null;
    stripe_checkout_session_id: string | null;
    stripe_payment_intent_id: string | null;

    paid_at: string | null;
    refunded_at: string | null;
    canceled_at: string | null;
    created_at: string | null;

    user: AdminOrderUser | null;
    items: AdminOrderItem[];
    licenses: AdminOrderLicense[];
}
