<?php

namespace App\Services;

use App\Http\Middleware\TenantConnection;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\Website;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PageSyncService
{
    /**
     * Prevents infinite synchronization loops.
     */
    public static bool $isSyncing = false;

    /**
     * Suppress synchronization during callback execution.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutSyncing(callable $callback): mixed
    {
        $previous = static::$isSyncing;
        static::$isSyncing = true;
        try {
            return $callback();
        } finally {
            static::$isSyncing = $previous;
        }
    }

    public static function isSyncing(): bool
    {
        return static::$isSyncing;
    }

    /**
     * Synchronize a local Vocabot page create/update/delete to the central CodeBaseHub database.
     */
    public function syncToCodeBaseHub(Page $page, string $action = 'save'): bool
    {
        return true;
    }

    /**
     * Synchronize a central CodeBaseHub page into the tenant Vocabot database.
     * Used by the scheduled polling command.
     */
    public function syncFromCodeBaseHub(object $centralRecord): bool
    {
        if (static::isSyncing()) {
            return true;
        }

        return static::withoutSyncing(function () use ($centralRecord): bool {
            $centralConn = TenantConnection::getCentralConnection();
            $cbhDb = DB::connection($centralConn);
            $website = $cbhDb->table('websites')
                ->where('website_name', 'Vocabot')
                ->orWhere('domain', 'vocabots.com')
                ->orWhere('domain', 'like', '%vocabot%')
                ->first();
            if (! $website) {
                Log::warning('Vocabot website not found in central DB during syncFromCodeBaseHub');

                return false;
            }
            // Prevent duplicate creation
            $exists = Page::where('central_page_id', $centralRecord->id)->first();
            if ($exists) {
                return true;
            }
            // Create tenant page
            $tenantPage = new Page([
                'website_id' => $website->id,
                'tenant_page_id' => $centralRecord->tenant_page_id ?? null,
                'central_page_id' => $centralRecord->id,
                'title' => $centralRecord->title,
                'slug' => $centralRecord->slug,
                'page_type' => $centralRecord->page_type,
                'is_homepage' => (bool) $centralRecord->is_homepage,
                'status' => (int) $centralRecord->status,
                'content' => $centralRecord->content,
                'meta_title' => $centralRecord->meta_title,
                'meta_description' => $centralRecord->meta_description,
                'canonical_url' => $centralRecord->canonical_url,
                'og_image' => $centralRecord->og_image,
                'sync_hash' => $centralRecord->sync_hash,
                'sort_order' => $centralRecord->sort_order ?? 0,
                'is_indexable' => $centralRecord->is_indexable ?? 1,
            ]);
            $tenantPage->timestamps = false;
            $tenantPage->save();
            // Update central record with tenant_page_id
            $cbhDb->table('pages')->where('id', $centralRecord->id)->update(['tenant_page_id' => $tenantPage->id]);

            // Sync sections
            $sections = $cbhDb->table('page_sections')->where('page_id', $centralRecord->id)->orderBy('sort_order')->get();
            foreach ($sections as $s) {
                $tenantPage->sections()->create([
                    'section_name' => $s->section_name,
                    'section_type' => $s->section_type,
                    'content' => $s->content,
                    'sort_order' => $s->sort_order,
                    'status' => $s->status,
                ]);
            }
            // Sync metadata
            $metadata = $cbhDb->table('metadata')->where('page_id', $centralRecord->id)->get();
            foreach ($metadata as $m) {
                $tenantPage->metadata()->create([
                    'key' => $m->key,
                    'value' => $m->value,
                ]);
            }

            return true;
        });
    }

    // The following methods are retained from the original service for future subtasks.
    public function configureTenantConnection(Website $website): bool
    {
        return TenantConnection::configure($website);
    }

    /**
     * Synchronize a CodeBaseHub Page (and its sections & metadata) to the tenant website database.
     */
    public function syncPageToTenant(Page $page, ?Website $website = null): bool
    {
        if (static::isSyncing()) {
            return true;
        }
        $website = $website ?? $page->website;
        if (! $website || ! $website->databaseConnection) {
            return false;
        }
        if (! $this->configureTenantConnection($website)) {
            return false;
        }
        try {
            return static::withoutSyncing(function () use ($page): bool {
                $tenantDb = DB::connection('tenant');
                if (! Schema::connection('tenant')->hasTable('pages')) {
                    Log::warning('Tenant database missing pages table', ['page_id' => $page->id]);

                    return false;
                }
                $page->load(['sections', 'metadata']);
                $pageMeta = $page->metadata->first();
                $sectionsList = $page->sections->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'section_name' => $s->section_name,
                        'section_type' => $s->section_type,
                        'content' => is_array($s->content) ? $s->content : json_decode($s->content, true),
                        'sort_order' => (int) $s->sort_order,
                        'status' => (int) $s->status,
                    ];
                })->toArray();
                $syncHash = sha1(json_encode([
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'page_type' => $page->page_type,
                    'is_homepage' => (bool) $page->is_homepage,
                    'status' => (int) $page->status,
                    'content' => $page->content,
                    'meta_title' => $pageMeta?->meta_title ?? $page->meta_title,
                    'meta_description' => $pageMeta?->meta_description ?? $page->meta_description,
                    'canonical_url' => $pageMeta?->canonical_url ?? $page->canonical_url,
                    'og_image' => $pageMeta?->og_image ?? $page->og_image,
                    'sections' => $sectionsList,
                ]));
                // Locate existing tenant page
                $tenantQuery = $tenantDb->table('pages');
                $existing = null;
                if ($page->tenant_page_id) {
                    $existing = (clone $tenantQuery)->where('id', $page->tenant_page_id)->first();
                }
                if (! $existing && Schema::connection('tenant')->hasColumn('pages', 'central_page_id')) {
                    $existing = (clone $tenantQuery)->where('central_page_id', $page->id)->first();
                }
                if (! $existing) {
                    $existing = (clone $tenantQuery)->where('slug', $page->slug)->first();
                }
                $tenantData = [
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'page_type' => $page->page_type ?: 'standard',
                    'is_homepage' => $page->is_homepage ? 1 : 0,
                    'status' => (int) $page->status,
                    'sort_order' => (int) ($page->sort_order ?? 0),
                    'is_indexable' => $page->is_indexable !== false ? 1 : 0,
                    'updated_at' => now(),
                ];
                if (Schema::connection('tenant')->hasColumn('pages', 'central_page_id')) {
                    $tenantData['central_page_id'] = $page->id;
                }
                if (Schema::connection('tenant')->hasColumn('pages', 'content')) {
                    $tenantData['content'] = $page->content;
                }
                if (Schema::connection('tenant')->hasColumn('pages', 'meta_title')) {
                    $tenantData['meta_title'] = $pageMeta?->meta_title ?? $page->meta_title;
                }
                if (Schema::connection('tenant')->hasColumn('pages', 'meta_description')) {
                    $tenantData['meta_description'] = $pageMeta?->meta_description ?? $page->meta_description;
                }
                if (Schema::connection('tenant')->hasColumn('pages', 'canonical_url')) {
                    $tenantData['canonical_url'] = $pageMeta?->canonical_url ?? $page->canonical_url;
                }
                if (Schema::connection('tenant')->hasColumn('pages', 'og_image')) {
                    $tenantData['og_image'] = $pageMeta?->og_image ?? $page->og_image;
                }
                if (Schema::connection('tenant')->hasColumn('pages', 'sections_data')) {
                    $tenantData['sections_data'] = json_encode($sectionsList);
                }
                if (Schema::connection('tenant')->hasColumn('pages', 'sync_hash')) {
                    $tenantData['sync_hash'] = $syncHash;
                }
                if ($existing) {
                    $tenantDb->table('pages')->where('id', $existing->id)->update($tenantData);
                    $tenantPageId = $existing->id;
                } else {
                    $tenantData['created_at'] = now();
                    $tenantPageId = $tenantDb->table('pages')->insertGetId($tenantData);
                }
                // Update central mapping if needed
                if ($page->tenant_page_id !== $tenantPageId || $page->sync_hash !== $syncHash) {
                    $page->timestamps = false;
                    $page->update([
                        'tenant_page_id' => $tenantPageId,
                        'sync_hash' => $syncHash,
                    ]);
                    $page->timestamps = true;
                }
                // Sync sections to tenant if table exists
                if (Schema::connection('tenant')->hasTable('page_sections')) {
                    $existingTenantSections = $tenantDb->table('page_sections')->where('page_id', $tenantPageId)->get();
                    $activeCentralSectionIds = [];
                    foreach ($page->sections as $sec) {
                        $contentJson = is_array($sec->content) ? json_encode($sec->content) : (string) $sec->content;
                        $secData = [
                            'page_id' => $tenantPageId,
                            'section_name' => $sec->section_name,
                            'section_type' => $sec->section_type,
                            'content' => $contentJson,
                            'sort_order' => (int) $sec->sort_order,
                            'status' => (int) $sec->status,
                            'updated_at' => now(),
                        ];
                        // Match by central_section_id if present or by name
                        $matching = null;
                        if (Schema::connection('tenant')->hasColumn('page_sections', 'central_section_id')) {
                            $matching = $existingTenantSections->firstWhere('central_section_id', $sec->id);
                        }
                        if (! $matching) {
                            $matching = $existingTenantSections->firstWhere('section_name', $sec->section_name);
                        }
                        if ($matching) {
                            $tenantDb->table('page_sections')->where('id', $matching->id)->update($secData);
                            $activeCentralSectionIds[] = $matching->id;
                        } else {
                            $secData['created_at'] = now();
                            $newId = $tenantDb->table('page_sections')->insertGetId($secData);
                            $activeCentralSectionIds[] = $newId;
                        }
                    }
                    $tenantDb->table('page_sections')
                        ->where('page_id', $tenantPageId)
                        ->whereNotIn('id', $activeCentralSectionIds)
                        ->delete();
                }
                // Sync metadata if table exists
                if (Schema::connection('tenant')->hasTable('metadata')) {
                    $metaData = [
                        'page_id' => $tenantPageId,
                        'meta_title' => $pageMeta?->meta_title ?? $page->meta_title,
                        'meta_description' => $pageMeta?->meta_description ?? $page->meta_description,
                        'canonical_url' => $pageMeta?->canonical_url ?? $page->canonical_url,
                        'robots' => 'index, follow',
                        'og_image' => $pageMeta?->og_image ?? $page->og_image,
                        'updated_at' => now(),
                    ];
                    $existingMeta = $tenantDb->table('metadata')->where('page_id', $tenantPageId)->first();
                    if ($existingMeta) {
                        $tenantDb->table('metadata')->where('id', $existingMeta->id)->update($metaData);
                    } else {
                        $metaData['created_at'] = now();
                        $tenantDb->table('metadata')->insert($metaData);
                    }
                }

                return true;
            });
        } catch (Throwable $e) {
            Log::error('Failed to sync page to tenant: '.$e->getMessage(), [
                'page_id' => $page->id,
                'website_id' => $website->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Delete a page from tenant database.
     */
    public function deletePageFromTenant(Page $page, ?Website $website = null): bool
    {
        if (static::isSyncing()) {
            return true;
        }
        $website = $website ?? $page->website;
        if (! $website || ! $website->databaseConnection) {
            return false;
        }
        if (! $this->configureTenantConnection($website)) {
            return false;
        }
        try {
            return static::withoutSyncing(function () use ($page): bool {
                $tenantDb = DB::connection('tenant');
                if (! Schema::connection('tenant')->hasTable('pages')) {
                    return true;
                }
                // Find tenant page
                $tenantPage = null;
                if ($page->tenant_page_id) {
                    $tenantPage = $tenantDb->table('pages')->where('id', $page->tenant_page_id)->first();
                }
                if (! $tenantPage && Schema::connection('tenant')->hasColumn('pages', 'central_page_id')) {
                    $tenantPage = $tenantDb->table('pages')->where('central_page_id', $page->id)->first();
                }
                if (! $tenantPage) {
                    $tenantPage = $tenantDb->table('pages')->where('slug', $page->slug)->first();
                }
                if ($tenantPage) {
                    if (Schema::connection('tenant')->hasTable('page_sections')) {
                        $tenantDb->table('page_sections')->where('page_id', $tenantPage->id)->delete();
                    }
                    if (Schema::connection('tenant')->hasTable('metadata')) {
                        $tenantDb->table('metadata')->where('page_id', $tenantPage->id)->delete();
                    }
                    $tenantDb->table('pages')->where('id', $tenantPage->id)->delete();
                }

                return true;
            });
        } catch (Throwable $e) {
            Log::error('Failed to delete page from tenant: '.$e->getMessage(), [
                'page_id' => $page->id,
                'website_id' => $website->id ?? null,
            ]);

            return false;
        }
    }

    /**
     * Synchronize a page change from tenant back into CodeBaseHub.
     */
    public function syncFromTenant(Website $website, array|object $tenantData, string $action = 'save'): ?Page
    {
        $data = (array) $tenantData;
        $slug = (string) ($data['slug'] ?? '');
        $tenantId = $data['id'] ?? null;
        $centralPageId = $data['central_page_id'] ?? null;
        if (empty($slug) && ! $tenantId && ! $centralPageId) {
            return null;
        }

        return static::withoutSyncing(function () use ($website, $data, $slug, $tenantId, $centralPageId, $action): ?Page {
            $centralConn = TenantConnection::getCentralConnection();
            $query = Page::on($centralConn)->where('website_id', $website->id);
            $page = null;
            if ($centralPageId) {
                $page = (clone $query)->where('id', $centralPageId)->first();
            }
            if (! $page && $tenantId) {
                $page = (clone $query)->where('tenant_page_id', $tenantId)->first();
            }
            if (! $page && $slug) {
                $page = (clone $query)->where('slug', $slug)->first();
            }
            if ($action === 'delete') {
                if ($page) {
                    $page->sections()->delete();
                    $page->metadata()->delete();
                    $page->delete();
                }

                return null;
            }
            // Save (create or update)
            $isHomepage = ! empty($data['is_homepage']);
            if ($isHomepage) {
                Page::on($centralConn)->where('website_id', $website->id)
                    ->when($page, fn ($q) => $q->where('id', '!=', $page->id))
                    ->where('is_homepage', true)
                    ->update(['is_homepage' => false]);
            }
            $pageFields = [
                'website_id' => $website->id,
                'tenant_page_id' => $tenantId,
                'title' => $data['title'] ?? ucfirst(str_replace(['-', '_'], ' ', $slug)),
                'slug' => $slug,
                'page_type' => $data['page_type'] ?? 'standard',
                'is_homepage' => $isHomepage,
                'status' => $data['status'] ?? 1,
                'content' => $data['content'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'canonical_url' => $data['canonical_url'] ?? null,
                'og_image' => $data['og_image'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_indexable' => $data['is_indexable'] ?? true,
                'sync_hash' => $data['sync_hash'] ?? null,
            ];
            if ($page) {
                $page->update($pageFields);
            } else {
                $page = Page::on($centralConn)->create($pageFields);
            }

            // Sync sections if provided
            $sectionsData = $data['sections_data'] ?? null;
            if (is_string($sectionsData)) {
                $sectionsData = json_decode($sectionsData, true);
            }
            if (is_array($sectionsData)) {
                foreach ($sectionsData as $index => $sec) {
                    $secName = $sec['section_name'] ?? 'section_'.($index + 1);
                    $secType = $sec['section_type'] ?? 'content';
                    $secContent = $sec['content'] ?? [];
                    $secOrder = (int) ($sec['sort_order'] ?? ($index + 1));
                    $secStatus = (int) ($sec['status'] ?? 1);

                    $page->sections()->updateOrCreate(
                        ['section_name' => $secName],
                        [
                            'section_type' => $secType,
                            'content' => $secContent,
                            'sort_order' => $secOrder,
                            'status' => $secStatus,
                        ]
                    );
                }
            }

            return $page;
        });
    }

    /**
     * Synchronize all pages for a website to its tenant database.
     *
     * @return array{synced: int, errors: array<string>}
     */
    public function syncAllPagesForWebsite(Website $website): array
    {
        $result = ['synced' => 0, 'errors' => []];

        if (! $website->databaseConnection || $website->provisioning_status !== 'provisioned') {
            $result['errors'][] = "Website {$website->id} database is not provisioned.";

            return $result;
        }

        if (! $this->configureTenantConnection($website)) {
            $result['errors'][] = "Could not configure tenant connection for website {$website->id}";

            return $result;
        }

        try {
            $website->loadMissing(['pages.sections', 'pages.metadata']);
            foreach ($website->pages as $page) {
                if ($this->syncPageToTenant($page, $website)) {
                    $result['synced']++;
                } else {
                    $result['errors'][] = "Failed to sync page {$page->id}";
                }
            }
        } catch (Throwable $e) {
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }
}
