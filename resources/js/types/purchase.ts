import type { AssetOption } from '@/types/asset';
import type { PaginationLink } from '@/types/common';

export interface LicenseStatusSummary {
    key: 'active' | 'expiring_soon' | 'expired' | 'revoked' | 'refunded' | 'limit_reached';
    label: string;
    tone: 'success' | 'warning' | 'danger' | 'neutral' | 'info';
    can_download: boolean;
    message: string;
    is_expiring_soon: boolean;
    days_until_expiry: number | null;
}
export interface PurchaseOrderSummary { id:number|null; order_number:string|null; status?:string|null; paid_at:string|null; total_formatted:string|null; line_total_formatted:string|null; }
export interface PurchaseConfigurationLabel { group:string; values:string[]; }
export interface PurchaseConfigurationSnapshot { labels?:PurchaseConfigurationLabel[]; selections?:Record<string,unknown>; [key:string]:unknown; }
export interface PurchasedProduct { id:number; title:string; slug:string; creator:string|null; preview_url:string|null; is_ai_generated:boolean; asset_type_label:string; public_url:string; }
export interface PurchasedAsset { id:number; kind:'asset'|'legacy_image'; license_key:string; license_name:string; status:LicenseStatusSummary; downloads_used:number; download_limit:number|null; starts_at:string|null; expires_at:string|null; can_download:boolean; detail_url:string; download_url:string|null; download_all_url?:string|null; quantity:number; configuration:PurchaseConfigurationSnapshot|null; included_files_count:number; product:PurchasedProduct; order:PurchaseOrderSummary; }
export interface PaginatedPurchases { data:PurchasedAsset[]; links:PaginationLink[]; meta?:unknown; }
export interface PurchaseDetailProduct extends PurchasedProduct { description:string|null; created_at:string|null; collection:AssetOption|null; categories:AssetOption[]; tags:AssetOption[]; }
export interface PurchasedIncludedFile { id:number|null; name:string; role:string|null; media_type:string|null; extension:string|null; mime_type:string|null; size_bytes:number|null; is_available:boolean; download_url:string|null; }
export interface PurchaseDownloadHistory { id:number; type:string|null; filename:string|null; status:string; downloaded_at:string|null; }
export interface PurchaseStatusHistory { id:number; from_status:string|null; to_status:string; message:string|null; changed_at:string|null; }
export interface PurchaseDetailRecord extends Omit<PurchasedAsset,'detail_url'|'included_files_count'> { license_terms:string|null; current_license_terms:string|null; terms_changed:boolean; terms_version:number|null; status_reason:string|null; download_all_url:string|null; certificate_url:string; proof_of_purchase_url:string; support_url:string; pricing:Record<string,unknown>|null; included_files:PurchasedIncludedFile[]; download_history:PurchaseDownloadHistory[]; status_history:PurchaseStatusHistory[]; product:PurchaseDetailProduct; }
