<?php

/**
 * Kenya Data Protection Act, 2019 (Cap. 25C) - platform mapping for TILKIT.
 * Section 25 principles, Section 26 rights, Section 30 lawful bases.
 */

return [
    /*
    | Lawful basis codes stored on dc_projects.lawful_basis (Section 30).
    */
    'lawful_bases' => [
        'consent' => 'Consent of the data subject (s.30(1)(a))',
        'contract' => 'Performance of a contract (s.30(1)(b)(i))',
        'legal_obligation' => 'Compliance with a legal obligation (s.30(1)(b)(ii))',
        'vital_interests' => 'Protection of vital interests (s.30(1)(b)(iii))',
        'public_interest' => 'Public interest or official authority (s.30(1)(b)(iv)-(vi))',
        'legitimate_interest' => 'Legitimate interests (s.30(1)(b)(vii))',
        'research' => 'Historical, statistical or scientific research (s.30(1)(b)(viii))',
    ],

    'default_retention_months' => (int) env('KENYA_DPA_DEFAULT_RETENTION_MONTHS', 84),

    /*
    | Personal data inventory for the v4 weather workbook (purpose limitation & transparency).
    | category: identifier | location | image | demographic | opinion | governance
    */
    'tilkit_personal_data_columns' => [
        'AB' => ['field' => 'observed_by', 'category' => 'identifier', 'description' => 'Observer or group name'],
        'O' => ['field' => 'place_text', 'category' => 'location', 'description' => 'Place description'],
        'Z' => ['field' => 'gps', 'category' => 'location', 'description' => 'GPS coordinates'],
        'AA' => ['field' => 'landmark', 'category' => 'location', 'description' => 'Landmark'],
        'Y' => ['field' => 'evidence_media', 'category' => 'image', 'description' => 'Photos of signs or surroundings'],
        'BC' => ['field' => 'consent', 'category' => 'governance', 'description' => 'Consent level, share flag, attribution credit'],
        'BB' => ['field' => 'do_no_harm', 'category' => 'governance', 'description' => 'Do-no-harm limit on sharing'],
        'BG' => ['field' => 'observers_count', 'category' => 'demographic', 'description' => 'Workshop observer count'],
        'BH' => ['field' => 'gender_mix', 'category' => 'demographic', 'description' => 'Gender mix'],
        'BI' => ['field' => 'age_generation', 'category' => 'demographic', 'description' => 'Age / generation mix'],
        'BJ' => ['field' => 'livelihood_mix', 'category' => 'demographic', 'description' => 'Livelihood mix'],
        'BK' => ['field' => 'disability_inclusion', 'category' => 'demographic', 'description' => 'Disability inclusion notes'],
    ],

    'account_personal_data' => [
        'users.name' => 'Account display name',
        'users.email' => 'Account email (authentication and invitations)',
    ],

    'default_privacy_notice' => <<<'TEXT'
This processing is governed by the Kenya Data Protection Act, 2019.

**Data controller:** {{controller_name}}
**Contact:** {{controller_email}}
**Purpose:** {{purpose}}
**Lawful basis:** {{lawful_basis_label}}
**Retention:** Personal identifiers in indicator rows are kept for up to {{retention_months}} months after the collection season unless a shorter period is agreed at community level. Aggregated forecasts may be retained for learning across seasons when consent allows.

**Categories of personal data:** Observer names, place descriptions, GPS pins, photographs, workshop demographic notes, and consent records captured in the v4 workbook (columns AB, O, Z, AA, Y, BC, BB, BG–BK).

**Your rights:** You may request access, correction, erasure, or object to processing. Contact {{rights_contact}} or submit a request at {{request_url}}.

**Cross-border transfers:** {{cross_border_statement}}

Community consent (column BC) and do-no-harm (BB) are recorded on each row. Rows below consent level 2 are not included in external exports.
TEXT,
];
