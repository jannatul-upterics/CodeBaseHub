<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'website_id',
        'tenant_page_id',
        'title',
        'slug',
        'page_type',
        'is_homepage',
        'is_template',
        'status',
        'content',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_image',
        'sections_data',
        'sync_hash',
        'parent_id',
        'template_id',
        'sort_order',
        'is_indexable',
        'master_page_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_homepage' => 'boolean',
            'is_template' => 'boolean',
            'status' => 'integer',
            'sections_data' => 'array',
            'sort_order' => 'integer',
            'is_indexable' => 'boolean',
            'tenant_page_id' => 'integer',
            'master_page_id' => 'integer',
        ];
    }

    /**
     * Get the website that owns this page.
     *
     * @return BelongsTo<Website, $this>
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id');
    }

    /**
     * Get the template blueprint this page belongs to.
     *
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    /**
     * Get the sections comprising this page.
     *
     * @return HasMany<PageSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class, 'page_id')->orderBy('sort_order');
    }

    /**
     * Get the metadata records for this page.
     *
     * @return HasMany<Metadata, $this>
     */
    public function metadata(): HasMany
    {
        return $this->hasMany(Metadata::class, 'page_id');
    }

    /**
     * Get the master blueprint page this page is derived from.
     *
     * @return BelongsTo<Page, $this>
     */
    public function masterPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'master_page_id');
    }

    /**
     * Get all pages derived from this master blueprint page.
     *
     * @return HasMany<Page, $this>
     */
    public function derivedPages(): HasMany
    {
        return $this->hasMany(Page::class, 'master_page_id');
    }
}
