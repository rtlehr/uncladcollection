<?php

namespace App\Contracts;

interface AssetAiProvider
{
    public function key(): string;

    public function label(): string;

    public function model(): string;

    public function isConfigured(): bool;

    /**
     * @return array{
     *     provider: string,
     *     model: string,
     *     suggestions: array<string, mixed>,
     *     usage: array{input_tokens: int|null, output_tokens: int|null, total_tokens: int|null}
     * }
     */
    public function analyze(string $imagePath, array $context = []): array;
}
