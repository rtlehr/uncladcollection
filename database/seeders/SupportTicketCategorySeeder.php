<?php

namespace Database\Seeders;

use App\Models\SupportTicketCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupportTicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Account Access', 'Help with account access, profile, or security.', 'normal'],
            ['Purchase or Billing', 'Questions about orders, charges, refunds, or receipts.', 'high'],
            ['Licensing', 'Questions about license rights or license records.', 'normal'],
            ['Download Problem', 'Problems downloading purchased files.', 'high'],
            ['Asset Issue', 'Report an issue with an asset or its files.', 'normal'],
            ['Advertising', 'Advertiser account, campaign, creative, or billing support.', 'normal'],
            ['Technical Issue', 'Unexpected application behavior or technical errors.', 'high'],
            ['Content Concern', 'Report content that may need review.', 'high'],
            ['General Question', 'Questions that do not fit another category.', 'normal'],
        ];

        foreach ($categories as $index => [$name, $description, $priority]) {
            SupportTicketCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'default_priority' => $priority,
                    'is_public' => true,
                    'is_member' => true,
                    'is_advertiser' => true,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        }
    }
}
