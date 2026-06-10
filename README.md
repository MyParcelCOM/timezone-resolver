# timezone-resolver

A lightweight PHP package that resolves an IANA timezone identifier from address data, using the [GeoNames](https://www.geonames.org/) API.

## Requirements

- PHP 8.2+
- A free [GeoNames account](https://www.geonames.org/login) with the web services enabled

To update dependencies on a system without PHP 8 use:
```shell
docker run --rm --mount type=bind,source="$(pwd)",target=/app composer:2 composer update
```

## Installation

```bash
composer require myparcelcom/timezone-resolver
```

## Usage

```php
use MyParcelCom\TimezoneResolver\TimezoneResolver;

$resolver = new TimezoneResolver(username: 'your-geonames-username');

$timezone = $resolver->getTimezone(
    countryCode: 'NL',
    postalCode: '1043NT',
    city: 'Amsterdam',
);

// 'Europe/Amsterdam'
```

### Method signature

```php
public function getTimezone(
    string $countryCode,
    ?string $postalCode = null,
    ?string $city = null,
): ?string
```

- Tries to resolve by `postalCode` first.
- Falls back to `city` if the postal code yields no results.
- Returns `null` if no timezone can be determined.

### Using the interface

Depend on `TimezoneResolverInterface` instead of the concrete class to keep your code decoupled:

```php
use MyParcelCom\TimezoneResolver\TimezoneResolverInterface;

class MyService
{
    public function __construct(
        private readonly TimezoneResolverInterface $timezoneResolver,
    ) {}
}
```

## Configuration

The GeoNames username is passed directly to the constructor. In a Laravel application, bind the interface in a service provider:

```php
use MyParcelCom\TimezoneResolver\TimezoneResolver;
use MyParcelCom\TimezoneResolver\TimezoneResolverInterface;

$this->app->singleton(
    TimezoneResolverInterface::class,
    fn () => new TimezoneResolver(username: 'my-geonames-username'),
);
```

## Testing
```bash
docker run -v $(pwd):/app --rm -w /app php:8.4-cli vendor/bin/phpunit
```

## License

Proprietary — © MyParcel.com
