<?php

namespace App\Services\NawalaChecker;

use App\Models\NawalaChecker\Target;
use App\Models\NawalaChecker\CheckResult;
use App\Models\NawalaChecker\Resolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckRunnerService
{
    /**
     * Check a target against all active resolvers.
     */
    public function checkTarget(Target $target): array
    {
        $resolvers = Resolver::active()->byPriority()->get();
        $results = [];
        $verdicts = [];

        foreach ($resolvers as $resolver) {
            try {
                $result = $this->performCheck($target, $resolver);
                $results[] = $result;
                $verdicts[] = $result['status'];

                // Store result in database
                $this->storeCheckResult($target, $resolver, $result);
            } catch (\Exception $e) {
                Log::error('Check failed for target ' . $target->id, [
                    'resolver' => $resolver->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fuse verdicts to determine final status
        $finalStatus = $this->fuseVerdicts($verdicts);
        $confidence = $this->calculateConfidence($verdicts, $finalStatus);

        // Update target status if changed
        if ($target->current_status !== $finalStatus) {
            $oldStatus = $target->current_status;
            $target->update([
                'current_status' => $finalStatus,
                'last_status_change_at' => now(),
                'consecutive_failures' => $finalStatus === 'OK' ? 0 : $target->consecutive_failures + 1,
            ]);

            // Trigger notification
            app(NawalaCheckerService::class)->notifyStatusChange($target, $oldStatus, $finalStatus);
        }

        $target->update(['last_checked_at' => now()]);

        return [
            'status' => $finalStatus,
            'confidence' => $confidence,
            'results' => $results,
        ];
    }

    /**
     * Perform actual check against a resolver.
     */
    protected function performCheck(Target $target, Resolver $resolver): array
    {
        $startTime = microtime(true);
        $status = 'UNKNOWN';
        $resolvedIp = null;
        $httpStatusCode = null;
        $errorMessage = null;

        try {
            // DNS Resolution
            if ($resolver->type === 'dns') {
                $resolvedIp = $this->resolveDNS($target->domain_or_url, $resolver->address);
            } elseif ($resolver->type === 'doh') {
                $resolvedIp = $this->resolveDoH($target->domain_or_url, $resolver->address);
            }

            // Check if DNS is filtered (returns known block IPs)
            if ($this->isBlockedIP($resolvedIp)) {
                $status = 'DNS_FILTERED';
            } else {
                // Try HTTP/HTTPS request
                $httpResult = $this->checkHTTP($target->domain_or_url);
                $status = $httpResult['status'];
                $httpStatusCode = $httpResult['http_code'];
                $errorMessage = $httpResult['error'];
            }
        } catch (\Exception $e) {
            $status = 'TIMEOUT';
            $errorMessage = $e->getMessage();
        }

        $responseTime = (microtime(true) - $startTime) * 1000;

        return [
            'status' => $status,
            'response_time_ms' => round($responseTime, 2),
            'resolved_ip' => $resolvedIp,
            'http_status_code' => $httpStatusCode,
            'error_message' => $errorMessage,
        ];
    }

    /**
     * Resolve DNS using standard DNS server.
     */
    protected function resolveDNS(string $domain, string $server): ?string
    {
        // Extract domain from URL if needed
        $domain = parse_url($domain, PHP_URL_HOST) ?? $domain;

        // Use gethostbyname as simple implementation
        // In production, use proper DNS library like React/DNS
        $ip = gethostbyname($domain);
        
        return $ip !== $domain ? $ip : null;
    }

    /**
     * Resolve DNS using DNS over HTTPS.
     */
    protected function resolveDoH(string $domain, string $dohServer): ?string
    {
        $domain = parse_url($domain, PHP_URL_HOST) ?? $domain;

        try {
            $response = Http::timeout(5)->get($dohServer, [
                'name' => $domain,
                'type' => 'A',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['Answer'][0]['data'])) {
                    return $data['Answer'][0]['data'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('DoH resolution failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if IP is a known block page IP.
     */
    protected function isBlockedIP(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }

        // Known Indonesian ISP block page IPs
        $blockIPs = [
            '103.10.66.', // Common Nawala block
            '36.86.63.',  // Common ISP block
            '202.67.40.', // Trust+ block
        ];

        foreach ($blockIPs as $blockIP) {
            if (str_starts_with($ip, $blockIP)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check HTTP/HTTPS accessibility.
     */
    protected function checkHTTP(string $url): array
    {
        // Ensure URL has scheme
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false]) // Skip SSL verification for testing
                ->get($url);

            $statusCode = $response->status();
            $body = $response->body();

            // Check for block page indicators
            if ($this->isBlockPage($body)) {
                return [
                    'status' => 'HTTP_BLOCKPAGE',
                    'http_code' => $statusCode,
                    'error' => 'Block page detected',
                ];
            }

            if ($statusCode >= 200 && $statusCode < 400) {
                return [
                    'status' => 'OK',
                    'http_code' => $statusCode,
                    'error' => null,
                ];
            }

            return [
                'status' => 'INCONCLUSIVE',
                'http_code' => $statusCode,
                'error' => 'HTTP ' . $statusCode,
            ];
        } catch (\Exception $e) {
            // Check if it's connection reset (RST)
            if (str_contains($e->getMessage(), 'reset') || str_contains($e->getMessage(), 'RST')) {
                return [
                    'status' => 'RST',
                    'http_code' => null,
                    'error' => 'Connection reset',
                ];
            }

            return [
                'status' => 'TIMEOUT',
                'http_code' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if response body contains block page indicators.
     */
    protected function isBlockPage(string $body): bool
    {
        $indicators = [
            'Internet Positif',
            'Situs ini diblokir',
            'This site is blocked',
            'Nawala',
            'Trust Positif',
            'Kominfo',
        ];

        foreach ($indicators as $indicator) {
            if (stripos($body, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fuse multiple verdicts into final status.
     */
    protected function fuseVerdicts(array $verdicts): string
    {
        if (empty($verdicts)) {
            return 'UNKNOWN';
        }

        $counts = array_count_values($verdicts);
        arsort($counts);

        // If majority says blocked, it's blocked
        $blockedStatuses = ['DNS_FILTERED', 'HTTP_BLOCKPAGE', 'HTTPS_SNI_BLOCK', 'RST'];
        $blockedCount = 0;
        foreach ($blockedStatuses as $status) {
            $blockedCount += $counts[$status] ?? 0;
        }

        $okCount = $counts['OK'] ?? 0;

        if ($blockedCount > $okCount) {
            return array_key_first($counts);
        }

        if ($okCount > 0) {
            return 'OK';
        }

        return 'INCONCLUSIVE';
    }

    /**
     * Calculate confidence level for the verdict.
     */
    protected function calculateConfidence(array $verdicts, string $finalStatus): int
    {
        if (empty($verdicts)) {
            return 0;
        }

        $counts = array_count_values($verdicts);
        $finalCount = $counts[$finalStatus] ?? 0;
        $total = count($verdicts);

        return (int) round(($finalCount / $total) * 100);
    }

    /**
     * Store check result in database.
     */
    protected function storeCheckResult(Target $target, Resolver $resolver, array $result): void
    {
        CheckResult::create([
            'target_id' => $target->id,
            'resolver_id' => $resolver->id,
            'status' => $result['status'],
            'response_time_ms' => $result['response_time_ms'],
            'resolved_ip' => $result['resolved_ip'],
            'http_status_code' => $result['http_status_code'],
            'error_message' => $result['error_message'],
            'confidence' => 100,
            'checked_at' => now(),
        ]);
    }
}

