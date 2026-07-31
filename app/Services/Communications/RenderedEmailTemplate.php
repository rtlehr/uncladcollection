<?php

namespace App\Services\Communications;

final readonly class RenderedEmailTemplate
{
    public function __construct(
        public string $key,
        public string $subject,
        public ?string $previewText,
        public string $html,
        public string $text,
    ) {}
}
