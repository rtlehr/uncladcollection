<?php

namespace App\Services\Ai\ContentStudio;

use App\Services\Ai\AiTextGateway;
use RuntimeException;

class SavedPromptRefiner
{
    public function __construct(private AiTextGateway $gateway) {}

    /** @return array{content:string,provider:string,model:string,usage:array<string,int|null>} */
    public function refine(string $currentPrompt, string $instruction, array $context = []): array
    {
        $prompt = <<<PROMPT
Rewrite the existing image-generation prompt according to the refinement instruction.

Return only the complete revised prompt. Do not explain the changes. Do not use markdown fences. Preserve all useful details that are not contradicted by the refinement. Integrate the requested change naturally throughout the prompt instead of appending a disconnected sentence.

CONTENT CONTEXT: {$context['content_context']}
OUTPUT MODE: {$context['output_mode']}
BODY DETAIL LEVEL: {$context['body_detail_level']}
DESCRIPTION DEPTH: {$context['description_depth']}
CHARACTER DETAIL LEVEL: {$context['character_detail_level']}
ENVIRONMENT DETAIL LEVEL: {$context['environment_detail_level']}

EXISTING PROMPT:
{$currentPrompt}

REFINEMENT INSTRUCTION:
{$instruction}
PROMPT;

        $result = $this->gateway->generate('image_prompt', $prompt, [
            'temperature' => 0.25,
            'max_tokens' => 3200,
        ]);

        $result['content'] = trim(preg_replace('/^(revised prompt:|prompt:)\s*/i', '', trim($result['content'])) ?? trim($result['content']));

        if ($result['content'] === '') {
            throw new RuntimeException('The AI returned an empty refined prompt.');
        }

        return $result;
    }
}
