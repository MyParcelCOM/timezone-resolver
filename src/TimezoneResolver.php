<?php

declare(strict_types=1);

namespace MyParcelCom\TimezoneResolver;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use JsonException;

readonly class TimezoneResolver implements TimezoneResolverInterface
{
    private Client $client;

    public function __construct(
        private string $username,
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client();
    }

    /**
     * @throws JsonException
     * @throws GuzzleException
     */
    public function getTimezone(
        string $countryCode,
        ?string $postalCode = null,
        ?string $city = null,
    ): ?string {
        $data = null;

        if ($postalCode !== null) {
            $data = $this->request('postalCodeSearchJSON', [
                'country'    => $countryCode,
                'postalcode' => $postalCode,
                'maxRows'    => 1,
            ]);
        }

        if (empty($data['postalCodes']) && $city !== null) {
            $data = $this->request('postalCodeSearchJSON', [
                'country'   => $countryCode,
                'placename' => $city,
                'maxRows'   => 1,
            ]);
        }

        if (empty($data['postalCodes'])) {
            return null;
        }

        $lat = $data['postalCodes'][0]['lat'] ?? null;
        $lng = $data['postalCodes'][0]['lng'] ?? null;

        if ($lat === null || $lng === null) {
            return null;
        }

        return $this->getTimezoneByCoordinates((float) $lat, (float) $lng);
    }

    /**
     * @throws JsonException|GuzzleException
     */
    private function getTimezoneByCoordinates(float $lat, float $lng): ?string
    {
        $data = $this->request('timezoneJSON', ['lat' => $lat, 'lng' => $lng]);

        return $data['timezoneId'] ?? null;
    }

    /**
     * @throws JsonException|GuzzleException
     */
    private function request(string $endpoint, array $query): array
    {
        $url = (string) Uri::fromParts([
            'scheme' => 'https',
            'host'   => 'secure.geonames.org',
            'path'   => $endpoint,
            'query'  => http_build_query([...$query, 'username' => $this->username]),
        ]);

        $response = $this->client->get($url);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
