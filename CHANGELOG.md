# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.1-skeleton] - 2026-05-07

### Added
- 4-level module skeleton : `App\Modules\Migration\Modules\Migration\Modules\{Generator,Extract,Import}\`.
- 5 ServiceProviders wired across all 4 levels.
- Stub commands : `migrate:generate`, `migrate:extract`, `migrate:import`.
- Tooling : Pint, PHPStan level 8 (Larastan), Pest 4, GitHub Actions.

### Removed (vs `xethron/migrations-generator`)
- All legacy code (PHP < 8.4, Laravel < 13, doctrine/dbal, xethron/laravel-4-generators).

[Unreleased]: https://github.com/SimardRichard/laravel-migrations-generator/compare/v0.0.1-skeleton...HEAD
[0.0.1-skeleton]: https://github.com/SimardRichard/laravel-migrations-generator/releases/tag/v0.0.1-skeleton
