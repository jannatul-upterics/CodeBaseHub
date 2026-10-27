<?php

namespace App\Models;

use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'themes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'theme_name',
        'slug',
        'description',
        'preview_image',
        'version',
        'status',
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
     * Get the settings associated with this theme across websites.
     *
     * @return HasMany<WebsiteThemeSetting, $this>
     */
    public function websiteThemeSettings(): HasMany
    {
        return $this->hasMany(WebsiteThemeSetting::class, 'theme_id');
    }

    /**
     * Get the websites utilizing this theme.
     *
     * @return BelongsToMany<Website, $this>
     */
    public function websites(): BelongsToMany
    {
        return $this->belongsToMany(Website::class, 'website_theme_settings', 'theme_id', 'website_id')
            ->withPivot(['setting_key', 'setting_value'])
            ->withTimestamps();
    }
}
