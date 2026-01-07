# Repository Guidelines

## Project Structure & Module Organization
- Root entry points live in `index.php`, `login.php`, `logout.php`, and `admin/` for the administration console UI.
- Core feature areas are organized by domain: `api/` (HTTP endpoints), `lti/` (LTI launch and flow), `mod/` (optional modules), `store/` (tool store), `util/` (shared helpers), and `locale/` (translations).
- Configuration starts from `config-dist.php`; copy to `config.php` and edit for local secrets and settings.
- Dependencies are vendored in `vendor/` (including `tsugi/lib` and `koseu/lib`), so changes there should be intentional and tracked.

## Build, Test, and Development Commands
- `docker compose build` — build the local Docker image.
- `docker compose up` — start the app and initialize the database; default URL is `http://localhost:8888/tsugi`.
- `composer update tsugi/lib` — update the Tsugi PHP runtime without bumping all transitive dependencies (see `README_COMPOSER.md`).
- `composer update --ignore-platform-reqs` — advance dependencies when explicitly needed.

## Coding Style & Naming Conventions
- Follow the existing PHP style in nearby files: 4-space indentation, braces on the same line, and compact, readable conditionals.
- Use established naming patterns (`$CFG` for config, `Tsugi\` namespaces for core classes) and keep filenames lowercase with hyphens/underscores as already used.
- Prefer small, focused changes in `admin/`, `api/`, or `lti/` that mirror current structure and routing patterns.

## Testing Guidelines
- This repository does not include a formal automated test suite; `test/old_test.php` is legacy.
- Validate changes manually by running the app, visiting key admin screens, and re-running the database upgrade flow if schema changes are involved.

## Commit & Pull Request Guidelines
- Recent commits are short, sentence-style summaries (e.g., “Add item to headers.”). Keep messages concise and action-oriented.
- PRs go through GitHub and require a CLA before acceptance (see `CONTRIBUTING.md`).
- If you touch vendored libraries in `vendor/`, include clear notes in the PR so maintainers can propagate changes across related repos.

## Security & Configuration Tips
- Treat `config.php` as local-only: never commit real secrets or production credentials.
- Rotate any exposed keys immediately and update `config-dist.php` only with non-sensitive defaults.
