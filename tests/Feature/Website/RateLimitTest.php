<?php

namespace Tests\Feature\Website;

/**
 * Exercises the real "website" limiter: phpunit.xml sets CACHE_DRIVER=array, so the limiter's
 * counters live in an in-process cache that starts empty for every test (fresh application).
 */
class RateLimitTest extends WebsiteTestCase
{
    private const URL = '/api/website/v1/categories';

    /**
     * @return array<int, int> status code of each request, in order
     */
    private function hit(int $times, array $headers = []): array
    {
        // Headers go per request: withHeaders() would stick to every later request in the test.
        $statuses = [];
        for ($i = 0; $i < $times; $i++) {
            $statuses[] = $this->getJson(self::URL, $headers)->getStatusCode();
        }

        return $statuses;
    }

    public function test_public_clients_get_429_after_60_requests(): void
    {
        config(['website.api_key' => 'k']);

        $statuses = $this->hit(61);

        $this->assertSame(array_fill(0, 60, 200), array_slice($statuses, 0, 60));
        $this->assertSame(429, $statuses[60]);
    }

    public function test_website_server_key_lifts_the_limit(): void
    {
        config(['website.api_key' => 'k']);

        $statuses = $this->hit(61, ['X-Website-Key' => 'k']);

        $this->assertSame(array_fill(0, 61, 200), $statuses);

        $response = $this->getJson(self::URL, ['X-Website-Key' => 'k'])->assertOk();
        $this->assertSame('1000', $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_wrong_key_is_treated_as_public(): void
    {
        config(['website.api_key' => 'k']);

        $statuses = $this->hit(61, ['X-Website-Key' => 'not-k']);

        $this->assertSame(array_fill(0, 60, 200), array_slice($statuses, 0, 60));
        $this->assertSame(429, $statuses[60]);
    }

    /**
     * @dataProvider emptyKeyHeaders
     */
    public function test_empty_key_never_matches_an_unset_server_key(array $headers): void
    {
        // WEBSITE_API_KEY not configured: a missing or empty header must not unlock the high limit.
        config(['website.api_key' => null]);

        $statuses = $this->hit(61, $headers);

        $this->assertSame(429, $statuses[60]);
    }

    public function emptyKeyHeaders(): array
    {
        return [
            'no header' => [[]],
            'empty header' => [['X-Website-Key' => '']],
        ];
    }

    public function test_server_key_has_its_own_bucket_separate_from_the_public_one(): void
    {
        config(['website.api_key' => 'k']);

        // Exhaust the public (per-IP) bucket.
        $this->assertSame(429, $this->hit(61)[60]);

        // The Next.js server shares the same IP but not the bucket.
        $this->getJson(self::URL, ['X-Website-Key' => 'k'])->assertOk();
        // ...and public clients are still blocked.
        $this->getJson(self::URL)->assertStatus(429);
    }

    public function test_limited_response_reports_the_public_limit(): void
    {
        config(['website.api_key' => 'k']);

        $first = $this->getJson(self::URL)->assertOk();
        $this->assertSame('60', $first->headers->get('X-RateLimit-Limit'));
        $this->assertSame('59', $first->headers->get('X-RateLimit-Remaining'));
    }
}
