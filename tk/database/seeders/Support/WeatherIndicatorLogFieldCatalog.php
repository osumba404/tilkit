<?php

namespace Database\Seeders\Support;

/**
 * Field definitions aligned with the TILKIT Weather Indicator Log template
 * (FGD, plenary, and community indicator monitoring).
 */
class WeatherIndicatorLogFieldCatalog
{
    /** @return list<array<string, mixed>> */
    public static function workshopSessionFields(): array
    {
        return [
            self::note('section_workshop', 'Workshop & collection context', 'Record once per FGD, plenary, or field monitoring session before logging individual indicators.'),
            self::text('community_site_name', 'Community / site name', 'e.g. Saru, Kluajon, Kalba, Konfusi', true),
            self::text('season_year', 'Season / year', 'e.g. 2026 Long Rains; MAM 2027', true),
            self::select('session_format', 'Session format', [
                ['label' => 'Focus group discussion (FGD)', 'value' => 'fgd'],
                ['label' => 'Community plenary', 'value' => 'plenary'],
                ['label' => 'Field indicator monitoring', 'value' => 'field_monitoring'],
                ['label' => 'Individual interview', 'value' => 'interview'],
                ['label' => 'Mixed session', 'value' => 'mixed'],
            ], true),
            self::date('data_collection_date', 'Date of data collection', 'Date of the FGD / plenary / interview — not necessarily the date the indicator was sighted in the field.', true),
            self::text('primary_language', 'Primary language of the session', 'Language in which local names and descriptions were originally given.', true),
            self::text('secondary_language', 'Secondary language (if any)', null, false),
            self::text('lead_facilitator', 'Lead facilitator', null, true),
            self::text('co_facilitator', 'Co-facilitator / note-taker', null, false),
            self::integer('participants_total', 'Total participants', null, false),
            self::select('participants_gender_mix', 'Overall gender composition of participants', self::genderCompositionChoices()),
            self::select('participants_age_mix', 'Overall age / generation composition', self::ageCompositionChoices()),
            self::text('session_location_landmark', 'Session location or landmark', null, false),
            self::geopoint('session_gps', 'GPS of session location', null, false),
            self::note('barriers_context', 'Climate information barriers', 'This session supports salience, access, legitimacy, equity, and integration when facilitation reaches diverse voices and links ITK to institutional forecasts.'),
            self::text('integrated_forecast_session_id', 'Integrated consensus forecast reference (session)', 'Forecast ID / date from the knowledge integration session, if already assigned.', false),
            self::text('session_notes', 'Session notes', 'Facilitation notes, contested topics, or follow-up actions.', false),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function indicatorLogFields(): array
    {
        $fields = [
            self::note('section_classification', 'A. Indicator classification (A1–C2)', 'Nature, type, and grouping of the indicator.'),
            self::select('indicator_category', 'Nature of indicator (indicator category)', self::indicatorCategoryChoices(), true),
            self::select('indicator_type', 'Indicator type', self::indicatorTypeChoices(), true),
            self::text('indicator_grouping', 'Indicator grouping', 'Finer grouping within the type (e.g. plant part, animal class, celestial body).', false),
            self::text('indicator_exhibit', 'What exactly is the indicator?', 'How the indicator exhibits itself in the community / locality / area.', true),
            self::text('forecast_use', 'Indicator interpretation / use in forecasting', 'Use for predicting rain onset, cessation, intensity, distribution, drought, and flood events.', true),

            self::note('section_identity', 'B. Names & local meaning (D4–H8)', null),
            self::text('local_name', 'Local name of the indicator', null, true),
            self::text('english_name', 'English name of the indicator (if known)', null, false),
            self::text('scientific_name', 'Scientific name of the indicator (if known)', null, false),
            self::text('local_meaning_weather', 'Local meaning for weather prediction', 'Whether there will be rain or drought, or rains more, less, or normal, based on historical average in this locality.', true),

            self::note('section_reliability', 'C. Reliability & sighting (J9–N13)', null),
            self::select('historical_or_emerging', 'Historical or emerging indicator', [
                ['label' => '2 — Historical (old) indicator', 'value' => '2'],
                ['label' => '1 — New (emerging) indicator', 'value' => '1'],
            ], true),
            self::text('prediction_lead_time', 'Prediction lead time after witnessing', 'How long (days, weeks, or months) until the predicted event.', true),
            self::select('historical_reliability_rating', 'Historical reliability rating (past)', self::reliabilityChoices(), true),
            self::select('current_reliability_rating', 'Current reliability rating (present)', self::reliabilityChoices(), true),
            self::select('sighted_this_season', 'Sighted / observed this season?', [
                ['label' => '1 — Yes (prediction can be made)', 'value' => '1'],
                ['label' => '0 — No (prediction not yet possible)', 'value' => '0'],
            ], true),
            self::text('sighting_place_and_date', 'Where and when sighted', 'Locality and date if sighted this season.', false),

            self::note('section_forecast', 'D. Forecast from this indicator (P14–Q15)', 'Based on information available from this indicator in this locality.'),
            self::text('predicted_rain_onset_window', 'Likely rain onset window', 'Dates or range (e.g. between date x and date y) when rains may start.', false),
            self::select('season_tercile_prediction', 'Season rainfall tercile prediction', self::tercileChoices(), true),
            self::text('recommended_actions', 'Recommended plans, actions, or strategies', 'Advice to adopt based on this weather prediction.', true),

            self::note('section_evidence', 'E. Photos, location & landmarks (R16–S17)', 'Attach visual evidence and precise location.'),
            self::image('photo_1', 'Photo 1', 'First of at least three photos of the indicator as observed.', true),
            self::image('photo_2', 'Photo 2', 'Second photo (different angle or context).', true),
            self::image('photo_3', 'Photo 3', 'Third photo.', true),
            self::image('photo_additional', 'Additional photo (optional)', 'Extra plant parts, animal behaviour, sky condition, etc.', false),
            self::geopoint('indicator_gps', 'GPS location of the indicator', null, true),
            self::text('landmark_near_gps', 'Landmark feature near GPS location', null, true),

            self::note('section_safeguards', 'F. Control, safeguards & validation chain (T18–W21)', 'Observed → confirmed → approved → verified.'),
            self::text('observed_by', 'Observed by (community member)', 'Name of observant community member.', true),
            self::text('confirmed_by', 'Confirmed by (ITK holder / FGD / plenary)', 'Has the observation been confirmed? Yes/No; by whom?', true),
            self::text('approved_by', 'Approved by (ITK holder)', 'Has the observation been approved? Yes/No; by whom?', true),
            self::text('verified_by_expert', 'Verified by (technical expert)', 'Verification of hits, misses, and false starts; by whom?', false),
            self::text('calibration_notes', 'Calibration analysis', 'Reliability, hits (2), false starts (1), misses (0), and confidence adjustments.', false),

            self::note('section_identification', 'G. Indicator & record identification (X22–AB)', 'Multi-season, multi-community tracking.'),
            self::text('unique_indicator_id', 'Unique indicator ID', 'Same physical indicator across seasons/sites: Category-Type-Subtype + sequence (e.g. 03-PLT-TRE-001). Do not embed season/year in this code.', true),
            self::select('indicator_code_prefix', 'Indicator code prefix (legend)', self::codePrefixChoices(), true),
            self::text('indicator_sequence', 'Sequence number', 'Three-digit sequence for this prefix at this site (e.g. 001).', true),
            self::text('record_community_site', 'Community / site for this record', 'May match workshop session or a distinct micro-site.', true),
            self::text('record_season_year', 'Season / year for this record', null, true),
            self::date('record_collection_date', 'Date this record was collected', 'FGD / plenary / interview date for this row.', true),
            self::text('record_source_language', 'Language of local name and description', null, true),

            self::note('section_validation_status', 'H. Validation & endorsement (AC–AE)', 'FGD and plenary validation status.'),
            self::select('validation_status', 'Validation status', [
                ['label' => '1 — FGD-validated only', 'value' => '1'],
                ['label' => '2 — Community / plenary-endorsed', 'value' => '2'],
                ['label' => '3 — Sub-community-specific', 'value' => '3'],
                ['label' => '4 — Contested', 'value' => '4'],
                ['label' => '5 — Provisional / emerging', 'value' => '5'],
            ], true),
            self::text('validation_groups_detail', 'Groups recognizing or disputing (if sub-community or contested)', null, false),
            self::text('geographic_scope', 'Geographic scope of applicability', 'Whole locality or specific micro-zone; specify if limited.', true),

            self::note('section_outcome', 'I. Actual outcome & verification (AF–AI)', 'Complete after the season for calibration.'),
            self::date('actual_rain_onset_date', 'Actual observed rain onset date', null, false),
            self::select('actual_season_classification', 'Actual season classification (once known)', [
                ['label' => '3 — More / above normal', 'value' => '3'],
                ['label' => '2 — Normal / long-term average', 'value' => '2'],
                ['label' => '1 — Less / below normal', 'value' => '1'],
            ], false),
            self::select('prediction_outcome', 'Hit, miss, or false start', [
                ['label' => '1 — Hit', 'value' => '1'],
                ['label' => '2 — Miss', 'value' => '2'],
                ['label' => '3 — False start', 'value' => '3'],
            ], false),
            self::text('outcome_reliability_notes', 'Notes on hits, misses, and composite indicators', 'Other indicators assessed together as a composite.', false),

            self::note('section_governance', 'J. Safety, governance & evidence (AJ–AN)', null),
            self::select('do_no_harm_flag', 'Do-no-harm / safety flag', [
                ['label' => 'No — no conflict with official warnings', 'value' => 'no'],
                ['label' => 'Yes — review with technical expert before advisory use', 'value' => 'yes'],
            ], true),
            self::text('do_no_harm_detail', 'Do-no-harm detail (if yes)', null, false),
            self::text('consent_data_sharing', 'Consent / data-sharing preference', 'ITK holder agreement on sharing, citation, and attribution beyond this project.', true),
            self::file('audiovisual_reference', 'Audio / video recording reference', 'File or link capturing the ITK holder description in their own words.', false),
            self::text('duplicate_indicator_link', 'Cross-reference / duplicate indicator ID', 'If merged with or duplicating another logged indicator.', false),
            self::text('integrated_consensus_forecast_ref', 'Integrated consensus forecast reference', 'Forecast ID / date this indicator rating fed into.', false),

            self::note('section_observers', 'K. Observer demographic profile (AO–AS)', 'Equity monitoring across the dataset.'),
            self::integer('observer_count', 'Number of observers / contributors', null, true),
            self::select('observer_gender', 'Gender composition of observer(s)', self::genderCompositionChoices(), true),
            self::select('observer_age', 'Age / generation composition of observer(s)', self::ageCompositionChoices(), true),
            self::select('observer_livelihood', 'Livelihood / occupation of observer(s)', self::livelihoodChoices(), true),
            self::text('observer_livelihood_other', 'Livelihood — other (specify)', null, false),
            self::select('observer_disability_included', 'Observer group includes person(s) with disability?', [
                ['label' => '1 — Yes', 'value' => '1'],
                ['label' => '2 — No', 'value' => '2'],
                ['label' => '3 — Not recorded / prefer not to say', 'value' => '3'],
            ], true),

            self::note('section_calibration_metrics', 'L. Calibration metrics (manual entry until history accumulates)', 'Platform analytics can aggregate by Unique Indicator ID over seasons; enter cumulative figures when migrating from spreadsheets.'),
            self::integer('cumulative_corroboration_count', 'Cumulative corroboration count', 'Seasons independently confirmed and approved.', false),
            self::integer('cumulative_scored_seasons', 'Cumulative scored seasons', 'Seasons with Hit, Miss, or False Start recorded.', false),
            self::integer('cumulative_hit_count', 'Cumulative hit count', null, false),
            self::integer('cumulative_miss_count', 'Cumulative miss count', null, false),
            self::integer('cumulative_false_start_count', 'Cumulative false start count', null, false),
            self::decimal('hit_rate_cumulative', 'Hit rate (cumulative)', 'Hit count ÷ scored seasons.', false),
            self::decimal('miss_rate_cumulative', 'Miss rate (cumulative)', null, false),
            self::decimal('false_start_rate_cumulative', 'False-start rate (cumulative)', null, false),
            self::select('weighting_phase', 'Weighting phase', [
                ['label' => 'Rating-based (fewer than 3 scored seasons)', 'value' => 'rating'],
                ['label' => 'Hit-rate-based (3 or more scored seasons)', 'value' => 'hit_rate'],
            ], false),
            self::decimal('final_weight', 'Final weight (protocol)', 'Combines reliability / hit-rate and corroboration.', false),
            self::select('stability_flag', 'Stability flag (≥2-point swing J vs K)', [
                ['label' => 'No significant swing', 'value' => '0'],
                ['label' => 'Swing flagged for facilitator discussion', 'value' => '1'],
            ], false),

            self::note('section_hazard_actions', 'M. Hazard, risk, impact, opportunity & linked actions', 'Links to community and integrated action-planning documents.'),
            self::select('hazard_type', 'Hazard type implied by prediction', [
                ['label' => '1 — Drought / dry spell', 'value' => '1'],
                ['label' => '2 — Excess rain / flood', 'value' => '2'],
                ['label' => '3 — Windstorm', 'value' => '3'],
                ['label' => '4 — Erratic / unreliable rainfall distribution', 'value' => '4'],
                ['label' => '5 — Heat stress', 'value' => '5'],
                ['label' => '6 — Pest / disease outbreak risk', 'value' => '6'],
                ['label' => '7 — Other (specify)', 'value' => '7'],
                ['label' => '8 — None / not applicable', 'value' => '8'],
            ], false),
            self::text('hazard_type_other', 'Hazard type — other (specify)', null, false),
            self::text('risk_impact_narrative', 'Risk / impact narrative', 'Who or what is exposed; anticipated impact on crops, livestock, water, mobility, safety.', false),
            self::select('opportunity_flag', 'Opportunity flag', [
                ['label' => 'No', 'value' => 'no'],
                ['label' => 'Yes', 'value' => 'yes'],
            ], false),
            self::text('opportunity_description', 'Opportunity description (if yes)', null, false),
            self::text('community_action_plan_ref', 'Community representatives action reference', 'Action ID / date (external action plan document).', false),
            self::text('intermediary_action_plan_ref', 'Intermediary officer action reference', null, false),
            self::text('integrated_action_plan_ref', 'Integrated action plan reference', null, false),

            self::note('section_tercile_tally', 'N. Seasonal tercile tally (eligibility reminder)', 'For bottom-up forecast integration: include indicators that are sighted (L=Yes), confirmed (T), approved (U), validation not Contested (AC≠4), and tercile ≠ 0. Weighted tally uses Final Weight alongside simple counts.'),
            self::ack('data_quality_acknowledgement', 'I confirm this entry follows the TILKIT indicator weighting and validation protocol.', true),
            self::metadata('submission_metadata', 'Record metadata', false),
        ];

        return $fields;
    }

    /** @return list<array<string, mixed>> */
    public static function tercileSummaryFields(): array
    {
        return [
            self::note('tally_intro', 'Seasonal tercile tally — live bottom-up forecast', 'Tally qualifying indicators for the community/site and season/year above. Show simple (unweighted) and weighted (Final Weight) percentages side by side per the Indicator Weighting Protocol.'),
            self::text('tally_community_site', 'Community / site', null, true),
            self::text('tally_season_year', 'Season / year', null, true),
            self::integer('tally_above_normal_count', 'Above normal — simple count', null, false),
            self::decimal('tally_above_normal_pct', 'Above normal — simple %', null, false),
            self::decimal('tally_above_normal_weight_sum', 'Above normal — sum of final weights', null, false),
            self::decimal('tally_above_normal_weight_pct', 'Above normal — weighted %', null, false),
            self::integer('tally_normal_count', 'Normal — simple count', null, false),
            self::decimal('tally_normal_pct', 'Normal — simple %', null, false),
            self::decimal('tally_normal_weight_sum', 'Normal — sum of final weights', null, false),
            self::decimal('tally_normal_weight_pct', 'Normal — weighted %', null, false),
            self::integer('tally_below_normal_count', 'Below normal — simple count', null, false),
            self::decimal('tally_below_normal_pct', 'Below normal — simple %', null, false),
            self::decimal('tally_below_normal_weight_sum', 'Below normal — sum of final weights', null, false),
            self::decimal('tally_below_normal_weight_pct', 'Below normal — weighted %', null, false),
            self::text('tally_composite_notes', 'Composite indicator adjustments', 'If indicators were grouped as one composite vote, document manual weight adjustments here (Protocol Step 5).', false),
        ];
    }

    /** @param list<array{label: string, value: string}> $choices */
    private static function select(string $name, string $label, array $choices, bool $required = false, ?string $hint = null): array
    {
        return [
            'name' => $name,
            'field_type' => 'select_one',
            'label' => $label,
            'hint' => $hint,
            'is_required' => $required,
            'parameters' => ['choices' => $choices],
        ];
    }

    private static function text(string $name, string $label, ?string $hint = null, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'text',
            'label' => $label,
            'hint' => $hint,
            'is_required' => $required,
            'parameters' => [],
        ];
    }

    private static function integer(string $name, string $label, ?string $hint = null, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'integer',
            'label' => $label,
            'hint' => $hint,
            'is_required' => $required,
            'parameters' => ['min' => 0],
        ];
    }

    private static function decimal(string $name, string $label, ?string $hint = null, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'decimal',
            'label' => $label,
            'hint' => $hint,
            'is_required' => $required,
            'parameters' => ['min' => 0, 'max' => 1],
        ];
    }

    private static function date(string $name, string $label, ?string $hint = null, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'date',
            'label' => $label,
            'hint' => $hint,
            'is_required' => $required,
            'parameters' => [],
        ];
    }

    private static function image(string $name, string $label, ?string $hint = null, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'image',
            'label' => $label,
            'hint' => $hint,
            'is_required' => $required,
            'parameters' => ['allow_capture' => true, 'allow_upload' => true, 'max_size_mb' => 25],
        ];
    }

    private static function file(string $name, string $label, ?string $hint = null, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'file',
            'label' => $label,
            'hint' => $hint,
            'is_required' => $required,
            'parameters' => ['allow_capture' => false, 'allow_upload' => true, 'max_size_mb' => 100],
        ];
    }

    private static function geopoint(string $name, string $label, ?string $hint = null, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'geopoint',
            'label' => $label,
            'hint' => $hint,
            'is_required' => $required,
            'parameters' => [],
        ];
    }

    private static function note(string $name, string $label, ?string $hint = null): array
    {
        return [
            'name' => $name,
            'field_type' => 'note',
            'label' => $label,
            'hint' => $hint,
            'is_required' => false,
            'parameters' => [],
        ];
    }

    private static function ack(string $name, string $label, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'acknowledge',
            'label' => $label,
            'hint' => null,
            'is_required' => $required,
            'parameters' => [],
        ];
    }

    private static function metadata(string $name, string $label, bool $required = false): array
    {
        return [
            'name' => $name,
            'field_type' => 'metadata',
            'label' => $label,
            'hint' => null,
            'is_required' => $required,
            'parameters' => [],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private static function reliabilityChoices(): array
    {
        return [
            ['label' => '3 — High', 'value' => '3'],
            ['label' => '2 — Medium', 'value' => '2'],
            ['label' => '1 — Low', 'value' => '1'],
            ['label' => '0 — Not sure', 'value' => '0'],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private static function tercileChoices(): array
    {
        return [
            ['label' => '3 — More / above normal', 'value' => '3'],
            ['label' => '2 — Normal / long-term average', 'value' => '2'],
            ['label' => '1 — Less / below normal', 'value' => '1'],
            ['label' => '0 — No prediction yet', 'value' => '0'],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private static function genderCompositionChoices(): array
    {
        return [
            ['label' => '1 — All male', 'value' => '1'],
            ['label' => '2 — All female', 'value' => '2'],
            ['label' => '3 — Mixed (male & female)', 'value' => '3'],
            ['label' => '4 — Not recorded / prefer not to say', 'value' => '4'],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private static function ageCompositionChoices(): array
    {
        return [
            ['label' => '1 — Youth (approx. under 35)', 'value' => '1'],
            ['label' => '2 — Adult', 'value' => '2'],
            ['label' => '3 — Elder', 'value' => '3'],
            ['label' => '4 — Mixed', 'value' => '4'],
            ['label' => '5 — Not recorded / prefer not to say', 'value' => '5'],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private static function livelihoodChoices(): array
    {
        return [
            ['label' => '1 — Farmer', 'value' => '1'],
            ['label' => '2 — Pastoralist', 'value' => '2'],
            ['label' => '3 — Trader', 'value' => '3'],
            ['label' => '4 — Fisherfolk', 'value' => '4'],
            ['label' => '5 — Other (specify)', 'value' => '5'],
            ['label' => '6 — Mixed', 'value' => '6'],
            ['label' => '7 — Not recorded / prefer not to say', 'value' => '7'],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private static function indicatorCategoryChoices(): array
    {
        return [
            ['label' => '01 — Astronomical / astrological / celestial', 'value' => '01'],
            ['label' => '02 — Atmospheric / physical / meteorological', 'value' => '02'],
            ['label' => '02 — Hydrological / water-related', 'value' => '02-HYD'],
            ['label' => '03 — Biological / ecological', 'value' => '03'],
            ['label' => '04 — Traditional rituals / examination of hidden indicators', 'value' => '04'],
            ['label' => '05 — Sensory / human body physiological (meteorosensitivity)', 'value' => '05'],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private static function indicatorTypeChoices(): array
    {
        return [
            ['label' => 'Sun, moon, stars, planets, sky', 'value' => 'ast_celestial'],
            ['label' => 'Wind, cloud, air, rain, temperature, humidity, thunder, lightning, rainbow, mist/fog, dew, soil/dust', 'value' => 'atm_physical'],
            ['label' => 'Water bodies (hydrological): waves, foam, etc.', 'value' => 'hyd_water'],
            ['label' => 'Plants / crops (tree, shrub, grass, herb, vine, other)', 'value' => 'bio_plants'],
            ['label' => 'Animals (insects, birds, mammals, reptiles, amphibians, fish, spiders, molluscs, other)', 'value' => 'bio_animals'],
            ['label' => 'Traditional examination (e.g. he-goat intestines, liver, spleen)', 'value' => 'ritual_examination'],
            ['label' => 'Human body sensitivity (temperature, humidity, smell, etc.)', 'value' => 'sen_body'],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private static function codePrefixChoices(): array
    {
        $prefixes = [
            '01-AST', '01-AST-SUN', '01-AST-MON', '01-AST-STR', '01-AST-PLN', '01-AST-SKY', '01-AST-OTH',
            '02-ATM', '02-ATM-WND', '02-ATM-CLD', '02-ATM-AIR', '02-ATM-RAN', '02-ATM-TMP', '02-ATM-HUM',
            '02-ATM-THU', '02-ATM-LTG', '02-ATM-RBW', '02-ATM-MST', '02-ATM-DEW', '02-ATM-SOL', '02-ATM-OTH',
            '02-HYD', '02-HYD-WAV', '02-HYD-FOM', '02-HYD-OTH',
            '03-PLT-TRE', '03-PLT-SHR', '03-PLT-GRS', '03-PLT-HRB', '03-PLT-VIN', '03-PLT-OTH',
            '03-ANI-INS', '03-ANI-BRD', '03-ANI-MAM', '03-ANI-REP', '03-ANI-AMP', '03-ANI-FSH', '03-ANI-SPD', '03-ANI-MOL', '03-ANI-OTH',
            '04-RIT', '04-RIT-INT', '04-RIT-LIV', '04-RIT-SPL', '04-RIT-OTH',
            '05-SEN', '05-SEN-TMP', '05-SEN-HUM', '05-SEN-SML', '05-SEN-OTH',
        ];

        return array_map(
            fn (string $code) => ['label' => $code, 'value' => $code],
            $prefixes,
        );
    }
}
