<?php

namespace App\Support\Seo;

use App\Models\DcForm;
use App\Models\DcProject;
use Illuminate\Support\Str;

class PublicPageSeo
{
    public static function toolDescription(DcProject $tool, int $limit = 160): string
    {
        $text = trim((string) ($tool->purpose ?: $tool->name));

        return Str::limit(strip_tags($text), $limit);
    }

    public static function formDescription(DcForm $form, int $limit = 160): string
    {
        $text = trim((string) ($form->description ?: $form->title.' - '.$form->project?->name));

        return Str::limit(strip_tags($text), $limit);
    }

    /** @return array<string, mixed> */
    public static function webPageJsonLd(string $name, string $url, ?string $description = null): array
    {
        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $name,
            'url' => $url,
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => config('app.name', 'Tilkit'),
                'url' => config('app.url') ?: url('/'),
            ],
        ];
        if ($description !== null && $description !== '') {
            $graph['description'] = $description;
        }

        return $graph;
    }

    public static function pageTitle(string $primary, ?string $suffix = null): string
    {
        $suffix ??= config('app.name', 'Tilkit');

        return $primary.' | '.$suffix;
    }
}
