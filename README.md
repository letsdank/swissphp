# SwissPHP

[![CI](https://github.com/letsdank/swissphp/actions/workflows/ci.yml/badge.svg)](https://github.com/letsdank/swissphp/actions/workflows/ci.yml)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4.svg)](https://www.php.net/)

SwissPHP is a PHP port/reimplementation of core Swiss Ephemeris functionality.

The project is in active development. It already includes a tested calculation core for dates, delta T, sidereal time, coordinates, houses, Moshier planetary and lunar positions, rise/set calculations, phenomena, nodes/apsides, orbital elements, fixed stars and early support for Swiss Ephemeris files.

## Development workflow

This project is an incremental native PHP reimplementation of Swiss Ephemeris.
The translation and testing workflow is documented in
[docs/translation-workflow.md](docs/translation-workflow.md).

## Requirements

- PHP 8.4+
- Composer

## Installation

Packagist publication is planned. For now, install from source:

```bash
git clone https://github.com/letsdank/swissphp.git
cd swissphp
composer install
```

For local development:

```bash
composer install
```

## Running Tests

```bash
vendor/bin/phpunit --configuration phpunit.xml.dist
```

## Example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use SwissEph\Calculator;
use SwissEph\Catalog;

$result = Calculator::calcUt(
    2451545.0,
    Catalog::SE_SUN,
    Catalog::SEFLG_MOSEPH
);

print_r($result);
```

## Status

This library is not a drop-in replacement for the original Swiss Ephemeris yet.

Implemented areas include:

- Julian date conversion
- Delta T
- Sidereal time
- Coordinate transformations
- Houses
- Moshier Sun, Moon, and planet calculations
- Apparent/geometric correction layers
- Rise, set, and transit calculations
- Phenomena
- Longitude crossings
- Nodes and apsides
- Orbital elements
- Fixed stars
- Initial ephemeris file path and header support

Still in progress:

- Full Swiss Ephemeris .se1 binary coefficient reader
- JPL ephemeris support
- Full asteroid file support
- Fill fixed-star catalog support
- Eclipses and occultations
- Heliacal events

## License

This project is licensed under AGPL-3.0-or-later.

Swiss Ephemeris is distributed by Astrodienst AG under AGPL/commercial licensing terms. Review the original Swiss Ephemeris licensing terms before using this project in production or network-accessible services.