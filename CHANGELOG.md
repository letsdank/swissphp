# Changelog

All notable changes to SwissPHP will be documented in this file.

The project follows semantic versioning once tagged releases begin.

## Unreleased

### Added

- Native PHP calculation core for Julian dates, Delta T, sidereal time, coordinates, houses, and Moshier positions.
- Apparent/geometric correction layers, rise/set calculations, phenomena, nodes/apsides, orbital elements, fixed stars, and aspects.
- Initial Swiss Ephemeris `.se1` file reader foundation in development branches.
- PHPUnit coverage for implemented calculation areas.
- CI workflow for PHP 8.4.

### Changed

- Public package metadata is prepared for Composer/Packagist publication.

### Notes

SwissPHP is still in active development and is not yet a drop-in replacement for the original Swiss Ephemeris.
