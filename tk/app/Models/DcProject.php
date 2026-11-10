<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DcProject extends Model
{
    use SoftDeletes;

    protected $table = 'dc_projects';

    protected $fillable = [
        'code',
        'name',
        'module',
        'purpose',
        'lawful_basis',
        'data_controller_name',
        'data_controller_email',
        'data_protection_contact_email',
        'odpc_registration_number',
        'privacy_policy_url',
        'personal_data_notice',
        'retention_months',
        'retention_policy_summary',
        'cross_border_transfers_allowed',
        'cross_border_safeguards',
        'starts_on',
        'ends_on',
        'status',
        'public_collection_enabled',
        'consent_text',
        'consent_ack_label',
        'consent_require_signature',
        'brand_logo_path',
        'brand_footer',
        'default_locale',
        'owner_user_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'public_collection_enabled' => 'boolean',
            'consent_require_signature' => 'boolean',
            'cross_border_transfers_allowed' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function publicCollectUrl(): string
    {
        return route('public.tools.collect', $this);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ToolMember::class, 'project_id');
    }

    public function forms(): HasMany
    {
        return $this->hasMany(DcForm::class, 'project_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(DcSubmission::class, 'project_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ToolInvitation::class, 'project_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ToolAuditLog::class, 'project_id');
    }

    public function scheduledExports(): HasMany
    {
        return $this->hasMany(ToolScheduledExport::class, 'project_id');
    }

    public function tilkitPrograms(): HasMany
    {
        return $this->hasMany(\App\Models\Tilkit\TilkitProgram::class, 'project_id');
    }

    public function tilkitSites(): HasMany
    {
        return $this->hasMany(\App\Models\Tilkit\TilkitSite::class, 'project_id');
    }

    public function tilkitSeasons(): HasMany
    {
        return $this->hasMany(\App\Models\Tilkit\TilkitSeason::class, 'project_id');
    }

    public function tilkitIndicators(): HasMany
    {
        return $this->hasMany(\App\Models\Tilkit\TilkitIndicator::class, 'project_id');
    }

    public function requiresConsentGate(): bool
    {
        return filled(trim((string) $this->consent_text));
    }

    public function dataSubjectRequests(): HasMany
    {
        return $this->hasMany(DataSubjectRequest::class, 'project_id');
    }

    public function lawfulBasisLabel(): string
    {
        $bases = config('kenya_dpa.lawful_bases', []);

        return $bases[$this->lawful_basis] ?? str_replace('_', ' ', (string) $this->lawful_basis);
    }

    public function publicPrivacyUrl(): string
    {
        return route('public.tools.privacy', $this);
    }

    public function dataProtectionAdminUrl(): string
    {
        if ($this->module === 'tilkit_weather') {
            return route('tools.tilkit.data-protection', $this);
        }

        return route('tools.data-protection', $this);
    }
}
