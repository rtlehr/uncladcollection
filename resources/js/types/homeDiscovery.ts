import type { HomeDiscoveryCollectionPlacement, HomeImage } from '@/types/home';
import type { RelatedPublicAsset } from '@/types/publicAsset';

export type HomepageDiscoverySection = {
    key: 'primary_collections' | 'recommended' | 'trending' | 'featured_assets' | 'secondary_collections';
    label: string;
    eyebrow: string | null;
    heading: string | null;
    description: string | null;
    item_limit: number;
    items: Array<HomeDiscoveryCollectionPlacement | RelatedPublicAsset | HomeImage>;
};
