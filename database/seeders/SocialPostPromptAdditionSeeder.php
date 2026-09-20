<?php

namespace Database\Seeders;

use App\Models\SocialPostPromptAddition;
use Illuminate\Database\Seeder;

class SocialPostPromptAdditionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'About Unclad Collection',
                'category' => 'Brand context',
                'sort_order' => 10,
                'prompt_text' => 'Context only—use this information when it is relevant to the requested subject. Unclad Collection is a nudist/naturist creative marketplace and content platform. It offers nudist-focused stock photography, artwork and digital creative assets; a Creative Image Studio for creating custom graphics; image and creative licensing options; blog content featuring positive nudist stories, experiences and discussion topics; and creative resources for nudist resorts, clubs, bloggers, artists and the broader naturist community. Do not invent products, services or features that are not described here or in the Subject / Topic.',
            ],
            [
                'name' => 'Positive Naturism Tone',
                'category' => 'Tone',
                'sort_order' => 20,
                'prompt_text' => 'When writing about nudism or naturism, keep the message positive, respectful, wholesome, nonsexual and community-oriented. Themes may include confidence, body acceptance, freedom from shame, comfort, respect, normalizing nonsexual nudity, community and enjoying life naturally.',
            ],
            [
                'name' => 'Do Not Invent Features',
                'category' => 'Accuracy',
                'sort_order' => 30,
                'prompt_text' => 'Do not invent products, services, business features, statistics, partnerships, pricing, claims or capabilities. Only mention offerings explicitly supplied in the Subject / Topic or other selected prompt additions. If a detail is unknown, write around it rather than guessing.',
            ],
            [
                'name' => 'Include Unclad Collection URL',
                'category' => 'Promotion',
                'sort_order' => 40,
                'prompt_text' => 'For posts that promote or direct readers to Unclad Collection, include this exact URL in the finished post text: https://uncladcollection.com/ . Do not force the URL into a post that the Subject / Topic clearly intends to be non-promotional.',
            ],
            [
                'name' => 'Conversation and Engagement',
                'category' => 'Style',
                'sort_order' => 50,
                'prompt_text' => 'Favor natural social-media conversation over advertisement copy. Where appropriate, use an interesting question, observation or discussion point that invites respectful engagement. Avoid clickbait and do not make every post a sales pitch.',
            ],
        ];

        foreach ($items as $item) {
            SocialPostPromptAddition::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'category' => $item['category'],
                    'prompt_text' => $item['prompt_text'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ],
            );
        }
    }
}
