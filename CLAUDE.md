# CLAUDE.md

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

Guidance for Claude Code in this repo. For **code structure/navigation** (where things live, what calls what) query graphify (`graphify query "..."`; see `## graphify`). This file holds the **conventions, domain semantics, and gotchas** the code doesn't express.

## Project Overview

**Engaja** — Laravel 12 app to manage educational events, enrollments, attendance, and engagement reports for the Alfa-EJA project (formations, workshops, meetings, lives).

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.2+) · **Frontend:** Bootstrap 5 + Blade + Livewire 4
- **DB:** PostgreSQL · **Auth:** Breeze + Spatie Laravel Permission
- **PDF:** spatie/laravel-pdf (Browsershot/Puppeteer) · **Imports:** maatwebsite/excel · **QR:** simplesoftwareio/simple-qrcode

## Common Commands

```bash
php artisan serve                        # dev server :8000
npm run dev                              # Vite watch (prod: npm run build)
php artisan migrate --seed               # fresh setup (roles, permissions, seed)
./vendor/bin/pint                        # code style (PSR-12)
php artisan test --filter TestClassName  # single test
php artisan limesurvey:importar-dados    # warm LimeSurvey cache (24h TTL)
```

## Domain Model

Hierarchy: **Evento → Atividade → Presença/Inscrição**. Non-obvious bits:
- `Atividade.carga_horaria` is in **minutes** (column name is legacy). Tied to a `Municipio`; planning/closure checklists are JSON columns.
- `Evento` ties to `Eixo`, `acao_geral`, `subacao` (Alfa-EJA constants defined in the model); has planning/closure checklists.
- `Participante` is **separate** from `User`. `Inscricao` = enrollment in an Evento. `Presenca` = attendance at an Atividade, with `avaliacao_respondida` (bool). `Agendamento` schedules a Participante for an AtividadeAcao.

### UI ↔ Model ↔ Table naming

| Term in UI | Model | Table |
|---|---|---|
| Ação pedagógica | `Evento` | `eventos` |
| Momento / Encontro | `Atividade` | `atividades` |
| Relatório do Momento | `AvaliacaoAtividade` | `avaliacao_atividades` |

## Authorization (Spatie Permission)

Roles: `administrador`, `gerente`, `eq_pedagogica`, `articulador`, `participante`, `SME`. Permissions follow `resource.action` (e.g. `evento.criar`, `presenca.abrir`), guarded via `role:`/`permission:` middleware. Most management routes require `administrador|gerente|eq_pedagogica|articulador`.

## Conventions & Patterns

- **Filter+sort (Blade reports)** — used in `DashboardController::index` / `RelatorioQuantitativoController::index`: `$sortable = ['key'=>'db_column']` map, direction validated to `asc`/`desc`; filters via `->when(...)`; aggregated counts via `->withCount(['rel as alias' => fn($q)=>...])`; `->appends($request->query())` to keep filters across pagination; sort links built inline with `http_build_query` (no shared helper).
- **Import flow (Presença/Inscrição)** — upload→parse xlsx→session → paginated preview → confirm→persist. Headers parsed tolerantly.
- **Seeding** — `RolesPermissionsSeeder` (roles/permissions) + `DatabaseSeeder` (admin `admin@engaja.local` + sample data). Always `--seed` on fresh installs.
- **Tabelas (data tables)** — TODA tabela nova (e migração de tabelas existentes) deve usar o componente `<x-data-table>` (AG-Grid; `resources/views/components/data-table.blade.php` + `resources/js/lib/ag-grid-table.js`), não `<table>` Bootstrap cru. Padrão: monta `$columns` (`field`, `headerName`, `flex`/`minWidth`, `align`, `html`=>true p/ badges/dropdowns/forms via cellRenderer — desabilita sort) e `$rows` (arrays c/ `id`). HTML de célula é injetado via `innerHTML` — handlers inline (`onchange="this.form.submit()"`) funcionam; use `csrf_field()` p/ forms. Paginação server-side existente: mantenha `->paginate()` + `{{ $x->links() }}` e passe `:pagination="false"` (grid mostra a página atual). Referências: `usuarios/index.blade.php`, `atividades/show.blade.php`.

## Reports

- **Relatórios do Momento** (`/relatorios-avaliacao`, `AvaliacaoAtividadeController`) — qualitative reports per atividade, grouped by ação/momento.
- **Relatório Quantitativo** (`/relatorio-quantitativo`, `RelatorioQuantitativoController`) — attendance/evaluation counts per encontro. Filters: ação, momento, município, date range, período (manhã/tarde/noite via `hora_inicio`). Grouped by ação with subtotal rows, all columns sortable. Cascading filters fetch `GET /relatorio-quantitativo/momentos` (JSON) → filtered `momentos`+`municipios`.

## LimeSurvey Integration & Avaliações Dashboard

- **Entry:** `GET /dashboards/avaliacoes?fonte=limesurvey&survey_id=X`; AJAX `GET /dashboards/avaliacoes/dados` → `DashboardController::avaliacoesDataLimeSurvey()` → `LimeSurveyDashboardService::buildPayload()`. Survey list at `/dashboards/leitura-mundo`. Payload: `{totais, perguntas, bi_matrizes, question_blocks, recentes}`.
- **Client** (`LimeSurveyClient`) — JSON-RPC 2.0, session auto-acquired/released per call. Config in `config/services.php` (`LIMESURVEY_*` env).
- **Cache (database driver):** TTL via `LIMESURVEY_CACHE_MINUTES` (default 5). Keys: `limesurvey:{id}:questions`, `:responses`, `:answer_options:{qid}` (type-L only). Daily warm-up `limesurvey:importar-dados` at 00:00 UTC (`routes/console.php`) caches active surveys 24h; on-demand fallback if scheduler fails.
- **Service** — infers question types (`texto`/`boolean`/`escala`/`numero`); município-level aggregation via email→município mapping; `de`/`ate` date filters applied **post-cache**.
- **Frontend (Chart.js, not ApexCharts):** two render paths — new `question_blocks` (`renderSimpleQuestionCard`/`renderMatrixBlockCard`) and legacy `perguntas`/`bi_matrizes` (`renderLegacyCharts`). Circular charts (doughnut/polarArea): `maintainAspectRatio:false` + `canvas.style.height` to avoid runaway height.

## PDF Generation (spatie/laravel-pdf + Browsershot/Puppeteer)

`Pdf::view()` → Puppeteer/Chromium. Node via fnm; path in `.env` `LARAVEL_PDF_NODE_BINARY`.

- **Local vs prod** (`AppServiceProvider::configureRemotePdfRendering()`): without `LARAVEL_PDF_REMOTE_HOST`, early-return with **no** Browsershot customization (local Chromium, package defaults) — the early return is **intentional, keep it**. With the host set, Browsershot points at remote browserless (`setRemoteInstance($host,$port)->noSandbox()`) + wide timeout. `tests/Feature/PdfRemoteInstanceTest.php` covers both cases — update it when touching this method.
- **Large PDFs (timeout/memory)** — config in `config/dashboard.php` (`dashboard.pdf.*`): `max_atividades` (200) caps rows in `DashboardController::export()` (count the filtered universe with `(clone $query)->count()` before `->limit()`; truncated PDFs show a "Resultado parcial" banner); `memory_limit` (512M) via pointful `ini_set`; `timeout` (120s) on the remote Browsershot. Defaults live **only** in config (reading `env()`); call sites use `config(...)` with no 2nd arg.
- **Macro `->withAlfaEjaBrand()`** (`AppServiceProvider::registerPdfMacros()`) — sets Puppeteer margins + native header/footer templates with the institutional base64 images. Portrait: `->withAlfaEjaBrand()` (margins `28 14 22 14`mm); landscape: `->withAlfaEjaBrand(35,10,25,10)`.
- **Controller return type:** `PdfBuilder` implements `Responsable` — return it directly (declare `: PdfBuilder` or omit the type). **Never** `: Symfony\Component\HttpFoundation\Response`.

**Puppeteer pitfalls (read before touching PDFs):**
1. Header/footer render in **isolated HTML** — no inherited CSS/resources. Always include a full reset (`*{margin:0;padding:0;box-sizing:border-box}html,body{width:100%;height:100%;font-size:0;line-height:0;overflow:hidden}`).
2. **base64 images may not render** in header/footer — preload by adding invisible `<img>` with the same `src` in the Blade layouts (`pdf-alfa-eja*.blade.php`).
3. Header off the top: use `position:absolute;top:0`, not `margin:0` (Chrome print has default spacing).
4. Footer misaligned: body `display:flex` is unreliable (isolated body width ≠ page); use `position:absolute;bottom:0;left:50%;transform:translateX(-50%)`.
5. **`position:fixed` does NOT repeat per page** in Chrome PDF (unlike wkhtmltopdf) and adds a blank trailing page — use the native `headerHtml`/`footerHtml` templates via the macro.
6. Legacy `<div class="footer">` from migrated wkhtmltopdf views becomes body content → extra pages. Grep & remove when migrating.
7. After editing PDF layouts: `php artisan view:clear`.
8. CSS heights don't inherit page height in Puppeteer — center vertically with explicit `height: XXmm` (A4 landscape, margins 35/25 → 150mm available).

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/breeze (BREEZE) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
