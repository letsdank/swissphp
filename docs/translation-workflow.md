# Translation workflow

SwissPHP is a PHP reimplementation of selected Swiss Ephemeris functionality.

The project is not a PHP extension and does not bind to the original C library at
runtime. The goal is to port the relevant algorithms to native PHP while keeping
behavior close to Swiss Ephemeris through focused tests and fixtures.

## Source material

The implementation is guided by:

- Swiss Ephemeris C source code
- Swiss Ephemeris public documentation
- generated fixtures from the original implementation
- independent mathematical checks where practical

The port is intentionally incremental. Each small area is implemented with tests
before moving to the next one.

## LLM assistance

An LLM was used as a development assistant during the port.

The workflow is interactive rather than automatic bulk transition:

1. A small function or subsystem from the C source is selected.
2. The relevant C code and existing PHP project context are reviewed.
3. The assistant proposes PHP code and focused PHPUnit tests.
4. The maintainer manually applies the code.
5. Tests are run locally.
6. Differences against Swiss Ephemeris fixtures are investigated.
7. The code or tests are corrected until the behavior is understood and stable.
8. The change is committed as a small step.

The assistant does not replace review. Generated code is treated as a draft and
must pass tests before it is committed.

## Prompting style

The working prompt is task-oriented and usually contains:

- the target Swiss Ephemeris function or behavior
- the local PHP API shape to preserve
- failing PHPUnit output when behavior differs
- expected values generated from Swiss Ephemeris or local fixtures
- a constraint to keep changes small and idiomatic for the current codebase

Typical prompt shape:

```text
We are porting Swiss Ephemeris to native PHP.

Please implement the next small piece of <subsystem>.
Use the existing project style and add focused PHPUnit tests.
Do not change unrelated code.

Here is the relevant behavior / failing test output / fixture value:
```

## Testing strategy

The project uses PHPUnit tests as the main safety mechanism.

Tests are based on several layers:

- exact small mathematical invariants
- generated Swiss Ephemeris fixture values
- round-trip checks
- boundary and error cases
- synthetic ephemeris files for CI-safe binary reader tests
- optional real ephemeris file tests for local verification

Synthetic ephemeris files are used in CI because real Swiss Ephemeris data files
are not bundled with the package.

## Review policy

A change is considered acceptable only when:

- it has focused tests
- the behavior is compared with a known fixture or invariant
- numerical tolerances are explicit
- unsupported behavior fails clearly
- the public API remains documented by tests or examples

Large rewrites are avoided. The project prefers many small commits over one
large automatic translation.
