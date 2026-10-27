<?php

namespace App\Support\Tilkit;

/**
 * Plain-language labels for workbook capture forms (Community Data List).
 * Acronyms are spelled out first, then shown in brackets.
 */
class WorkbookFormLabel
{
    /** @var array<string, string> */
    private const CAPTIONS = [
        'A' => 'Indicator category (broad group for this sign)',
        'B' => 'Indicator type and sub-type',
        'C' => 'Specific indicator — local name, English name, and scientific name if known',
        'D' => 'What the sign looks or sounds like (behaviour)',
        'E' => 'What weather it predicts, in local words',
        'F' => 'Locality and landscape where the sign is usually observed',
        'G' => 'Ethnic community and language of the local name',
        'H' => 'What aspect of weather it predicts (rain onset, rain stop, spread, or amount)',
        'I' => 'Sectors affected by this sign (for example crops, livestock, water)',
        'J' => 'How long the community has trusted this sign (ancient to newly recognised)',
        'K' => 'How far ahead the sign usually warns (months, weeks, or days)',
        'L' => 'Historical reliability rating',
        'M' => 'Current-season reliability rating',
        'N' => 'Was this sign sighted during this monitoring season?',
        'O' => 'Where and when the sign was sighted (description)',
        'P' => 'Predicted rain onset — earliest likely date',
        'Q' => 'Predicted rain onset — latest likely date',
        'R' => 'Predicted rain cessation — earliest likely date',
        'S' => 'Predicted rain cessation — latest likely date',
        'T' => 'Predicted rainfall distribution across the area',
        'U' => 'Rainfall tercile forecast from this sign (above normal, normal, below normal)',
        'V' => 'How useful this sign is for decisions this season',
        'W' => 'Recommended actions or strategies linked to this forecast',
        'X' => 'Sector impact ratings (positive or negative per sector)',
        'Y' => 'Photos of the sign',
        'Z' => 'Global Positioning System (GPS) location of the sighting',
        'AA' => 'Landmark near the Global Positioning System (GPS) point',
        'AB' => 'Name of observer or observing group',
        'AC' => 'Confirmed by community or Focus Group Discussion (FGD)?',
        'AD' => 'Approved by lead Indigenous and Traditional Knowledge (ITK) holder?',
        'AE' => 'Verified by a technical expert (extension, drought monitor, or meteorology)?',
        'AF' => 'Entered by (name and date)',
        'AG' => 'Reliability analysis notes (free text)',
        'AH' => 'Unique indicator identifier',
        'AI' => 'Community or monitoring site',
        'AJ' => 'Ward or local administrative area',
        'AK' => 'County or region',
        'AL' => 'Country',
        'AM' => 'Monitoring season (month range)',
        'AN' => 'Date this row was collected',
        'AO' => 'Collection year (with season months)',
        'AP' => 'Validation status (workshop and data desk)',
        'AQ' => 'Notes on validation or disputes',
        'AR' => 'Follow-up actions after validation',
        'AS' => 'Date confirmed',
        'AT' => 'Date approved',
        'AU' => 'Workshop plenary rating (3 / 2 / 1 scale)',
        'AV' => 'Secondary plenary rating (3 / 2 / 1 scale)',
        'AZ' => 'Season outcome (what actually happened)',
        'BA' => 'Season review notes',
        'BB' => 'Do-no-harm flag — limit sharing of sensitive details',
        'BC' => 'Community consent for sharing this record',
        'BD' => 'Consent contact name',
        'BE' => 'Consent contact role',
        'BF' => 'Consent documentation reference',
        'BG' => 'Number of consenting representatives',
        'BH' => 'Demographics — age group (workshop)',
        'BI' => 'Demographics — gender (workshop)',
        'BJ' => 'Demographics — livelihood (workshop)',
        'BK' => 'Demographics — years living in area (workshop)',
        'BU' => 'Integration — external outlook source notes',
        'BV' => 'Integration — agreement with official forecast (text)',
        'BW' => 'Integration — disagreement notes',
        'BX' => 'Integration — action taken after comparison',
        'BY' => 'Integration — responsible officer',
        'BZ' => 'Integration — date recorded',
        'CI' => 'Date the sign was sighted in the field',
        'CK' => 'Field sighting reference code',
        'CL' => 'Field sighting batch or visit label',
        'CN' => 'Climate change relevance notes',
        'CO' => 'Climate change flag explanation',
        'CQ' => 'Vegetation condition score (local observation)',
        'CR' => 'Vegetation Condition Index 3-month (VCI3M) value from official feed',
        'CW' => 'Seasonal review — lessons learned',
        'CX' => 'Seasonal review — facilitator',
        'CY' => 'Seasonal review — sign still trusted?',
        'CZ' => 'Seasonal review — recommended protocol change',
        'DA' => 'Archive — include in long-term dataset?',
        'DB' => 'Archive — date archived',
        'DC' => 'Archive — archivist or data desk',
        'DD' => 'Workshop participant count (demographics)',
        'DE' => 'Workshop female participant count',
        'DF' => 'Workshop male participant count',
        'DG' => 'Workshop other gender count',
        'DJ' => 'Kenya Meteorological Society (KMSA) county rainfall outlook tercile',
        'DL' => 'National Drought Management Authority (NDMA) drought phase',
        'DM' => 'Official outlook agrees with community tercile?',
        'DN' => 'Notes when official and community forecasts differ',
        'DO' => 'Use prior literature multiplier in tally?',
        'DP' => 'Prior multiplier override notes',
    ];

    public static function caption(array $column): string
    {
        $letter = strtoupper($column['column']);
        $text = self::CAPTIONS[$letter] ?? self::fallbackFromRegistry($column);

        return $text.' · Workbook column '.$letter;
    }

    public static function captionForLetter(string $letter): string
    {
        $meta = WorkbookColumnRegistry::forColumn($letter);

        return $meta !== null
            ? self::caption($meta)
            : self::expandAcronyms($letter).' · Workbook column '.strtoupper($letter);
    }

    public static function hint(array $column): ?string
    {
        $letter = strtoupper($column['column']);
        $kind = WorkbookColumnRegistry::inputKind($column);

        return match (true) {
            in_array($letter, ['N', 'AC', 'AD', 'AE', 'BB', 'CY', 'DA', 'DM', 'DO'], true) => 'Choose Yes (1) if true, No (0) if not.',
            in_array($kind, ['rating', 'rating_321'], true) => 'Use 3 = high or above normal, 2 = medium or normal, 1 = low or below normal, 0 = not sure (where applicable).',
            $kind === 'tercile' => '3 = above-normal rains, 2 = normal, 1 = below-normal (tercile forecast).',
            $kind === 'history_scale' => '3 = ancient / well known, 2 = known for some time, 1 = newly recognised sign.',
            $kind === 'lead_time_class' => '3 = warns months ahead, 2 = weeks ahead, 1 = days ahead.',
            $letter === 'BC' => 'Record who agreed to share data and at what level (column BC).',
            $letter === 'I' => 'Add one sector per line (for example crops, livestock, health, water).',
            $letter === 'X' => 'Rate each sector; use + or − codes as in your workshop guide.',
            $letter === 'Y' => 'Upload at least three clear photos when possible.',
            $letter === 'Z' => 'Tap the map or use your device location to set coordinates.',
            default => null,
        };
    }

    private static function fallbackFromRegistry(array $column): string
    {
        $field = (string) ($column['field'] ?? 'Field');
        $field = preg_replace('/\s*\(1\/0\)\s*/i', '', $field) ?? $field;
        $field = preg_replace('/\s*\(3\/2\/1\/0\)\s*/i', '', $field) ?? $field;

        return self::expandAcronyms(trim($field));
    }

    public static function expandAcronyms(string $text): string
    {
        $replacements = [
            '/\bFGD\b/' => 'Focus Group Discussion (FGD)',
            '/\bITK\b/' => 'Indigenous and Traditional Knowledge (ITK)',
            '/\bGPS\b/' => 'Global Positioning System (GPS)',
            '/\bKMSA\b/' => 'Kenya Meteorological Society (KMSA)',
            '/\bNDMA\b/' => 'National Drought Management Authority (NDMA)',
            '/\bVCI3M\b/' => 'Vegetation Condition Index 3-month (VCI3M)',
            '/\bICT\b/' => 'Information and Communication Technology (ICT)',
            '/\bKMD\b/' => 'Kenya Meteorological Department (KMD)',
            '/\bM&E\b/' => 'Monitoring and Evaluation (M&E)',
        ];

        $out = $text;
        foreach ($replacements as $pattern => $replacement) {
            if (preg_match($pattern, $out) && ! str_contains($out, $replacement)) {
                $out = preg_replace($pattern, $replacement, $out) ?? $out;
            }
        }

        return $out;
    }
}
