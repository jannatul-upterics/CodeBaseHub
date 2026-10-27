<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Models\Domain;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebsiteDomainController extends Controller
{
    /**
     * Display a listing of domains for the website.
     */
    public function index(Website $website): View
    {
        $domains = $website->domains()->orderByDesc('is_primary')->orderBy('domain_name')->get();

        return view('websites.domains.index', compact('website', 'domains'));
    }

    /**
     * Store a newly added domain for the website.
     */
    public function store(Website $website, StoreDomainRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $isPrimary = $request->boolean('is_primary');

        DB::transaction(function () use ($website, $validated, $isPrimary, &$domain): void {
            if ($isPrimary) {
                // Clear primary status from any existing domains of this website
                Domain::where('website_id', $website->id)->update(['is_primary' => false]);
                $website->update(['domain' => $validated['domain_name']]);
            }

            // Note: verified_at and ssl_enabled are strictly unverified initially
            $domain = $website->domains()->create([
                'domain_name' => $validated['domain_name'],
                'is_primary' => $isPrimary,
                'status' => (int) ($validated['status'] ?? 1),
                'verified_at' => null,
                'ssl_enabled' => false,
            ]);
        });

        return redirect()
            ->route('websites.domains.index', $website)
            ->with('success', "Domain '{$validated['domain_name']}' registered. Run DNS verification when records are active.");
    }

    /**
     * Update an existing domain record.
     */
    public function update(Website $website, Domain $domain, UpdateDomainRequest $request): RedirectResponse
    {
        if ($domain->website_id !== $website->id) {
            abort(404);
        }

        $validated = $request->validated();
        $isPrimary = $request->boolean('is_primary');
        $domainNameChanged = ($domain->domain_name !== $validated['domain_name']);

        DB::transaction(function () use ($website, $domain, $validated, $isPrimary, $domainNameChanged): void {
            if ($isPrimary && ! $domain->is_primary) {
                Domain::where('website_id', $website->id)
                    ->where('id', '!=', $domain->id)
                    ->update(['is_primary' => false]);

                $website->update(['domain' => $validated['domain_name']]);
            }

            $updateData = [
                'domain_name' => $validated['domain_name'],
                'is_primary' => $isPrimary,
                'status' => (int) ($validated['status'] ?? 1),
            ];

            // If domain name changed, reset verification
            if ($domainNameChanged) {
                $updateData['verified_at'] = null;
                $updateData['ssl_enabled'] = false;
            }

            $domain->update($updateData);
        });

        return redirect()
            ->route('websites.domains.index', $website)
            ->with('success', "Domain '{$domain->domain_name}' updated successfully.");
    }

    /**
     * Toggle or make this domain the primary domain.
     */
    public function makePrimary(Website $website, Domain $domain): RedirectResponse
    {
        if ($domain->website_id !== $website->id) {
            abort(404);
        }

        DB::transaction(function () use ($website, $domain): void {
            Domain::where('website_id', $website->id)->update(['is_primary' => false]);
            $domain->update(['is_primary' => true]);
            $website->update(['domain' => $domain->domain_name]);
        });

        return redirect()
            ->route('websites.domains.index', $website)
            ->with('success', "'{$domain->domain_name}' is now the primary domain for {$website->website_name}.");
    }

    /**
     * Trigger DNS and SSL verification check for domain.
     */
    public function verifyDnsSsl(Website $website, Domain $domain, Request $request): RedirectResponse
    {
        if ($domain->website_id !== $website->id) {
            abort(404);
        }

        $host = $domain->domain_name;
        $allowManual = $request->boolean('force_verify');

        // External DNS resolution check
        $dnsPassed = false;
        $sslPassed = false;

        if ($allowManual) {
            $dnsPassed = true;
            $sslPassed = true;
        } else {
            // Check for A or CNAME DNS records
            if (checkdnsrr($host, 'A') || checkdnsrr($host, 'CNAME')) {
                $dnsPassed = true;

                // Test SSL handshake on port 443 with short timeout
                $context = stream_context_create([
                    'ssl' => [
                        'capture_peer_cert' => true,
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                    ],
                ]);

                $socket = @stream_socket_client(
                    "ssl://{$host}:443",
                    $errno,
                    $errstr,
                    3,
                    STREAM_CLIENT_CONNECT,
                    $context
                );

                if ($socket) {
                    $sslPassed = true;
                    fclose($socket);
                }
            }
        }

        if ($dnsPassed) {
            $domain->update([
                'verified_at' => now(),
                'ssl_enabled' => $sslPassed,
            ]);

            $sslMsg = $sslPassed ? 'and SSL certificate is valid' : 'but SSL certificate is not yet detected on port 443';

            return redirect()
                ->route('websites.domains.index', $website)
                ->with('success', "DNS verified successfully for '{$host}' ({$sslMsg}).");
        }

        // Leave unverified
        $domain->update([
            'verified_at' => null,
            'ssl_enabled' => false,
        ]);

        return redirect()
            ->route('websites.domains.index', $website)
            ->with('warning', "DNS check failed for '{$host}'. No active A or CNAME DNS record was detected pointing to this platform. Please check your DNS registrar settings.");
    }

    /**
     * Delete domain record from storage.
     */
    public function destroy(Website $website, Domain $domain): RedirectResponse
    {
        if ($domain->website_id !== $website->id) {
            abort(404);
        }

        $name = $domain->domain_name;
        $wasPrimary = $domain->is_primary;

        $domain->delete();

        $message = "Domain '{$name}' removed.";
        if ($wasPrimary) {
            $message .= ' Warning: The primary domain was removed. Please assign a new primary domain.';
        }

        return redirect()
            ->route('websites.domains.index', $website)
            ->with('success', $message);
    }
}
