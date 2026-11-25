<?php

namespace App\Models;

use Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Contracts\Domain as DomainContract;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\ConvertsDomainsToLowercase;

class Domain extends Model implements DomainContract
{
    /** @use HasFactory<DomainFactory> */
    use CentralConnection, ConvertsDomainsToLowercase, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'domains';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'domain',
        'verified_at',
        'is_primary',
        'ssl_enabled',
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
            'verified_at' => 'datetime',
            'is_primary' => 'boolean',
            'ssl_enabled' => 'boolean',
            'status' => 'integer',
        ];
    }

    /**
     * Get the website that owns the domain.
     *
     * @return BelongsTo<Website, $this>
     */
    /**
     * New tenancy relationship – the website acting as the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'tenant_id');
    }

    /**
     * Get the website that owns the domain.
     *
     * @return BelongsTo<Website, $this>
     */
    public function website(): BelongsTo
    {
        return $this->tenant();
    }
}
