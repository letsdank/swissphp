# Contributing

SwissPHP is an incremental native PHP reimplementation of selected Swiss Ephemeris functionality.

## Development setup

```bash
composer install
vendor/bin/phpunit --configuration phpunit.xml.dist
```

## Contribution guidelines

- Keep changes focused and small.
- Add or update PHPUnit tests for behavior changes.
- Prefer fixtures, invariants, or comparisons with Swiss Ephemeris output for numerical code.
- Use explicit numerical tolerances in tests.
- Do not bundle Swiss Ephemeris data files in the repository.
- Keep generated local artifacts out of commits.

## Translation workflow

The porting workflow, including LLM-assisted development, is documented in
docs/translation-workflow.md (docs/translation-workflow.md).
