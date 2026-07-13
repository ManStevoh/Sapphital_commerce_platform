<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Services;

use Illuminate\Validation\ValidationException;

final class SectionTreeValidator
{
    /**
     * @param  array<string, mixed>|null  $bodyJson
     */
    public function validate(?array $bodyJson): void
    {
        if ($bodyJson === null) {
            return;
        }

        if (! isset($bodyJson['sections']) || ! is_array($bodyJson['sections'])) {
            throw ValidationException::withMessages([
                'body_json' => ['body_json.sections must be an array.'],
            ]);
        }

        foreach ($bodyJson['sections'] as $index => $section) {
            if (! is_array($section)) {
                throw ValidationException::withMessages([
                    "body_json.sections.{$index}" => ['Each section must be an object.'],
                ]);
            }

            $type = $section['type'] ?? null;

            if (! is_string($type) || $type === '') {
                throw ValidationException::withMessages([
                    "body_json.sections.{$index}.type" => ['Section type is required.'],
                ]);
            }

            match ($type) {
                'rich-text' => $this->validateRichText($section, $index),
                'image-banner' => $this->validateImageBanner($section, $index),
                'faq-accordion' => $this->validateFaqAccordion($section, $index),
                'testimonials' => $this->validateTestimonials($section, $index),
                'video-embed' => $this->validateVideoEmbed($section, $index),
                default => throw ValidationException::withMessages([
                    "body_json.sections.{$index}.type" => ["Unsupported section type: {$type}."],
                ]),
            };
        }
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function validateRichText(array $section, int $index): void
    {
        if (! isset($section['content']) || ! is_string($section['content'])) {
            throw ValidationException::withMessages([
                "body_json.sections.{$index}.content" => ['Rich text content is required.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function validateImageBanner(array $section, int $index): void
    {
        foreach (['heading', 'image_url'] as $field) {
            if (! isset($section[$field]) || ! is_string($section[$field]) || $section[$field] === '') {
                throw ValidationException::withMessages([
                    "body_json.sections.{$index}.{$field}" => ["{$field} is required for image-banner."],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function validateFaqAccordion(array $section, int $index): void
    {
        if (! isset($section['items']) || ! is_array($section['items']) || $section['items'] === []) {
            throw ValidationException::withMessages([
                "body_json.sections.{$index}.items" => ['FAQ section requires at least one item.'],
            ]);
        }

        foreach ($section['items'] as $itemIndex => $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach (['question', 'answer'] as $field) {
                if (! isset($item[$field]) || ! is_string($item[$field]) || trim($item[$field]) === '') {
                    throw ValidationException::withMessages([
                        "body_json.sections.{$index}.items.{$itemIndex}.{$field}" => ['FAQ items require question and answer.'],
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function validateTestimonials(array $section, int $index): void
    {
        if (! isset($section['items']) || ! is_array($section['items']) || $section['items'] === []) {
            throw ValidationException::withMessages([
                "body_json.sections.{$index}.items" => ['Testimonials section requires at least one item.'],
            ]);
        }

        foreach ($section['items'] as $itemIndex => $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach (['quote', 'author'] as $field) {
                if (! isset($item[$field]) || ! is_string($item[$field]) || trim($item[$field]) === '') {
                    throw ValidationException::withMessages([
                        "body_json.sections.{$index}.items.{$itemIndex}.{$field}" => ['Testimonials require quote and author.'],
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function validateVideoEmbed(array $section, int $index): void
    {
        if (! isset($section['embed_url']) || ! is_string($section['embed_url']) || $section['embed_url'] === '') {
            throw ValidationException::withMessages([
                "body_json.sections.{$index}.embed_url" => ['Video embed URL is required.'],
            ]);
        }
    }
}
