# Repository Guidelines

## Project Structure & Module Organization
- Root entry points live in `index.php`, `login.php`, `logout.php`, and `admin/` for the administration console UI.
- Core feature areas are organized by domain: `api/` (HTTP endpoints), `lti/` (LTI launch and flow), `mod/` (optional modules), `tool/` (in-tree tools shipped with this repo), `store/` (tool store), `util/` (shared helpers), and `locale/` (translations).
- Configuration starts from `config-dist.php`; copy to `config.php` and edit for local secrets and settings.
- Dependencies are vendored in `vendor/` (including `tsugi/lib` and `koseu/lib`), so changes there should be intentional and tracked.

## Dependency management (Composer)
- **`vendor/` is committed to git** on purpose: production deploys do not run Composer, which avoids deploy-time network/hiccups and keeps releases reproducible. Any dependency bump must update **`composer.json`**, **`composer.lock`**, and the **`vendor/`** tree together in the same change.
- **Dev packages are gitignored.** `.gitignore` excludes `vendor/phpunit/`, `vendor/phpstan/`, `vendor/myclabs/`, `vendor/symfony/panther/`, and other `require-dev` trees. Production checkouts only get what git tracks.
- **Always finish a vendor commit with `composer install --no-dev --ignore-platform-reqs`.** If you run `composer update` without `--no-dev`, Composer installs dev tools and rewrites `vendor/composer/autoload_*.php` to `require()` those packages—but they are **not committed**, so production fatals (e.g. missing `myclabs/deep-copy`). The lock file may still list dev deps for local QA; the committed autoload must match the gitignored production vendor only.
- **Install git hooks after a fresh checkout:** run `bash qa/install-git-hooks.sh` so the pre-commit hook runs `qa/pre-commit-vendor-check.sh`. Agents should verify `.git/hooks/pre-commit` exists before composer/vendor commits (see `.cursor/rules/git-hooks-and-vendor.mdc`).
- See **`README_COMPOSER.md`** for day-to-day commands. Common patterns:
  - `composer update <package> --ignore-platform-reqs -W --no-dev` — bump one direct dependency and its transitive updates.
  - `composer install --no-dev --ignore-platform-reqs` — **required last step** before committing `vendor/`; regenerates autoload without dev references.
  - `composer audit` — check for security advisories before/after updates.
- **`platform-check` is false** and the PHP constraint is `>=8.4.0`; do not raise the PHP version unless explicitly asked. Use `--ignore-platform-reqs` locally if your CLI PHP is newer (e.g. 8.5) than a package’s declared support.
- **Pinning strategy:** security-sensitive packages (`phpseclib`, `firebase/php-jwt`, `htmlpurifier`) and patched Symfony CVE fixes use **exact versions** in `composer.json` so `composer update` cannot accidentally jump Symfony **8.0 → 8.1** or similar. Other libraries use `>=` floors; the lockfile is the real pin.
- **Symfony is mixed 7.4 + 8.0** (e.g. `http-foundation`/`http-kernel` on 7.4.x, `routing`/`mime` on 8.0.x). That is intentional. When advancing Symfony, stay on **patch releases within the current minor** (e.g. 7.4.12, 8.0.13)—do not bump to 8.1 unless planned. Pin `error-handler` and `var-dumper` to **7.4.x** when updating `http-kernel` or Composer may pull 8.1.
- **Do not remove direct dependencies** (e.g. `symfony/browser-kit`, `symfony/routing`) even if core Tsugi code does not import them; optional modules and tools rely on them.
- **Deferred / coordinated upgrades** (do not batch casually): `firebase/php-jwt` 7.x (requires `google/auth` ^1.50 and `google/apiclient` ^2.19; test LTI 1.3 launches), `minishlink/web-push` 10.x, Symfony 8.1+, and `symfony/process` 8.x. Track these with `composer audit` and plan explicit upgrade PRs.
- **`lib/composer.json`** (tsugi/lib submodule) has its own pins; keep `phpseclib` and JWT versions aligned with the root when you touch crypto/LTI dependencies.

## Build, Test, and Development Commands
- `bash qa/install-git-hooks.sh` — install the local **pre-commit** hook (not automatic on clone; Cursor agents should verify it exists at session start).
- `bash qa/pre-commit-vendor-check.sh` — sanity-check that committed autoload does not reference gitignored dev packages.
- `docker compose build` — build the local Docker image.
- `docker compose up` — start the app and initialize the database; default URL is `http://localhost:8888/tsugi`.
- `composer update tsugi/lib` — update the Tsugi PHP runtime without bumping all transitive dependencies (see `README_COMPOSER.md`).
- `composer update --ignore-platform-reqs` — advance dependencies when explicitly needed.

## Session and auth helpers (`lib/include/lms_lib.php`)
- **`isLoggedIn()`** means the current user id is non-zero (`loggedInUserId() !== 0`). It **does not** require a non-zero **`currentContextId()`** (course/context). Users can be authenticated site-wide without a course.
- **Contexts and LTI:** The long-term model is that users **log in first**, then **pick among multiple contexts** (courses). **For now**, there is typically **one** active context at a time unless the request is an **LTI launch** (where the LMS may bind a course). **LTI launches can also be “no context”** (system-wide or non-course tools), so “logged in” must remain valid when **`currentContextId()` is zero**.
- Use **`currentContextId()`** (and checks like `currentContextId() !== 0`) only when the code path **requires** a course/context. Do **not** change `isLoggedIn()` to require context, and do not “fix” call sites by folding context into login unless the product requirement explicitly asks for it.
- **`loggedInUserId()`** and **`currentContextId()`** are paired by source (session vs `$USER`/`$CONTEXT`); see the docblocks in `lms_lib.php` before refactoring auth checks.

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
