<?php

namespace App\Support\Marketing;

use Illuminate\Support\Str;

class HomeTilkitAbout
{
    /**
     * @return array{
     *     intro: string,
     *     platform_note: string,
     *     origins: list<string>,
     *     implementations: list<array{title: string, text: string, datetime: ?string}>,
     *     timeline: list<array{label: string, text: string, datetime: string}>
     * }
     */
    public static function content(): array
    {
        return [
            'intro' => 'TILKIT is a participatory method and workbook that turns the weather signs communities already observe-tree flowering, wind shifts, animal behaviour, and more-into a shared seasonal forecast expressed in the same above-normal, normal, and below-normal (tercile) language national meteorological services use. That lets local knowledge be validated, weighted, tallied, and discussed alongside official outlooks rather than kept separate from science.',
            'platform_note' => 'Tilkit is the web platform that hosts the v4 Weather Indicator Log and broader institutional data collection for forms, governance, and analysis.',
            'origins' => [
                'TILKIT grew out of climate-smart agriculture training-of-trainers work in East Africa from March 2016 through December 2021, linked to CGIAR AICCRA and CRAFT with partners including SNV. Participatory studies with more than 1,100 farmers, extension agents, and community leaders in Kenya, Tanzania, and Uganda documented locality-specific indicators and consensus-based forecasting. The methodology was published in 2023 in the FARA Research Report (Osumba et al., Vol. 7(25):296–320).',
                'What began as group-work tables in workshops evolved into the multi-sheet Excel master template. Version 3 introduced the full weighting protocol; version 4 (reviewed 19 October 2026) extends the same record across the early-warning chain-124 columns from observation through tally, integration fields, and exports. This site implements that v4 contract in software, with rules-engine parity tested against the demo workbook.',
            ],
            'implementations' => [
                [
                    'title' => 'East Africa (2017–2021)',
                    'text' => 'Field testing and workshops across Kenya, Tanzania, and Uganda-including Kenyan counties such as Homa Bay, Kisumu, Narok, Machakos, and Embu. A documented July 2021 session in Narok County captured 24 community-trusted signs with meanings, lead times, and tercile votes.',
                    'datetime' => '2017-01/2021-12',
                ],
                [
                    'title' => 'Kenya (ongoing pilots)',
                    'text' => 'Community sites including Mutirithia in Laikipia, aligned with Kenya Meteorological Service Authority (KMSA) and National Drought Management Authority (NDMA) cycles; the Adaptation Consortium–led Green Schools approach places learners and school environmental clubs at the centre of local observation and dissemination.',
                    'datetime' => null,
                ],
                [
                    'title' => 'West Africa (2025–2026)',
                    'text' => 'The SCLoWFER (Strengthening Consensus-based Local Weather Forecasting for Enhanced Resilience) design was shared with ECOWAS meteorological services in May 2025; tri-border pilot work in Ghana, Burkina Faso, and Côte d\'Ivoire extends the same methodology with gender-responsive training of trainers.',
                    'datetime' => '2025-01/2026-12',
                ],
            ],
            'timeline' => [
                [
                    'label' => '2016 – 2021',
                    'text' => 'Concept and field validation under CSA training initiatives in Kenya, Tanzania, and Uganda.',
                    'datetime' => '2016-03/2021-12',
                ],
                [
                    'label' => 'July 2021',
                    'text' => 'Narok County workshop-24 signs recorded, weighted, and tallied into a community forecast.',
                    'datetime' => '2021-07',
                ],
                [
                    'label' => '2023',
                    'text' => 'FARA Research Report documents TILKIT as a replicable tool for agro-weather resilience.',
                    'datetime' => '2023',
                ],
                [
                    'label' => 'May 2025',
                    'text' => 'SCLoWFER proposal presented to West African met services; regional call to document indigenous indicators.',
                    'datetime' => '2025-05',
                ],
                [
                    'label' => 'October 2026',
                    'text' => 'Weather Indicator Log master template v4 (OND 2026) signed as the digital specification.',
                    'datetime' => '2026-10',
                ],
                [
                    'label' => '2026',
                    'text' => 'Tilkit web platform-capture, validation, tally, setup, and exports aligned to the v4 workbook.',
                    'datetime' => '2026',
                ],
            ],
        ];
    }

    public static function plainTextBody(bool $includePlatformNote = false): string
    {
        $c = self::content();
        $parts = [
            'About TILKIT',
            $c['intro'],
        ];

        if ($includePlatformNote) {
            $parts[] = $c['platform_note'];
        }

        $parts[] = 'Origins and history';
        array_push($parts, ...$c['origins']);

        $parts[] = 'Where it has been implemented';
        foreach ($c['implementations'] as $item) {
            $parts[] = $item['title'].': '.$item['text'];
        }

        $parts[] = 'Timeline at a glance';
        foreach ($c['timeline'] as $item) {
            $parts[] = $item['label'].': '.$item['text'];
        }

        return implode("\n\n", $parts);
    }

    public static function metaDescriptionShort(): string
    {
        return Str::limit(self::content()['intro'], 158, '…');
    }

    public static function metaDescriptionSocial(): string
    {
        $text = preg_replace('/\s+/u', ' ', self::plainTextBody()) ?? self::plainTextBody();

        return Str::limit(trim($text), 300, '…');
    }

    /** @return list<string> */
    public static function keywords(): array
    {
        return [
            'TILKIT',
            'Traditional Indigenous and Local Knowledge Tool',
            'seasonal weather forecast',
            'tercile forecast',
            'indigenous weather indicators',
            'community-based forecasting',
            'Weather Indicator Log v4',
            'Kenya',
            'Tanzania',
            'Uganda',
            'Ghana',
            'SCLoWFER',
            'Green Schools',
            'AICCRA',
            'CRAFT',
            'FARA Research Report',
            'Mutirithia',
            'Narok County',
            'ECOWAS',
            'KMSA',
            'NDMA',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function jsonLdGraph(string $pageUrl, string $pageTitle, string $appName): array
    {
        $c = self::content();
        $body = self::plainTextBody();
        $short = self::metaDescriptionShort();

        $timelineItems = [];
        foreach ($c['timeline'] as $i => $item) {
            $timelineItems[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['label'],
                'description' => $item['text'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $pageUrl.'#website',
                    'url' => $pageUrl,
                    'name' => $appName,
                    'description' => $short,
                    'inLanguage' => str_replace('_', '-', app()->getLocale()),
                ],
                [
                    '@type' => ['WebPage', 'AboutPage'],
                    '@id' => $pageUrl.'#webpage',
                    'url' => $pageUrl,
                    'name' => $pageTitle,
                    'description' => $short,
                    'isPartOf' => ['@id' => $pageUrl.'#website'],
                    'about' => [
                        '@type' => 'Thing',
                        'name' => 'TILKIT (Traditional Indigenous and Local Knowledge Tool)',
                        'description' => $c['intro'],
                    ],
                    'mainEntity' => ['@id' => $pageUrl.'#about-tilkit'],
                    'keywords' => implode(', ', self::keywords()),
                ],
                [
                    '@type' => 'Article',
                    '@id' => $pageUrl.'#about-tilkit',
                    'headline' => 'About TILKIT',
                    'description' => $c['intro'],
                    'articleBody' => $body,
                    'inLanguage' => str_replace('_', '-', app()->getLocale()),
                    'author' => [
                        '@type' => 'Organization',
                        'name' => $appName,
                    ],
                    'about' => [
                        '@type' => 'Thing',
                        'name' => 'TILKIT',
                        'alternateName' => 'Traditional Indigenous and Local Knowledge Tool',
                    ],
                ],
                [
                    '@type' => 'ItemList',
                    '@id' => $pageUrl.'#tilkit-timeline',
                    'name' => 'TILKIT timeline at a glance',
                    'description' => 'Key milestones from East Africa field validation through v4 digital platform.',
                    'itemListElement' => $timelineItems,
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => $pageUrl.'#application',
                    'name' => $appName,
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'url' => $pageUrl,
                    'description' => $body,
                    'featureList' => 'Weather Indicator Log v4, seasonal tercile tally, eligibility and weighting engine, multi-site seasons, validation workflows',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'USD',
                    ],
                ],
            ],
        ];
    }
}
