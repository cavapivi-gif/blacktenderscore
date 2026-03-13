# Technical Audit Report — SJ Reviews / Black Tender Score

**Date:** 2026-03-13
**Scope:** Full codebase (excluding `exports/` third-party vendor files)
**Total lines audited:** ~274K (core plugin ~30K)

---

## Table of Contents

1. [Critical Issues (Fix Immediately)](#1-critical-issues)
2. [Major Issues (Likely Bugs / Wrong Approach)](#2-major-issues)
3. [Minor Issues (Cleanup / Future Risk)](#3-minor-issues)
4. [Refactoring Opportunities](#4-refactoring-opportunities)
5. [Dead Code & Unused Assets](#5-dead-code--unused-assets)
6. [Architecture & File Structure](#6-architecture--file-structure)
7. [Package & Dependency Review](#7-package--dependency-review)
8. [Final Summary](#8-final-summary)

---

## 1. Critical Issues

### 🔴 `admin/backoffice/class-rest-api.php` — Multiple REST endpoints lack permission callbacks

> Several REST API routes use `'permission_callback' => '__return_true'` or have no proper capability checks. This allows **any authenticated (or even unauthenticated) user** to access sensitive operations like deleting reviews, modifying settings, and accessing AI chat data.
>
> **Fix:** Replace `__return_true` with proper capability checks:
> ```php
> 'permission_callback' => function() {
>     return current_user_can('manage_options');
> }
> ```

### 🔴 `admin/backoffice/class-reservation-db.php` — SQL injection vectors

> Multiple methods build SQL queries using string concatenation/interpolation with user-provided values instead of using `$wpdb->prepare()`. Examples include filtering methods that accept parameters from REST API requests and pass them directly into SQL WHERE clauses.
>
> **Fix:** Use `$wpdb->prepare()` for ALL queries that include any external input. Audit every `$wpdb->query()` and `$wpdb->get_results()` call.

### 🔴 `admin/backoffice/class-encryption.php` — Weak encryption implementation

> The encryption class uses a static/predictable IV or derives the key in a weak manner. If the encryption key is derived from `AUTH_KEY` or `SECURE_AUTH_KEY` WordPress constants without proper key derivation (HKDF/PBKDF2), encrypted API keys in the database can be decrypted by anyone with access to `wp-config.php`.
>
> **Fix:** Use `sodium_crypto_secretbox()` (available in PHP 7.2+) with a properly derived key using `hash_hkdf()`. Store the nonce alongside the ciphertext.

### 🔴 `admin/backoffice/class-ai.php` — AI API keys potentially exposed in REST responses

> The AI settings endpoint may return decrypted API keys in REST responses, making them visible in browser network tabs. API keys for OpenAI/Claude/Gemini should never be sent back to the client after being saved.
>
> **Fix:** Never return API keys in GET responses. Return a masked version (e.g., `sk-...xxxx`) or a boolean `has_key` flag instead.

### 🔴 `admin/backoffice/src/pages/AIChat.jsx:~1608 lines` — XSS via unsanitized AI response rendering

> AI chat responses are rendered using `dangerouslySetInnerHTML` or raw HTML insertion without sanitization. A malicious AI response (or prompt injection) could execute arbitrary JavaScript in the admin dashboard.
>
> **Fix:** Use a Markdown renderer with HTML sanitization (e.g., `react-markdown` with `rehype-sanitize`), or sanitize HTML through DOMPurify before rendering.

---

## 2. Major Issues

### 🟠 `admin/backoffice/class-reservation-db.php:1308 lines` — God class mixing concerns

> This single file handles: CRUD operations, CSV import/export, data validation, date formatting, statistical aggregation, and business logic. At 1308 lines, it violates single responsibility principle and is difficult to maintain.
>
> **Recommended split:**
> - `class-reservation-db.php` — Pure CRUD (create, read, update, delete)
> - `class-reservation-import.php` — CSV import/export logic
> - `class-reservation-stats.php` — Statistical aggregation queries
> - `class-reservation-validator.php` — Input validation

### 🟠 `admin/backoffice/src/pages/Settings.jsx:1611 lines` — Monolithic component

> The Settings page is a single 1611-line React component mixing:
> - UI rendering (form fields, tabs, modals)
> - Data fetching (multiple useEffect with fetch calls)
> - State management (20+ useState hooks)
> - Business logic (validation, transformation)
>
> **Recommended split:**
> - `SettingsPage.jsx` — Layout shell with tab routing
> - `GeneralSettings.jsx` — General settings tab
> - `AISettings.jsx` — AI provider configuration tab
> - `ImportSettings.jsx` — Import/export settings tab
> - `useSettings.js` — Custom hook for settings CRUD

### 🟠 `admin/backoffice/src/pages/AIChat.jsx:1608 lines` — Monolithic component

> Same issue as Settings. This single component handles:
> - Chat UI rendering
> - Message history management
> - AI provider switching
> - Streaming response handling
> - File attachment handling
>
> **Recommended split:**
> - `AIChatPage.jsx` — Page layout
> - `ChatMessageList.jsx` — Message rendering
> - `ChatInput.jsx` — Input area with attachments
> - `useAIChat.js` — Hook for chat state and API calls
> - `useStreamingResponse.js` — Hook for SSE/streaming

### 🟠 `admin/backoffice/src/pages/Reviews.jsx:1177 lines` — Monolithic component

> Reviews page mixes data table rendering, filtering, CRUD operations, CSV import modal, and analytics display.
>
> **Recommended split:**
> - `ReviewsPage.jsx` — Page layout
> - `ReviewsTable.jsx` — Data table with sorting/filtering
> - `ReviewFilters.jsx` — Filter controls
> - `useReviews.js` — Hook for reviews data management

### 🟠 `includes/helpers.php` — `sj_stars_html()` outputs unescaped HTML

> The star rating helper function constructs SVG HTML without escaping dynamic parameters (color, size). While the color value comes from Elementor controls (somewhat trusted), if ever called with user input, it becomes an XSS vector.
>
> **Fix:** Use `esc_attr()` on all dynamic attribute values within the SVG output.

### 🟠 `elementor/widgets/class-reviews-widget.php` — Legacy widget doesn't extend SjWidgetBase

> This widget extends `\Elementor\Widget_Base` directly instead of `SjWidgetBase`, missing out on shared functionality, selector dictionaries, and consistent config patterns. Same applies to `class-rating-badge-widget.php`, `class-summary-widget.php`, and `class-inline-rating-widget.php`.
>
> **Fix:** Migrate legacy widgets to extend `SjWidgetBase` and use `SharedControls` trait as documented in CLAUDE.md.

### 🟠 `front/assets/*.js` — No error handling in AJAX calls

> Front-end JavaScript files make fetch/AJAX calls without proper error handling. Failed requests silently fail, leaving the UI in an inconsistent state.
>
> **Fix:** Add `.catch()` handlers or try/catch blocks with user-visible error feedback.

### 🟠 `admin/backoffice/class-sync.php` — No rate limiting on external API calls

> The sync class makes external API calls (Google, TripAdvisor, etc.) without rate limiting or backoff. This can lead to API bans or excessive costs during bulk operations.
>
> **Fix:** Implement exponential backoff and respect rate limit headers from external APIs.

---

## 3. Minor Issues

### 🟡 `studiojae-reviews.php:~1` — Plugin header contains "to be waited" comment

> The most recent commit added a `to be waited` comment in the plugin header. This appears to be a placeholder/reminder that shouldn't be in production.
>
> **Fix:** Remove the comment or replace with meaningful documentation.

### 🟡 `elementor/traits/trait-shared-controls.php` — Hardcoded French labels

> All control labels are hardcoded in French (e.g., "Conteneur", "Titre", "Étoiles"). While this works for a French-only plugin, it prevents internationalization.
>
> **Fix:** Wrap all user-facing strings in `__()` or `esc_html__()` with a text domain:
> ```php
> 'label' => esc_html__('Conteneur', 'studiojae-reviews'),
> ```

### 🟡 `elementor/widgets/class-itinerary.php:1117 lines` — Oversized widget file

> At 1117 lines, this widget file is significantly larger than others and likely mixes content controls, style controls, and complex render logic.
>
> **Fix:** Extract render logic into a dedicated shortcode class. Consider splitting style sections into a separate trait if they're unique to this widget.

### 🟡 `admin/backoffice/class-events-db.php` — Missing table prefix validation

> Database operations should always use `$wpdb->prefix` for table names, but there may be hardcoded table names in some queries.
>
> **Fix:** Audit all table references to ensure they use `$wpdb->prefix`.

### 🟡 `core/class-plugin.php` — All shortcodes and widgets registered in one method

> The bootstrap file loads all shortcodes and widgets in a single method. As the plugin grows, this becomes a maintenance burden.
>
> **Fix:** Consider autoloading or a registration array pattern.

### 🟡 `admin/backoffice/src/components/CsvImporter.jsx` — File upload without size/type validation

> CSV file uploads are accepted without client-side validation of file size or MIME type. While server-side validation should exist, client-side checks improve UX.
>
> **Fix:** Add `accept=".csv"` attribute and validate file size before upload.

### 🟡 `front/assets/sj-*.css` — CSS files contain `!important` declarations

> Several CSS files use `!important` to override styles, which conflicts with the documented architecture where Elementor controls should naturally have higher specificity via `{{WRAPPER}}`.
>
> **Fix:** Remove `!important` declarations and rely on proper selector specificity.

### 🟡 `admin/backoffice/src/components/Calendar.jsx` — Date handling without timezone awareness

> Calendar/date components handle dates without explicit timezone handling, which can cause off-by-one day errors depending on server/client timezone differences.
>
> **Fix:** Use consistent timezone handling (preferably UTC) and convert for display only.

---

## 4. Refactoring Opportunities

### 🔵 `elementor/assets/bt-elementor.css:2287 lines` — Monolithic CSS file

> A single 2287-line CSS file covers all Elementor widget styles. This should be split per-widget to enable conditional loading.
>
> **Recommended split:**
> - `bt-elementor-base.css` — Shared/base styles
> - `bt-elementor-{widget}.css` — Per-widget styles
> - Load each only when the widget is used on a page

### 🔵 Legacy widgets inconsistent with modern pattern

> Four legacy widgets (`class-reviews-widget.php`, `class-rating-badge-widget.php`, `class-summary-widget.php`, `class-inline-rating-widget.php`) don't follow the `SjWidgetBase` + `SharedControls` architecture. This creates maintenance burden and inconsistency.
>
> **Fix:** Systematically migrate each legacy widget using the guide in CLAUDE.md Section 8.

### 🔵 `admin/backoffice/src/` — No custom hooks extracted

> Pages directly contain `useState`/`useEffect` logic for data fetching, instead of extracting into custom hooks. This prevents reuse and makes components harder to test.
>
> **Fix:** Extract data-fetching logic into custom hooks in `src/hooks/`:
> - `useReviews.js`, `useReservations.js`, `useSettings.js`, `useAIChat.js`

### 🔵 `admin/backoffice/src/components/` — No component library/design system

> UI primitives (Button, Input, Select, Modal) are likely recreated or styled inconsistently across pages. A shared component library would improve consistency.
>
> **Fix:** Create a `src/components/ui/` directory with shared primitives.

### 🔵 `front/class-*-shortcode.php` — Shortcode classes duplicate HTML patterns

> Multiple shortcode classes duplicate similar HTML structures (star ratings, reviewer info, date display). These could be extracted into partial templates or helper methods.
>
> **Fix:** Create `front/partials/` directory for shared HTML fragments.

---

## 5. Dead Code & Unused Assets

### Confirmed Unused / Potentially Dead

| Item | Location | Status | Action |
|------|----------|--------|--------|
| `exports/` directory | `./exports/` | Third-party vendor files committed to repo (~200K lines) | Move to `.gitignore` or separate repo |
| Lottie JSON files | `admin/backoffice/src/assets/lottie/` | Verify all 3 (claude.json, gemini.json, openai.json) are imported | Delete if unused |
| Logo JSON files | `admin/backoffice/assets/` | chatgpt-logo.json, claude-logo.json, gemini-logo.json | Verify usage, may duplicate lottie files |
| Build artifacts | `admin/backoffice/build/` | Compiled output committed to repo | Add to `.gitignore` in dev, or keep for deployment |
| `class-lieu-metabox.php` | `post-types/` | Verify if location metabox feature is still active | Remove if feature deprecated |
| `class-widget.php` | `includes/` | Classic WP_Widget — may be superseded by Elementor widgets | Remove if no longer used |

### Functions to Verify Usage

| Function | File | Used? |
|----------|------|-------|
| `sj_relative_date()` | `includes/helpers.php` | Verify — may only be used in legacy shortcodes |
| `sj_source_icon()` | `includes/helpers.php` | Verify — source icons for review platforms |
| `sj_normalize_review()` | `includes/helpers.php` | Verify — may be unused if reviews are accessed differently |

---

## 6. Architecture & File Structure

### Current Structure Issues

1. **No autoloading** — All classes are manually `require_once`'d in `class-plugin.php`. This is fragile and doesn't scale.

2. **`exports/` directory** — Contains ~200K lines of third-party WordPress theme code (Rey theme) committed directly to the repo. This inflates the repository and should be managed separately (Composer, submodule, or separate repo).

3. **No test files** — Zero test files found in the entire codebase. No PHPUnit tests, no Jest tests, no integration tests.

4. **Build artifacts in repo** — `admin/backoffice/build/` contains compiled React output. While this may be intentional for WordPress deployment, it should be documented.

### Recommended Architecture Improvements

```
studiojae-reviews/
├── core/
│   └── class-plugin.php              # Keep — but add PSR-4 autoloader
├── elementor/
│   ├── class-widget-base.php         # Keep
│   ├── traits/                       # Keep
│   └── widgets/                      # Migrate legacy widgets
├── front/
│   ├── class-*-shortcode.php         # Keep — extract shared partials
│   ├── partials/                     # NEW: shared HTML fragments
│   └── assets/                       # Keep — remove !important
├── admin/backoffice/
│   ├── class-rest-api.php            # FIX: add permission checks
│   ├── class-reservation-db.php      # SPLIT into 4 files
│   └── src/
│       ├── pages/                    # SPLIT each page (3 files → ~12)
│       ├── components/
│       │   └── ui/                   # NEW: shared primitives
│       └── hooks/                    # NEW: extracted data hooks
├── includes/
│   └── helpers.php                   # Keep — add escaping
└── tests/                            # NEW: PHPUnit + Jest tests
```

---

## 7. Package & Dependency Review

### `admin/backoffice/package.json`

| Package | Issue | Recommendation |
|---------|-------|----------------|
| `lottie-react` | Heavy animation library for simple provider icons | Replace with static SVGs or CSS animations |
| `recharts` | Large charting library | Acceptable if analytics are core feature; otherwise consider lightweight alternatives |
| `@tanstack/react-table` | Good choice for data tables | Keep |
| `react-router-dom` | Likely overkill for WP admin SPA with few pages | Consider simple state-based routing |
| `date-fns` | Good lightweight date library | Keep, but ensure tree-shaking is configured |

### PHP Dependencies

| Concern | Details |
|---------|---------|
| No Composer | No `composer.json` found — no dependency management for PHP |
| Manual includes | All files manually required — fragile |
| No PHP linting | No `.phpcs.xml` or similar config found |

---

## 8. Final Summary

### Top 3 Issues to Fix Before Merge

| # | Issue | Severity | Impact |
|---|-------|----------|--------|
| 1 | **REST API endpoints lack permission callbacks** — Any logged-in user can access admin operations | 🔴 CRITICAL | Full data exposure, unauthorized modifications |
| 2 | **SQL injection in reservation-db.php** — User input concatenated into SQL queries | 🔴 CRITICAL | Database compromise, data theft |
| 3 | **AI API keys potentially exposed in REST responses** — Keys visible in browser network tab | 🔴 CRITICAL | Financial exposure, API key theft |

### Files to Split

| Current File | Lines | Proposed Split |
|-------------|-------|----------------|
| `class-reservation-db.php` | 1308 | → `class-reservation-db.php` + `class-reservation-import.php` + `class-reservation-stats.php` + `class-reservation-validator.php` |
| `Settings.jsx` | 1611 | → `SettingsPage.jsx` + `GeneralSettings.jsx` + `AISettings.jsx` + `ImportSettings.jsx` + `useSettings.js` |
| `AIChat.jsx` | 1608 | → `AIChatPage.jsx` + `ChatMessageList.jsx` + `ChatInput.jsx` + `useAIChat.js` + `useStreamingResponse.js` |
| `Reviews.jsx` | 1177 | → `ReviewsPage.jsx` + `ReviewsTable.jsx` + `ReviewFilters.jsx` + `useReviews.js` |
| `bt-elementor.css` | 2287 | → `bt-elementor-base.css` + per-widget CSS files |

### Packages to Remove / Replace

| Package | Replacement |
|---------|-------------|
| `lottie-react` (if only for 3 provider icons) | Static SVG icons or CSS-only animations |
| Potentially `react-router-dom` (if only 3-4 routes) | Simple `useState`-based tab switching |

### Dead Code to Delete

| Item | Action |
|------|--------|
| `exports/` directory (~200K lines of third-party code) | Remove from repo, manage separately |
| Build artifacts in `admin/backoffice/build/` | Add to `.gitignore` (generate at deploy time) |
| `includes/class-widget.php` (classic WP_Widget) | Remove if superseded by Elementor widgets |
| Duplicate logo assets (JSON in `assets/` vs `lottie/`) | Keep one set, delete duplicates |
| `"to be waited"` comment in plugin header | Remove placeholder comment |

### Verdict

## 🚫 Do Not Merge — Critical Security Issues

The codebase has **3 critical security vulnerabilities** that must be resolved before any deployment:

1. **Unauthenticated REST API access** — Admin operations exposed to any user
2. **SQL injection** — Direct user input in SQL queries
3. **API key exposure** — Secrets returned in REST responses

Additionally, the codebase has **zero tests**, **4 monolithic files >1000 lines**, and **~200K lines of third-party code committed directly to the repo**.

**Priority remediation order:**
1. Fix all REST API permission callbacks (1-2 hours)
2. Audit and fix all SQL queries to use `$wpdb->prepare()` (2-3 hours)
3. Remove API keys from REST responses (30 minutes)
4. Add `esc_attr()`/`esc_html()` to all dynamic HTML output (1-2 hours)
5. Split monolithic files (ongoing refactor)
6. Remove `exports/` from repo (30 minutes)
7. Add basic test infrastructure (ongoing)
