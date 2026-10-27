<?php

namespace App\Models;

use Database\Factories\WebsiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Website extends Model
{
    /** @use HasFactory<WebsiteFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'websites';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'website_name',
        'domain',
        'description',
        'notes',
        'status',
        'provider',
        'template_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'integer',
        ];
    }

    /**
     * Get the customer that owns the website.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get all domains mapped to this website.
     *
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'website_id');
    }

    /**
     * Get the primary domain for this website.
     *
     * @return HasOne<Domain, $this>
     */
    public function primaryDomain(): HasOne
    {
        return $this->hasOne(Domain::class, 'website_id')->where('is_primary', true);
    }

    /**
     * Get all pages under this website.
     *
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'website_id');
    }

    /**
     * Get all media files uploaded for this website.
     *
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'website_id');
    }

    /**
     * Get all key-value settings for this website.
     *
     * @return HasMany<Setting, $this>
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class, 'website_id');
    }

    /**
     * Get all metadata records associated with this website.
     *
     * @return HasMany<Metadata, $this>
     */
    public function metadata(): HasMany
    {
        return $this->hasMany(Metadata::class, 'website_id');
    }

    /**
     * Get the theme configuration settings for this website.
     *
     * @return HasMany<WebsiteThemeSetting, $this>
     */
    public function themeSettings(): HasMany
    {
        return $this->hasMany(WebsiteThemeSetting::class, 'website_id');
    }

    /**
     * Get the themes configured for this website.
     *
     * @return BelongsToMany<Theme, $this>
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class, 'website_theme_settings', 'website_id', 'theme_id')
            ->withPivot(['setting_key', 'setting_value'])
            ->withTimestamps();
    }

    /**
     * Provide alias attribute 'name' for backwards compatibility.
     */
    public function getNameAttribute(): string
    {
        return (string) $this->website_name;
    }

    /**
     * Provide backwards-compatible accessor for template_name.
     */
    /**
     * Staging property for legacy template assignment.
     */
    protected ?string $pendingTemplateName = null;

    /**
     * Provide backwards-compatible accessor for template_name.
     */
    public function getTemplateNameAttribute(): string
    {
        return $this->themes->first()?->theme_name ?? 'Standard Theme';
    }

    /**
     * Backwards-compatible mutator for legacy template_name assignment.
     */
    public function setTemplateNameAttribute(mixed $value): void
    {
        $this->pendingTemplateName = $value ? (string) $value : null;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (self $website): void {
            if ($website->pendingTemplateName !== null) {
                $theme = Theme::firstOrCreate(
                    ['theme_name' => $website->pendingTemplateName],
                    [
                        'slug' => Str::slug($website->pendingTemplateName),
                        'status' => 1,
                    ]
                );

                WebsiteThemeSetting::updateOrCreate(
                    ['website_id' => $website->id, 'setting_key' => 'active_theme'],
                    [
                        'theme_id' => $theme->id,
                        'setting_value' => 'active',
                    ]
                );

                $website->pendingTemplateName = null;
                $website->unsetRelation('themes');
            }
        });
    }
}
