# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0-alpha] - 2026-05-07

### Added
- Generator SQLite : full implementation of `migrate:generate` for SQLite databases.
- `SchemaDriver` interface + `SqliteDriver` reading via `PRAGMA` / `sqlite_master`.
- `SchemaDriverFactory` (SQLite-only for plan 1, additional drivers in plans 2-6).
- 5 Schema DTOs (Database / Table / Column / Index / ForeignKey) shared at level 2.
- 3 Enums (`ColumnType`, `IndexType`, `OnAction`) — 41 column-type cases.
- 14 typed exceptions in 4 branches (Configuration / SchemaRead / Generation / Write)
  with `context()` for structured debugging.
- 4-action pipeline: `ReadDatabaseAction` → `BuildMigrationPlanAction`
  → `RenderMigrationAction` → `WriteMigrationFileAction`.
- 3 Renderers (Column, Index, ForeignKey) producing Blueprint-style code.
- Anonymous-class migrations with topological FK ordering (Kahn's algorithm).
- Two-file output : `*_create_tables.php` + `*_add_foreign_keys.php`.
- `DefaultValueResolver` (CURRENT_TIMESTAMP detection, raw expressions, typed scalars).
- `ColumnTypeResolver` (SQLite native → `ColumnType` mapping with Raw fallback).
- `MigrationWriter` interface + `FilesystemMigrationWriter` (force-aware overwrites).
- `StubRenderer` interface + `DefaultStubRenderer` + 2 publishable stubs.
- Dependency injection wired in `GeneratorServiceProvider`.
- Exit codes mapped from exception branches (1=Config, 2=SchemaRead, 3=Generation, 4=Write).
- 88 tests (Pest 4) covering unit + Feature + E2E round-trip.

## [0.0.1-skeleton] - 2026-05-07

### Added
- 4-level module skeleton : `App\Modules\Migration\Modules\Migration\Modules\{Generator,Extract,Import}\`.
- 5 ServiceProviders wired across all 4 levels.
- Stub commands : `migrate:generate`, `migrate:extract`, `migrate:import`.
- Tooling : Pint, PHPStan level 8 (Larastan), Pest 4, GitHub Actions.

### Removed (vs `xethron/migrations-generator`)
- All legacy code (PHP < 8.4, Laravel < 13, doctrine/dbal, xethron/laravel-4-generators).

[Unreleased]: https://github.com/SimardRichard/laravel-migrations-generator/compare/v0.1.0-alpha...HEAD
[0.1.0-alpha]: https://github.com/SimardRichard/laravel-migrations-generator/compare/v0.0.1-skeleton...v0.1.0-alpha
[0.0.1-skeleton]: https://github.com/SimardRichard/laravel-migrations-generator/releases/tag/v0.0.1-skeleton
