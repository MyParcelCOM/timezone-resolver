<?php

declare(strict_types=1);

namespace MyParcelCom\TimezoneResolver\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MyParcelCom\TimezoneResolver\GeoNames;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class GeoNamesTest extends TestCase
{
    public function test_resolves_timezone_by_country_and_postal_code(): void
    {
        $requestHistoryContainer = [];
        $client = $this->mockGuzzle(
            $requestHistoryContainer,
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-postal-code-search-response.json')),
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-timezone-search-response.json')),
        );

        $geoNames = new GeoNames('test-user', $client);

        assertSame('Europe/Amsterdam', $geoNames->getTimezone('NL', postalCode: '1043NT'));

        /** @var Request $req1 */
        $req1 = $requestHistoryContainer[0]['request'];
        assertSame('/postalCodeSearchJSON', $req1->getUri()->getPath());
        parse_str($req1->getUri()->getQuery(), $query);
        assertTrue(isset($query['postalcode']));
        assertSame('1043NT', $query['postalcode']);
        assertSame('test-user', $query['username']);

        /** @var Request $req2 */
        $req2 = $requestHistoryContainer[1]['request'];
        assertSame('/timezoneJSON', $req2->getUri()->getPath());
        parse_str($req2->getUri()->getQuery(), $query);
        assertTrue(isset($query['lat'], $query['lng']));
    }

    public function test_falls_back_to_city_when_postal_code_returns_no_results(): void
    {
        $requestHistoryContainer = [];
        $client = $this->mockGuzzle(
            $requestHistoryContainer,
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-postal-code-search-empty-response.json')),
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-postal-code-search-response.json')),
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-timezone-search-response.json')),
        );

        $geoNames = new GeoNames('test-user', $client);

        assertSame('Europe/Amsterdam', $geoNames->getTimezone('NL', postalCode: '1043NT', city: 'Amsterdam'));

        /** @var Request $req1 */
        $req1 = $requestHistoryContainer[0]['request'];
        parse_str($req1->getUri()->getQuery(), $query);
        assertTrue(isset($query['postalcode']));
        assertSame('1043NT', $query['postalcode']);

        /** @var Request $req2 */
        $req2 = $requestHistoryContainer[1]['request'];
        parse_str($req2->getUri()->getQuery(), $query);
        assertTrue(isset($query['placename']));
        assertSame('Amsterdam', $query['placename']);

        /** @var Request $req3 */
        $req3 = $requestHistoryContainer[2]['request'];
        assertSame('/timezoneJSON', $req3->getUri()->getPath());
        parse_str($req3->getUri()->getQuery(), $query);
        assertTrue(isset($query['lat'], $query['lng']));
    }

    public function test_returns_null_when_no_postal_code_results_and_no_city_given(): void
    {
        $requestHistoryContainer = [];
        $client = $this->mockGuzzle(
            $requestHistoryContainer,
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-postal-code-search-empty-response.json')),
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-postal-code-search-empty-response.json')),
        );

        $geoNames = new GeoNames('test-user', $client);

        assertNull($geoNames->getTimezone('NL', postalCode: '1043NT'));
    }

    public function test_returns_null_when_both_postal_code_and_city_return_no_results(): void
    {
        $requestHistoryContainer = [];
        $client = $this->mockGuzzle(
            $requestHistoryContainer,
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-postal-code-search-empty-response.json')),
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-postal-code-search-empty-response.json')),
        );

        $geoNames = new GeoNames('test-user', $client);

        assertNull($geoNames->getTimezone('NL', postalCode: '1043NT', city: 'Amsterdam'));
    }

    public function test_uses_https_endpoint(): void
    {
        $requestHistoryContainer = [];
        $client = $this->mockGuzzle(
            $requestHistoryContainer,
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-postal-code-search-response.json')),
            new Response(200, body: file_get_contents(__DIR__ . '/Stubs/geo-names-timezone-search-response.json')),
        );

        $geoNames = new GeoNames('test-user', $client);
        $geoNames->getTimezone('NL', postalCode: '1043NT');

        foreach ($requestHistoryContainer as $transaction) {
            /** @var Request $request */
            $request = $transaction['request'];
            assertSame('https', $request->getUri()->getScheme());
        }
    }

    private function mockGuzzle(array &$requestHistoryContainer, Response ...$responses): Client
    {
        $history = Middleware::history($requestHistoryContainer);
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        $handlerStack->push($history);

        return new Client(['handler' => $handlerStack]);
    }
}
