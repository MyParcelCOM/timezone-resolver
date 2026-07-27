<?php

declare(strict_types=1);

namespace MyParcelCom\TimezoneResolver;

use JsonException;

interface TimezoneResolverInterface
{
    /**
     * Resolve the IANA timezone identifier for the given address components.
     *
     * Returns null when no timezone can be determined.
     *
     * @throws JsonException
     */
    public function getTimezone(
        string $countryCode,
        ?string $postalCode = null,
        ?string $city = null,
    ): ?string;
}
