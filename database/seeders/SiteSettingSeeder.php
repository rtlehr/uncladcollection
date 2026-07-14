<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            $this->setting('general', 'site_name', 'Unclad Collection', 'text', 'The public name of the website.'),
            $this->setting('general', 'site_tagline', 'Licensed imagery and thoughtful stories for the nudist community.', 'text', 'Optional tagline displayed with the site name.'),
            $this->setting('general', 'contact_email', 'info@uncladcollection.com', 'email', 'Primary public contact email address.'),

            $this->setting('branding', 'active_theme', 'professional', 'text', 'Current active site theme.'),
            $this->setting('branding', 'primary_color', '#1E2A38', 'color', 'Primary brand color.'),
            $this->setting('branding', 'secondary_color', '#50634D', 'color', 'Secondary brand color.'),
            $this->setting('branding', 'accent_color', '#D9824B', 'color', 'Accent color used for links, highlights, and calls to action.'),
            $this->setting('branding', 'logo_full', null, 'image', 'Full stacked logo for spacious layouts.'),
            $this->setting('branding', 'logo_horizontal', null, 'image', 'Horizontal logo for the public site header.'),
            $this->setting('branding', 'logo_icon', null, 'image', 'Graphic-only logo for compact placements.'),
            $this->setting('branding', 'logo_light', null, 'image', 'Light logo for dark backgrounds.'),
            $this->setting('branding', 'logo_dark', null, 'image', 'Dark logo for light backgrounds.'),
            $this->setting('branding', 'watermark_logo', null, 'image', 'Transparent watermark logo.'),
            $this->setting('branding', 'social_image', null, 'image', 'Default 1200 by 630 social sharing image.'),
            $this->setting('branding', 'email_logo', null, 'image', 'Email-safe logo.'),
            $this->setting('branding', 'app_icon', null, 'image', 'Square app icon master.'),
            $this->setting('branding', 'watermark_enabled', 'true', 'boolean', 'Enable branded preview watermarks.'),
            $this->setting('branding', 'watermark_opacity', '70', 'integer', 'Watermark opacity percentage.'),
            $this->setting('branding', 'watermark_position', 'center', 'text', 'Watermark position.'),
            $this->setting('branding', 'watermark_scale', '35', 'integer', 'Watermark width percentage.'),
            $this->setting('branding', 'watermark_margin', '24', 'integer', 'Watermark margin in pixels.'),
            $this->setting('branding', 'footer_text', '© Unclad Collection. All rights reserved.', 'text', 'Footer copyright text.'),

            $this->setting('community', 'allow_comments', 'true', 'boolean', 'Allow users to comment on posts.', false),
            $this->setting('community', 'require_comment_approval', 'true', 'boolean', 'Require admin approval before comments appear publicly.', false),

            $this->setting('commerce', 'currency', 'USD', 'text', 'Default store currency.'),

            $this->setting('social', 'facebook_url', null, 'url', 'Facebook page URL.'),
            $this->setting('social', 'instagram_url', null, 'url', 'Instagram profile URL.'),
            $this->setting('social', 'youtube_url', null, 'url', 'YouTube channel URL.'),
            $this->setting('social', 'pinterest_url', null, 'url', 'Pinterest profile URL.'),
            $this->setting('social', 'x_account_url', null, 'url', 'X account URL.'),

            $this->setting('seo', 'default_meta_title', 'Unclad Collection', 'text', 'Default page title for SEO.'),
            $this->setting('seo', 'default_meta_description', 'Natural, respectful stock imagery and thoughtful writing for the nudist community.', 'textarea', 'Default meta description for SEO.'),

            $this->setting('homepage', 'hero_eyebrow', 'Authentic imagery. Thoughtful stories.', 'text', 'Small heading displayed above the homepage title.'),
            $this->setting('homepage', 'hero_title', 'A more natural way to represent nudist life.', 'text', 'Main homepage headline.'),
            $this->setting('homepage', 'hero_description', 'Discover respectful, community-centered photography and writing created for resorts, clubs, publishers, advocates, and people who value authentic representation.', 'textarea', 'Supporting homepage hero copy.'),
            $this->setting('homepage', 'hero_image_id', null, 'integer', 'Optional image ID used in the homepage hero.'),
            $this->setting('homepage', 'hero_primary_button_label', 'Browse Images', 'text', 'Primary hero button label.'),
            $this->setting('homepage', 'hero_primary_button_url', '/images', 'text', 'Primary hero button URL.'),
            $this->setting('homepage', 'hero_secondary_button_label', 'Read the Blog', 'text', 'Secondary hero button label.'),
            $this->setting('homepage', 'hero_secondary_button_url', '/blog', 'text', 'Secondary hero button URL.'),

            $this->setting('homepage', 'show_statistics', 'true', 'boolean', 'Show homepage library statistics.'),
            $this->setting('homepage', 'statistics_heading', 'Built for authentic representation', 'text', 'Heading above homepage statistics.'),

            $this->setting('homepage', 'show_featured_collections', 'true', 'boolean', 'Show featured collections on the homepage.'),
            $this->setting('homepage', 'collections_eyebrow', 'Curated collections', 'text', 'Small heading above featured collections.'),
            $this->setting('homepage', 'collections_title', 'Explore imagery with purpose', 'text', 'Featured collections section title.'),
            $this->setting('homepage', 'collections_description', 'Browse carefully organized collections created for editorial, educational, community, and marketing use.', 'textarea', 'Featured collections section description.'),
            $this->setting('homepage', 'featured_collection_ids', '[]', 'json', 'Optional JSON array of collection IDs shown on the homepage.'),

            $this->setting('homepage', 'show_featured_images', 'true', 'boolean', 'Show featured images on the homepage.'),
            $this->setting('homepage', 'images_eyebrow', 'Image library', 'text', 'Small heading above featured images.'),
            $this->setting('homepage', 'images_title', 'Photography that feels honest', 'text', 'Featured images section title.'),
            $this->setting('homepage', 'images_description', 'Discover natural, respectful imagery suited for publications, campaigns, resorts, organizations, and personal projects.', 'textarea', 'Featured images section description.'),
            $this->setting('homepage', 'featured_image_ids', '[]', 'json', 'Optional JSON array of image IDs shown on the homepage.'),

            $this->setting('homepage', 'show_latest_articles', 'true', 'boolean', 'Show latest blog articles on the homepage.'),
            $this->setting('homepage', 'articles_eyebrow', 'Ideas and experiences', 'text', 'Small heading above latest articles.'),
            $this->setting('homepage', 'articles_title', 'Stories from the community', 'text', 'Latest articles section title.'),
            $this->setting('homepage', 'articles_description', 'Read practical guidance, personal experiences, advocacy, and thoughtful perspectives on nudist life.', 'textarea', 'Latest articles section description.'),

            $this->setting('homepage', 'why_title', 'Made for a community that deserves better representation', 'text', 'Why Unclad Collection section title.'),
            $this->setting('homepage', 'why_description', 'Generic stock libraries rarely understand the difference between nudism and sexualized imagery. Unclad Collection is designed around authenticity, respect, normalcy, and community.', 'textarea', 'Why Unclad Collection section description.'),

            $this->setting('homepage', 'cta_title', 'Find the right image. Tell a better story.', 'text', 'Homepage call-to-action title.'),
            $this->setting('homepage', 'cta_description', 'Create an account to save favorites, purchase licensed downloads, and stay connected with new photography and articles.', 'textarea', 'Homepage call-to-action description.'),
            $this->setting('homepage', 'cta_button_label', 'Create an Account', 'text', 'Homepage call-to-action button label.'),
        ];

        foreach ($settings as $setting) {
            $record = SiteSetting::firstOrNew([
                'group_name' => $setting['group_name'],
                'setting_key' => $setting['setting_key'],
            ]);

            if (! $record->exists) {
                $record->setting_value = $setting['setting_value'];
            }

            $record->setting_type = $setting['setting_type'];
            $record->description = $setting['description'];
            $record->is_public = $setting['is_public'];
            $record->save();
        }
    }

    private function setting(
        string $group,
        string $key,
        ?string $value,
        string $type,
        string $description,
        bool $public = true,
    ): array {
        return [
            'group_name' => $group,
            'setting_key' => $key,
            'setting_value' => $value,
            'setting_type' => $type,
            'description' => $description,
            'is_public' => $public,
        ];
    }
}
