<?php

namespace App\Services\Tilkit;

use App\Support\Tilkit\PilotSeasonCalendar;

/**
 * Pilot workbook rows for Kenya NEO and Ghana programs (v4 template aligned).
 */
class KenyaGhanaPilotDataset
{
    /** @return list<array<string, mixed>> */
    public function kenyaRows(): array
    {
        $bases = $this->kenyaBaseRows();
        $rows = [];

        foreach (['mam', 'ond'] as $seasonKey) {
            $profile = PilotSeasonCalendar::kenya($seasonKey);
            foreach ($bases as $base) {
                $rows[] = $this->enrichKenya($base, count($rows) + 1, $profile);
            }
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function ghanaRows(): array
    {
        $templates = [
            ['uid' => '01-ANI-FRO-001', 'name' => 'Tree frogs calling after dusk near farm ponds', 'site' => 'Tamale Central', 'ward' => 'Tamale Metropolitan', 'county' => 'Northern Region', 'lang' => 'Dagbani', 'gps' => '9.4034,-0.8420', 'southern' => false],
            ['uid' => '02-ATM-CLD-001', 'name' => 'Low thick clouds from the east before storms', 'site' => 'Tamale Central', 'ward' => 'Tamale Metropolitan', 'county' => 'Northern Region', 'lang' => 'Dagbani', 'gps' => '9.4051,-0.8398', 'southern' => false],
            ['uid' => '02-ATM-WND-001', 'name' => 'Harmattan haze with dry north-east winds', 'site' => 'Bolgatanga', 'ward' => 'Bolgatanga East', 'county' => 'Upper East Region', 'lang' => 'Frafra', 'gps' => '10.7856,-0.8513', 'southern' => false],
            ['uid' => '03-PLT-TRE-001', 'name' => 'Shea trees flowering early on the savanna', 'site' => 'Bolgatanga', 'ward' => 'Bolgatanga East', 'county' => 'Upper East Region', 'lang' => 'Frafra', 'gps' => '10.7812,-0.8477', 'southern' => false],
            ['uid' => '03-ANI-BRD-001', 'name' => 'Weaver birds nesting low before rains', 'site' => 'Wa Municipal', 'ward' => 'Wa Central', 'county' => 'Upper West Region', 'lang' => 'Wali', 'gps' => '10.0601,-2.5099', 'southern' => false],
            ['uid' => '01-AST-MON-001', 'name' => 'Crescent moon tilt after harvest moon', 'site' => 'Wa Municipal', 'ward' => 'Wa Central', 'county' => 'Upper West Region', 'lang' => 'Wali', 'gps' => '10.0588,-2.5055', 'southern' => false],
            ['uid' => '04-SOI-CRK-001', 'name' => 'Wide soil cracks on bare upland fields', 'site' => 'Cape Coast', 'ward' => 'Cape Coast Metro', 'county' => 'Central Region', 'lang' => 'Fante', 'gps' => '5.1053,-1.2466', 'southern' => true],
            ['uid' => '04-HYD-RIV-001', 'name' => 'Pra river sand bars exposed at crossing', 'site' => 'Cape Coast', 'ward' => 'Cape Coast Metro', 'county' => 'Central Region', 'lang' => 'Fante', 'gps' => '5.1022,-1.2501', 'southern' => true],
            ['uid' => '05-PLT-GRS-001', 'name' => 'Guinea grass greening on flood recession plains', 'site' => 'Tamale Central', 'ward' => 'Tamale Metropolitan', 'county' => 'Northern Region', 'lang' => 'Dagbani', 'gps' => '9.4010,-0.8455', 'southern' => false],
            ['uid' => '02-ATM-HUM-001', 'name' => 'Evening humidity rise with distant thunder', 'site' => 'Bolgatanga', 'ward' => 'Bolgatanga East', 'county' => 'Upper East Region', 'lang' => 'Frafra', 'gps' => '10.7889,-0.8530', 'southern' => false],
        ];

        $rows = [];
        foreach ($templates as $t) {
            if ($t['southern']) {
                $rows[] = $this->enrichGhana($t, count($rows) + 1, PilotSeasonCalendar::ghanaSouthernMajor());
                $rows[] = $this->enrichGhana($t, count($rows) + 1, PilotSeasonCalendar::ghanaSouthernMinor());
            } else {
                $rows[] = $this->enrichGhana($t, count($rows) + 1, PilotSeasonCalendar::ghanaNorthern());
            }
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function kenyaBaseRows(): array
    {
        $fixture = base_path('tests/Fixtures/Tilkit/demo_workbook_full.json');
        $rows = [];
        if (is_readable($fixture)) {
            $payload = json_decode(file_get_contents($fixture), true, 512, JSON_THROW_ON_ERROR);
            $seen = [];
            foreach ($payload['rows'] ?? [] as $row) {
                if ((string) ($row['AL'] ?? '') !== 'Kenya') {
                    continue;
                }
                $row['AI'] = str_replace('Mutirithia (DEMO)', 'Mutirithia NEO', (string) ($row['AI'] ?? ''));
                $row['AJ'] = str_replace('(demo)', 'NEO', (string) ($row['AJ'] ?? ''));
                $row['AK'] = str_replace('(demo)', '', (string) ($row['AK'] ?? ''));
                $key = ($row['AH'] ?? '').'|'.($row['AI'] ?? '');
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $rows[] = $row;
                if (count($rows) >= 10) {
                    break;
                }
            }
        }

        if (count($rows) < 10) {
            $rows = array_merge($rows, $this->fallbackKenyaBaseRows());
            $rows = array_slice($rows, 0, 10);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{label: string, kind: string}  $season
     */
    private function enrichKenya(array $row, int $seq, array $season): array
    {
        $uid = (string) ($row['AH'] ?? '01-ANI-FRO-001');
        $category = explode('-', $uid)[0] ?? '01';

        $sites = [
            ['AI' => 'Mutirithia NEO', 'AJ' => 'Segera', 'AK' => 'Laikipia', 'Z' => '0.3547,37.5822'],
            ['AI' => 'Isiolo Drylands NEO', 'AJ' => 'Central', 'AK' => 'Isiolo', 'Z' => '0.3540,37.5820'],
            ['AI' => 'Kitui Central NEO', 'AJ' => 'Kitui Town', 'AK' => 'Kitui', 'Z' => '-1.3667,38.0103'],
        ];
        $site = $sites[($seq - 1) % count($sites)];

        $forecast = $this->forecastWindow($seq, $season['kind']);
        $observer = 'NEO community observer group '.$seq;
        $calendarLabel = $season['kind'] === 'mam'
            ? 'Long rains (Masika) · March–May'
            : 'Short rains (Vuli) · October–December';

        $merged = array_merge($row, $site, $forecast, $this->actualOutcomeWindow($seq, $forecast), $this->sectionBFields($seq, $site, $observer, $season), [
            'A' => $category,
            'B' => $this->typeLabel($uid),
            'D' => (string) ($row['D'] ?? 'Observed behaviour linked to rainfall timing in workshop narrative '.$seq),
            'E' => (string) ($row['E'] ?? 'Mvua inakaribia wakati wa alama hii'),
            'F' => 'Dam margins, seasonal pools, and grazing routes around '.$site['AI'],
            'G' => (string) ($row['AO'] ?? 'Maa').' / Swahili',
            'H' => '1;4',
            'I' => '1;2;4;9',
            'W' => 'FGD notes: elders linked sign timing to '.$season['label'].' workshop narrative.',
            'X' => "Crops 3+\nWater 3+\nLivestock 2−",
            'AE' => 1,
            'BU' => ($seq % 4) + 1,
            'BA' => 'Kenya NEO pilot · '.$season['label'].' capture row '.$seq,
            'AO' => (string) ($row['AO'] ?? 'Maa'),
            'AL' => 'Kenya',
            'AM' => $season['label'],
            'AP' => $row['AP'] ?? 'Community/Plenary-endorsed',
        ]);

        return $this->mergeMissingCaptureFields($merged, $this->communityCaptureDefaults($seq, [
            'site' => $site['AI'],
            'uid' => $uid,
            'sign' => (string) ($merged['C'] ?? $uid),
            'lang' => (string) ($merged['AO'] ?? 'Maa'),
            'calendar_label' => $calendarLabel,
            'season' => $season,
        ]));
    }

    /**
     * @param  array{uid: string, name: string, site: string, ward: string, county: string, lang: string, gps: string, southern: bool}  $t
     * @param  array{label: string, kind: string}  $season
     */
    private function enrichGhana(array $t, int $seq, array $season): array
    {
        $uid = $t['uid'];
        $category = explode('-', $uid)[0] ?? '01';
        $tercile = ($seq % 3) + 1;
        $reliability = max(1, min(3, 2 + ($seq % 2)));

        $forecast = $this->forecastWindow($seq, $season['kind']);
        $siteRow = ['AI' => $t['site'], 'Z' => $t['gps']];
        $observer = $t['lang'].' forecast observers · '.$t['site'];

        $calendarLabel = match ($season['kind']) {
            'ghana_south_major' => 'Major rains · April–July (peak June)',
            'ghana_south_minor' => 'Minor rains · September–November',
            default => 'Single rainy season · northern Ghana',
        };

        $merged = array_merge($forecast, $this->actualOutcomeWindow($seq, $forecast), $this->sectionBFields($seq, $siteRow, $observer, $season), [
            'A' => $category,
            'B' => $this->typeLabel($uid),
            'C' => $t['name'],
            'D' => 'Community-described behaviour used in seasonal forecast discussions',
            'E' => 'Biɛɣu bɛ niŋ kpee ka saha bɛ ni bo saani',
            'F' => 'Savanna woodland, floodplains, and household farmlands near '.$t['site'],
            'G' => $t['lang'].' / English',
            'H' => '1;4',
            'I' => '1;2;4;9',
            'W' => 'Workshop transcript: sign discussed for '.$season['label'].'.',
            'X' => "Crops 3+\nWater 2+\nLivestock 2−",
            'L' => $reliability,
            'M' => $reliability,
            'N' => 1,
            'U' => $tercile,
            'V' => $reliability,
            'AC' => 1,
            'AD' => 1,
            'AE' => 1,
            'AH' => $uid,
            'AI' => $t['site'],
            'AJ' => $t['ward'],
            'AK' => $t['county'],
            'AL' => 'Ghana',
            'AM' => $season['label'],
            'AO' => $t['lang'],
            'AP' => $seq % 5 === 0 ? 'FGD-validated only' : 'Community/Plenary-endorsed',
            'BA' => 'Ghana pilot · '.$season['label'].' row '.$seq,
            'BU' => sprintf('%d; %d', ($seq % 3) + 1, 4 + ($seq % 3)),
        ]);

        return $this->mergeMissingCaptureFields($merged, $this->communityCaptureDefaults($seq, [
            'site' => $t['site'],
            'uid' => $uid,
            'sign' => $t['name'],
            'lang' => $t['lang'],
            'calendar_label' => $calendarLabel,
            'season' => $season,
        ]));
    }

    /**
     * @param  array{AI: string, Z: string}  $site
     * @param  array{label: string, kind: string}  $season
     * @return array<string, mixed>
     */
    private function sectionBFields(int $seq, array $site, string $observerGroup, array $season): array
    {
        $siteName = $site['AI'];
        $sighting = $this->sightingDateForSeason($seq, $season);

        return [
            'L' => max(1, min(3, ($seq % 3) + 1)),
            'CI' => $sighting['iso'],
            'O' => sprintf('%s, %s field verification', $sighting['display'], $siteName),
            'Z' => $site['Z'],
            'AA' => 'Acacia marker and seasonal pool edge ('.$siteName.')',
            'AB' => $observerGroup,
            'AR' => 'Ward-wide; riverine strips, grazing corridors, and hamlets near '.$siteName,
            'BB' => 1,
            'BC' => sprintf('3; 1; %s ITK forecast circle', $siteName),
        ];
    }

    /**
     * @return array{P: string, Q: string, R: string, S: string, T: int}
     */
    private function forecastWindow(int $seq, string $kind): array
    {
        $t = ($seq % 3) + 1;

        return match ($kind) {
            'mam' => [
                'P' => sprintf('2026-03-%02d', min(28, 8 + ($seq % 20))),
                'Q' => sprintf('2026-05-%02d', min(31, 12 + ($seq % 19))),
                'R' => sprintf('2026-05-%02d', min(28, 5 + ($seq % 23))),
                'S' => sprintf('2026-06-%02d', min(30, 8 + ($seq % 22))),
                'T' => $t,
            ],
            'ghana_south_major' => [
                'P' => sprintf('2026-04-%02d', min(28, 5 + ($seq % 23))),
                'Q' => sprintf('2026-07-%02d', min(31, 10 + ($seq % 21))),
                'R' => sprintf('2026-07-%02d', min(31, 15 + ($seq % 16))),
                'S' => sprintf('2026-08-%02d', min(31, 5 + ($seq % 26))),
                'T' => $t,
            ],
            'ghana_south_minor' => [
                'P' => sprintf('2026-09-%02d', min(30, 5 + ($seq % 25))),
                'Q' => sprintf('2026-11-%02d', min(30, 8 + ($seq % 22))),
                'R' => sprintf('2026-11-%02d', min(30, 12 + ($seq % 18))),
                'S' => sprintf('2026-12-%02d', min(31, 5 + ($seq % 26))),
                'T' => $t,
            ],
            'ghana_north' => [
                'P' => sprintf('2026-05-%02d', min(31, 5 + ($seq % 26))),
                'Q' => sprintf('2026-09-%02d', min(30, 10 + ($seq % 20))),
                'R' => sprintf('2026-09-%02d', min(30, 15 + ($seq % 15))),
                'S' => sprintf('2026-10-%02d', min(31, 8 + ($seq % 23))),
                'T' => $t,
            ],
            default => [
                'P' => sprintf('2026-10-%02d', min(30, 12 + ($seq % 18))),
                'Q' => sprintf('2026-12-%02d', min(31, 5 + ($seq % 26))),
                'R' => sprintf('2027-01-%02d', min(31, 5 + ($seq % 26))),
                'S' => sprintf('2027-01-%02d', min(31, 15 + ($seq % 16))),
                'T' => $t,
            ],
        };
    }

    /**
     * @param  array{P: string, Q: string, R: string, S: string}  $forecast
     * @return array{AS: string, AT: string, AU: int, AV: int, AZ: string}
     */
    private function actualOutcomeWindow(int $seq, array $forecast): array
    {
        $pTs = strtotime((string) $forecast['P']);
        $qTs = strtotime((string) $forecast['Q']);
        $rTs = strtotime((string) $forecast['R']);
        $sTs = strtotime((string) $forecast['S']);

        $onsetOffsetDays = 2 + ($seq % 6);
        $asTs = min($qTs, $pTs + ($onsetOffsetDays * 86400));

        $cessOffsetDays = 4 + ($seq % 5);
        $atTs = min($sTs, $rTs + ($cessOffsetDays * 86400));

        $outcomes = ['Hit', 'Hit', 'Miss', 'Hit', 'False Start', 'Hit', 'Miss', 'Hit', 'Hit', 'Miss'];

        return [
            'AS' => date('Y-m-d', $asTs),
            'AT' => date('Y-m-d', $atTs),
            'AU' => ($seq % 3) + 1,
            'AV' => (($seq + 1) % 3) + 1,
            'AZ' => $outcomes[($seq - 1) % count($outcomes)],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fallbackKenyaBaseRows(): array
    {
        $six = base_path('tests/Fixtures/Tilkit/six_sign_ond2026.json');
        if (! is_readable($six)) {
            return [];
        }
        $payload = json_decode(file_get_contents($six), true, 512, JSON_THROW_ON_ERROR);
        $out = [];
        foreach ($payload['rows'] ?? [] as $row) {
            $row['AI'] = 'Mutirithia NEO';
            $row['AJ'] = 'Segera NEO';
            $row['AK'] = 'Laikipia';
            $row['AL'] = 'Kenya';
            $out[] = $row;
        }

        return $out;
    }

    private function typeLabel(string $uid): string
    {
        $parts = explode('-', $uid);

        return match ($parts[1] ?? '') {
            'ANI' => 'Animals',
            'ATM' => 'Atmosphere',
            'PLT' => 'Plants',
            'AST' => 'Astronomy',
            'SOI' => 'Soils',
            'HYD' => 'Hydrology',
            'RIT' => 'Ritual',
            default => 'Indicator type',
        };
    }

    /**
     * @param  array{site: string, uid: string, sign: string, lang: string, calendar_label: string, season: array{label: string, kind: string, start_month?: int}}  $ctx
     * @return array<string, mixed>
     */
    private function communityCaptureDefaults(int $seq, array $ctx): array
    {
        $site = $ctx['site'];
        $sign = $ctx['sign'];
        $season = $ctx['season'];
        $reliability = max(1, min(3, 2 + ($seq % 2)));
        $tercile = ($seq % 3) + 1;
        $sighting = $this->sightingDateForSeason($seq, $season);
        $advisory = $this->advisoryDateForSeason($seq, $season);

        $summary = mb_strlen($sign) > 90 ? mb_substr($sign, 0, 87).'…' : $sign;

        return [
            'J' => 2 + ($seq % 2),
            'K' => ($seq % 4) === 0 ? 1 : 2,
            'L' => max(1, min(3, ($seq % 3) + 1)),
            'M' => $reliability,
            'N' => 1,
            'U' => $tercile,
            'V' => $reliability,
            'AC' => 1,
            'AD' => 1,
            'AE' => 1,
            'AF' => sprintf('Pilot data desk · %s', $sighting['display']),
            'AG' => 'Workshop minutes verified wording for '.$season['label'].'.',
            'AN' => $this->collectionDateForSeason($season),
            'AQ' => 'Elders and youth observers agreed the sign description at '.$site.' plenary.',
            'BD' => 'Kenya-Ghana pilot seed',
            'BE' => 'Workbook v4 community capture',
            'BF' => 'Ready for ward tally review',
            'BG' => ($seq % 5) + 1,
            'BH' => min(6, 3 + ($seq % 3)),
            'BI' => min(6, 4 + ($seq % 2)),
            'BJ' => $seq % 7,
            'BK' => 0,
            'BV' => 'Households and herders near '.$site.': water stress, crop timing, livestock movement',
            'BW' => '1; repair water pans, adjust planting dates, and brief school early-action club',
            'BX' => sprintf('CRAP-26-%02d', $seq),
            'BY' => sprintf('IOAP-26-%02d', $seq),
            'BZ' => sprintf('IAP-26-%02d', $seq),
            'CI' => $sighting['iso'],
            'CK' => $ctx['calendar_label'],
            'CL' => (int) ($season['start_month'] ?? substr($sighting['iso'], 5, 2)),
            'CN' => $seq % 3 === 0 ? 'Less often/ fewer' : 'About the same',
            'CO' => 'Water development',
            'CQ' => $tercile,
            'CR' => 40 + ($seq * 3),
            'CW' => '1;2;4',
            'CX' => 'Anticipatory (before hazard)',
            'CY' => 1,
            'CZ' => '1; community meeting and SMS via ward focal point',
            'DA' => 1,
            'DB' => $advisory['iso'],
            'DC' => '1;2;4',
            'DD' => 600 + ($seq * 25),
            'DE' => 300 + ($seq * 15),
            'DF' => 70 + ($seq % 20),
            'DG' => min(95, 50 + ($seq * 2)),
            'DJ' => $tercile,
            'DL' => 'Normal',
            'DM' => $seq % 4 === 0 ? 1 : 0,
            'DN' => 'Residual dry-spell risk if onset arrives late in lowland plots.',
            'DO' => 1,
            'DP' => '"'.$summary.'" — align planting, water, and grazing with '.$season['label'].'.',
        ];
    }

    /**
     * @param  array{kind: string, start_month?: int}  $season
     * @return array{iso: string, display: string}
     */
    private function sightingDateForSeason(int $seq, array $season): array
    {
        $day = min(28, 2 + ($seq % 26));

        return match ($season['kind']) {
            'mam' => $this->dateParts(2026, 3, $day),
            'ghana_south_major' => $this->dateParts(2026, 5, $day),
            'ghana_south_minor' => $this->dateParts(2026, 10, $day),
            'ghana_north' => $this->dateParts(2026, 7, $day),
            default => $this->dateParts(2026, 11, min(28, $day + 2)),
        };
    }

    /** @param  array{kind: string}  $season */
    private function advisoryDateForSeason(int $seq, array $season): array
    {
        $sighting = $this->sightingDateForSeason($seq, $season);
        $ts = strtotime($sighting['iso'].' +3 days');

        return $this->dateParts(
            (int) date('Y', $ts),
            (int) date('n', $ts),
            (int) date('j', $ts),
        );
    }

    /** @param  array{kind: string, start_month?: int}  $season */
    private function collectionDateForSeason(array $season): string
    {
        $month = (int) ($season['start_month'] ?? 3);
        $year = 2026;
        if ($month >= 10) {
            $year = 2025;
        }
        if ($season['kind'] === 'ghana_south_minor') {
            $year = 2026;
            $month = 8;
        }

        return sprintf('%04d-%02d-15', $year, max(1, $month - 1));
    }

    /** @return array{iso: string, display: string} */
    private function dateParts(int $year, int $month, int $day): array
    {
        $day = min($day, (int) date('t', mktime(0, 0, 0, $month, 1, $year)));
        $iso = sprintf('%04d-%02d-%02d', $year, $month, $day);

        return [
            'iso' => $iso,
            'display' => sprintf('%02d/%02d/%04d', $month, $day, $year),
        ];
    }

    /** @param  array<string, mixed>  $row  @param  array<string, mixed>  $defaults */
    private function mergeMissingCaptureFields(array $row, array $defaults): array
    {
        foreach ($defaults as $letter => $value) {
            if (! is_string($letter) || ! preg_match('/^[A-Z]{1,2}$/', $letter)) {
                continue;
            }
            $current = $row[$letter] ?? null;
            if ($current === null || $current === '') {
                $row[$letter] = $value;
            }
        }

        return $row;
    }
}
