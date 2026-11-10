<?php

namespace App\Services\KenyaDpa;

use App\Models\DcProject;

class PrivacyNoticeBuilder
{
    public function render(DcProject $tool): string
    {
        if (filled($tool->personal_data_notice)) {
            return (string) $tool->personal_data_notice;
        }

        $bases = config('kenya_dpa.lawful_bases', []);
        $basisLabel = $bases[$tool->lawful_basis] ?? str_replace('_', ' ', (string) $tool->lawful_basis);

        $controller = $tool->data_controller_name ?: $tool->name;
        $email = $tool->data_protection_contact_email
            ?: $tool->data_controller_email
            ?: $tool->owner?->email
            ?: 'privacy contact not configured';

        $rightsContact = $tool->data_protection_contact_email ?: $email;
        $retention = $tool->retention_months ?? config('kenya_dpa.default_retention_months', 84);

        $crossBorder = $tool->cross_border_transfers_allowed
            ? 'Cross-border transfers are permitted only where documented safeguards apply: '.($tool->cross_border_safeguards ?: 'see controller.')
            : 'Personal data is not transferred outside Kenya unless adequate safeguards and consent are documented.';

        $template = config('kenya_dpa.default_privacy_notice', '');

        return str_replace(
            ['{{controller_name}}', '{{controller_email}}', '{{purpose}}', '{{lawful_basis_label}}', '{{retention_months}}', '{{rights_contact}}', '{{request_url}}', '{{cross_border_statement}}'],
            [
                $controller,
                $email,
                $tool->purpose,
                $basisLabel,
                (string) $retention,
                $rightsContact,
                route('public.tools.privacy', $tool),
                $crossBorder,
            ],
            $template
        );
    }
}
