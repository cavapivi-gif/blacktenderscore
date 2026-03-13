# Technical Audit Report — BlackTender Score

**Date:** 2026-03-13
**Scope:** Full codebase (excluding `exports/` third-party vendor files)
**Files audited:** 80+ source files across PHP, JSX, JS, CSS
**Agents used:** 7 parallel audit agents covering core, Elementor, admin PHP, React, front-end, dead code, and exports

---

## Table of Contents

1. [Critical Issues (12)](#1-critical-issues)
2. [Major Issues (22)](#2-major-issues)
3. [Minor Issues (35)](#3-minor-issues)
4. [Refactoring Opportunities (14)](#4-refactoring-opportunities)
5. [Dead Code & Unused Assets](#5-dead-code--unused-assets)
6. [Architecture & File Structure](#6-architecture--file-structure)
7. [Package & Dependency Review](#7-package--dependency-review)
8. [Final Summary](#8-final-summary)

---

## 1. Critical Issues

### 🔴 C01 — `admin/settings/class-settings.php:58-61` — CSRF vulnerability: cache flush without nonce verification

> The "Flush Cache" form is a plain `<form method="post">` with **no nonce field**. The handler checks `isset($_POST['bt_flush_cache'])` without verifying a nonce. Any page that can submit a POST to this URL can trigger a cache flush.
>
> **Fix:** Add `wp_nonce_field('bt_flush_cache')` to the form and `check_admin_referer('bt_flush_cache')` in the handler.

### 🔴 C02 — `admin/backoffice/class-backoffice.php:26-28` — Admin page accessible to ALL logged-in users

> The menu page uses `'read'` as the capability, meaning **every authenticated user including Subscribers** can access the backoffice. The React app, REST URL, nonce, and user details are exposed. While REST endpoints have their own permission callbacks, the UI shell loads for everyone.
>
> **Fix:** Use a custom capability or at minimum `'edit_posts'`. Check `bt_role_permissions` for the `'plugin'` cap in `render()` before outputting the React root div.

### 🔴 C03 — `admin/backoffice/class-rest-api.php:379` — Chat routes allow ANY logged-in user

> Chat endpoints (`/chats`, `/users/search`) only require `is_user_logged_in()`, not any BT role permission. The `search_users` endpoint lets any logged-in user search **all WordPress users by name/email** — information disclosure on multi-user sites.
>
> **Fix:** Add `$perm('chat_access')` to chat endpoints and `$perm('chat_share')` to user search.

### 🔴 C04 — `admin/backoffice/class-reservation-db.php:103-109` + `class-participation-db.php:81-88` — SQL escaping bypasses `$wpdb->prepare()`

> Both files define a `$q()` closure that calls `mysqli_real_escape_string()` directly, falling back to **`addslashes()`** (not safe against multi-byte charset attacks). The rationale is that encrypted values contain `%` chars, but `$wpdb->prepare()` handles `%s` placeholders correctly — the `%` in bound values is not interpreted.
>
> **Fix:** Replace the `$q()` closure with `$wpdb->prepare()` using `%s` placeholders, or use `$wpdb->insert()`/`$wpdb->update()` row-by-row within a transaction.

### 🔴 C05 — `admin/backoffice/class-rest-api.php:459` — Fatal error: undefined class `Db`

> `get_bookings()` instantiates `$db = new Db()` which **does not exist** anywhere in the codebase. Calling the `/bookings` endpoint triggers a fatal error. Same issue in `trait-rest-sync.php:29,34`.
>
> **Fix:** Replace with `new ReservationDb()` or remove the dead endpoints.

### 🔴 C06 — `admin/backoffice/class-encryption.php:78` — Same key for encryption AND HMAC

> A single derived key is used for both AES-256-CBC encryption and HMAC-SHA256 blind indexing. If the HMAC is compromised, the encryption key is also compromised.
>
> **Fix:** Derive two separate keys:
> ```php
> $this->enc_key  = hash_hmac('sha256', 'encrypt', BT_ENCRYPTION_KEY, true);
> $this->hmac_key = hash_hmac('sha256', 'hmac',    BT_ENCRYPTION_KEY, true);
> ```

### 🔴 C07 — `admin/backoffice/class-ai.php:95-104` — AI API keys stored in plaintext

> All AI provider API keys (Anthropic, OpenAI, Gemini, Mistral, Grok, Meta) are stored as **plaintext in `wp_options`**. The `Encryption` class exists but is not used for these keys. Anyone with database read access can steal all keys.
>
> **Fix:** Encrypt API keys at rest using the `Encryption` class, or require them as constants in `wp-config.php`.

### 🔴 C08 — `admin/meta-box/meta-box.js:26-33` — XSS via unescaped API response in jQuery `.append()`

> Product name (`p.name`) and price from the Regiondo API response are interpolated directly into HTML via template literals without escaping. Same issue at line 72 with `label` in `addTicketRow()`.
>
> **Fix:** Escape all dynamic values: use `$('<span>').text(p.name)` or create DOM nodes instead of string interpolation.

### 🔴 C09 — `elementor/assets/bt-elementor.css:1081` — Undefined CSS variable `var(--var--beige-on-bg)`

> This variable is **never defined** anywhere. The double `--var--` prefix is likely a typo. Transport step blocks render with transparent background (unintended).
>
> **Fix:** Define the variable in `:root` or correct to the intended name with a fallback: `var(--beige-on-bg, #f5f0e6)`.

### 🔴 C10 — `src/pages/Products.jsx:150` — React Fragment without key in `.map()`

> Each row uses `<>` (Fragment) wrapping multiple `<tr>` elements, but the Fragment has no `key`. React requires keys on the outermost element in `.map()`.
>
> **Fix:** Replace `<>` with `<Fragment key={row.product_id}>`.

### 🔴 C11 — `src/lib/api.js:1` + `src/lib/chatApi.js:6` — Nonce captured at module load, never refreshed

> `const { nonce } = window.btBackoffice` runs once at import. WordPress nonces expire after ~24 hours. Users leaving the tab open get 403 errors with no recovery.
>
> **Fix:** Read `window.btBackoffice.nonce` at call time inside `apiFetch`, or add nonce refresh on 403.

### 🔴 C12 — `core/class-plugin.php:42-69` — Inline JavaScript without proper escaping

> `echo "<script>..."` with `$encoded` interpolated into JS context. While `wp_json_encode` is safe for JSON values, the surrounding script is string-concatenated. The `phpcs:ignore` comment acknowledges but doesn't fix the issue.
>
> **Fix:** Use `wp_add_inline_script()` attached to a registered script handle, or `wp_json_encode` with `JSON_HEX_TAG`.

---

## 2. Major Issues

### 🟠 M01 — `admin/settings/class-settings.php:50` — Wrong hook slug: settings CSS never loads

> `enqueue()` checks for `$hook !== 'settings_page_bt-regiondo-settings'` but the page is registered with slug `bt-settings`. The correct hook would be `settings_page_bt-settings`. The CSS file is **never enqueued**.

### 🟠 M02 — `admin/settings/class-settings.php:124` — Synchronous API call blocks page load

> `(new Client())->get_products('fr-FR')` runs synchronously during `render()`. If the Regiondo API is slow/down, the entire settings page hangs.
>
> **Fix:** Load via AJAX or cache the response.

### 🟠 M03 — `core/class-plugin.php:73-78` — Aggressive cache invalidation on every `save_post`

> Hooks into `save_post` for ALL post types and runs a `DELETE ... LIKE` query against the options table every time any post is saved. On sites with frequent saves (autosaves, WooCommerce orders), this creates unnecessary DB load.
>
> **Fix:** Restrict to specific post types relevant to maps.

### 🟠 M04 — `admin/backoffice/class-rest-api.php:496-506` — N+1 query for customer reviews

> For each customer returned, a `WP_Query` with `posts_per_page => -1` runs to count reviews. 50 customers = 50 separate unbounded queries.
>
> **Fix:** Use a single aggregated `$wpdb->get_var()` COUNT query.

### 🟠 M05 — `admin/backoffice/class-ai.php:34-35` — AJAX nonce uses wrong action

> Uses `wp_rest` nonce for a `wp_ajax_` handler — semantically incorrect and could break with WP changes.
>
> **Fix:** Create a dedicated nonce (e.g., `bt_ai_chat`).

### 🟠 M06 — `api/regiondo/class-client.php:412-436` — Raw cURL bypasses WP HTTP API

> Uses `curl_init()` directly, bypassing WordPress's proxy settings, SSL verification, and HTTP request filters.
>
> **Fix:** Use `wp_remote_request()`.

### 🟠 M07 — `trait-ai-streams.php` + `trait-ai-json.php` + `trait-rest-translator.php` — cURL handles never closed

> Every `curl_init()` never calls `curl_close()`. In streaming operations, this leaks resources.
>
> **Fix:** Add `curl_close($ch)` after every `curl_exec($ch)`.

### 🟠 M08 — `elementor/widgets/class-google-map.php:303` — Map popup allows `javascript:` URIs

> `wp_kses` allows `<a href>` but doesn't restrict protocols. An `href="javascript:..."` would execute.
>
> **Fix:** Add protocol restrictions: `'href' => ['http', 'https', 'mailto']`.

### 🟠 M09 — `elementor/loop-queries/class-loop-queries.php` — Missing transient invalidation

> `bt_exc_by_city_` and `bt_similar_exc_` transients are never invalidated on `save_post`. Stale "similar excursions" persist for up to 6 hours.
>
> **Fix:** Add `save_post_excursion` invalidation hooks.

### 🟠 M10 — `elementor/class-elementor-manager.php:54-89` — Map style section injected into ALL widgets

> The `section_bt_map_style` controls are added to every Elementor widget globally (text, headings, buttons, etc.).
>
> **Fix:** Filter to only inject into map-related widgets.

### 🟠 M11 — `elementor/assets/bt-gmaps-init.js:109-114` — Infinite `setInterval` if Google Maps fails

> Polls every 100ms indefinitely if the API never loads (ad blocker, missing key).
>
> **Fix:** Add a max attempt counter (e.g., stop after 300 iterations / 30 seconds).

### 🟠 M12 — `cssformyfront.css:1-383` — Every declaration uses `!important`

> 100+ `!important` declarations override a third-party widget. Completely unmaintainable.
>
> **Fix:** Use higher-specificity wrapper selectors where possible.

### 🟠 M13 — `cssformyfront.css:232-241` — Selector targets generated hash class `.re-ba-haatk2`

> Hash-based class from CSS-in-JS library. Will silently break on Regiondo widget updates.
>
> **Fix:** Target by structure instead, or document the fragility.

### 🟠 M14 — `src/pages/AIChat.jsx:~1274` — `full` variable scope issue in streaming catch block

> The `full` variable accumulating streamed text may not be accessible in the `catch` block, silently losing partial responses.
>
> **Fix:** Declare `let full = ''` before the `try` block.

### 🟠 M15 — `src/pages/AIChat.jsx:~1207-1219` — Missing useEffect dependencies

> `activeConv?.provider` and `loadRemoteMessages` are used but not in the dependency array.
>
> **Fix:** Add them, or wrap `loadRemoteMessages` in `useCallback`.

### 🟠 M16 — `src/pages/AIChat.jsx` — `startResize` callback recreated on every mouse move

> `useCallback` includes `sidebarWidth` in deps, which changes on every drag frame.
>
> **Fix:** Use a ref for `sidebarWidth` inside the resize handler.

### 🟠 M17 — `src/hooks/useConversations.js:174-189` — `grouped` computed every render without useMemo

> Conversations grouped by date (today, yesterday, etc.) perform string parsing and date comparisons on every render.
>
> **Fix:** Wrap in `useMemo([conversations])`.

### 🟠 M18 — `vite.config.js:11` — Fixed output filenames without content hashing

> `entryFileNames: 'assets/index.js'` produces cache-hostile filenames. After deployment, browsers serve stale bundles.
>
> **Fix:** Use `assets/index.[hash].js` or versioned query strings.

### 🟠 M19 — `src/lib/api.js` + `src/lib/chatApi.js` — Duplicate `apiFetch()` implementations

> Two separate REST client implementations with slightly different error formatting.
>
> **Fix:** Consolidate into a single `apiFetch`.

### 🟠 M20 — `src/components/ui.jsx` + `src/lib/utils.js` — Duplicate `cn()` utility

> `cn()` (classNames merge) defined in both files. Components import from different sources.
>
> **Fix:** Keep only in `lib/utils.js`.

### 🟠 M21 — `package.json` — `@lobehub/ui` in dependencies but externalized in Vite

> Listed as a dependency but excluded from the bundle via `rollupOptions.external`. If not loaded at runtime by WordPress, the app crashes.
>
> **Fix:** Either bundle it or move to `peerDependencies` and document the runtime requirement.

### 🟠 M22 — `admin/backoffice/class-rest-api.php:873-929` — Dead code: `share_chat()` / `handle_shared_chat()`

> Methods defined but never registered as REST routes. Use `wp_options` for storage (anti-pattern) and `md5(uniqid())` for tokens (predictable).
>
> **Fix:** Remove entirely.

---

## 3. Minor Issues

### 🟡 PHP Backend

| # | Location | Issue |
|---|----------|-------|
| m01 | `blacktenders.php:10` | "to be waited" comment in plugin header |
| m02 | `blacktenders.php:22-26` | No `register_activation_hook` for table creation |
| m03 | `core/class-loader.php:8-28` | Autoloader doesn't handle `trait-*.php` files |
| m04 | `core/class-plugin.php:75-77` | Raw SQL with LIKE pattern not using `$wpdb->prepare()` |
| m05 | `admin/settings/class-settings.php:130` | Product ID output without `esc_attr()` |
| m06 | `admin/meta-box/class-meta-box.php:84` | Typo: `btRegionado` vs `btRegiondo` in localized JS variable |
| m07 | `admin/backoffice/class-backoffice.php:80-85` | User email and roles exposed in localized data |
| m08 | `class-encryption.php:56` | Silent fallback returns corrupted ciphertext as-is |
| m09 | `class-sync.php:82` | `bt_synced_products` in `wp_options` can grow unbounded |
| m10 | `class-reviews-db.php:43` | `tinyint(1)` confused with boolean for 1-5 ratings |
| m11 | `class-chat-db.php:217-219` | `sanitize_textarea_field()` strips HTML from AI messages |
| m12 | `class-events-db.php:28` | `CREATE TABLE IF NOT EXISTS` with dbDelta causes skipped updates |
| m13 | `trait-rest-settings.php:21` | `public_key` returned unmasked in GET /settings |
| m14 | `trait-rest-settings.php:110-114` | Custom JS stored with minimal sanitization |
| m15 | `trait-rest-translator.php:300-319` | Prompt injection blocklist easily bypassed |
| m16 | `trait-rest-stats.php:247-249` | `date('Y')` vs `date('o')` week numbering mismatch at year boundaries |
| m17 | `api/regiondo/class-auth.php:12-13` | API keys loaded from options on every instantiation |
| m18 | `api/regiondo/class-client.php:486-488` | API error responses silently returned as valid data |
| m19 | `uninstall.php:5-8` | Only 4 options cleaned up; dozens of options + custom tables left behind |

### 🟡 Elementor

| # | Location | Issue |
|---|----------|-------|
| m20 | `class-elementor-manager.php:118` | Google Maps API enqueued with `null` version parameter |
| m21 | `class-gallery.php:340` | Zoom control shows "px" unit but value is percentage |
| m22 | `class-excursion-schema.php:340` | `date('Y-12-31')` — correct by accident, fragile |
| m23 | `bt-elementor.js:161` | `resize` event listener without debounce |
| m24 | `bt-elementor.js:269-271` | `hashchange` listeners accumulate, never removed |
| m25 | `bt-elementor.js:531-538` | Deprecated `document.execCommand('copy')` fallback |
| m26 | `bt-gmaps-init.js:55` | Deprecated `google.maps.Marker` API |
| m27 | `bt-elementor.css:345,349` | `!important` on pricing panel display toggle |
| m28 | Widgets | Inconsistent ACF field control types (SELECT vs TEXT) |
| m29 | Widgets | CSS class prefix inconsistency (`bt-bspecs__` vs `bt-boat-specs__`) |

### 🟡 React Frontend

| # | Location | Issue |
|---|----------|-------|
| m30 | `ChatSharePanel.jsx:29` | `loadShares` not in useEffect deps |
| m31 | `UserSearchInput.jsx:38` | `excludeIds.join(',')` as useEffect dep — fires unnecessarily |
| m32 | `Planner.jsx:236` | `fmtDayShort` in useMemo deps — new ref every render |
| m33 | `EventsCorrelator.jsx:320` | eslint-disable on react-hooks/exhaustive-deps |
| m34 | `ReviewsImporter.jsx:305-307` | `resetAvis()` has no confirmation dialog for destructive action |
| m35 | `KpiCard.jsx:50-53` | Dynamic Tailwind classes won't be statically extracted |

---

## 4. Refactoring Opportunities

### 🔵 Files to Split

| Current File | Lines | Proposed Split |
|-------------|-------|----------------|
| `class-reservation-db.php` | 1308 | → `ReservationDb` (CRUD) + `ReservationImport` + `ReservationStats` + `ReservationValidator` |
| `class-rest-api.php` | 900+ | God class with 7 traits, 50+ routes → split into domain-specific REST controllers |
| `Settings.jsx` | 1611 | → `SettingsPage` + `GeneralSettings` + `AISettings` + `ImportSettings` + `useSettings` |
| `AIChat.jsx` | 1608 | → `AIChatPage` + `ChatMessageList` + `ChatInput` + `useAIChat` + `useStreamingResponse` + `Markdown` + `useTypewriter` |
| `Reviews.jsx` | 1177 | → `ReviewsPage` + `ReviewsTable` + `ReviewFilters` + `useReviews` |
| `bt-elementor.css` | 2287 | → `bt-elementor-base.css` + per-widget CSS files |
| `class-itinerary.php` | 1117 | → extract render logic into shortcode class |

### 🔵 Code Quality

| # | Issue | Fix |
|---|-------|-----|
| R01 | Duplicated `google_access_token()` in `AiPrompt` + `RestApiGoogle` traits | Extract into shared `GoogleAuth` class |
| R02 | Duplicated system prompts in `trait-rest-ai.php` + `trait-ai-prompt.php` | Extract into shared template |
| R03 | `$selectors`/`sel()` system in `SjWidgetBase` never used by any widget | Remove or migrate widgets to use it |
| R04 | `share.php:253` — `$prompt` variable assigned but never used | Delete dead variable |
| R05 | `pricing-tabs.php:587` — `$style_id` assigned but never used | Delete dead variable |
| R06 | `bt-elementor.js:76-78` — Hardcoded widget class toggles | Use `data-bt-tab-class` attribute pattern |
| R07 | `Settings.jsx:501-504` — `daysAgo()` and `fmtYMD()` duplicated from `lib/utils.js` | Import from utils |

---

## 5. Dead Code & Unused Assets

### Confirmed Dead Code

| Item | Location | Action |
|------|----------|--------|
| `exports/` directory | `./exports/` | 17MB / 1,660 files of Rey theme — **zero references** from plugin | DELETE |
| `Calendar.jsx` | `src/components/` | Never imported anywhere | DELETE |
| `BookingHoursChart.jsx` | `src/components/dashboard/` | Never imported or exported | DELETE |
| `share_chat()` + `handle_shared_chat()` | `class-rest-api.php:873-929` | Never registered as routes | DELETE |
| `$prompt` variable | `class-share.php:253` | Assigned, never used | DELETE |
| `$style_id` variable | `class-pricing-tabs.php:587` | Assigned, never used | DELETE |
| `abortRef` | `useTranslator.js:30` | Declared, never wired | DELETE or wire up |
| `delta` alias | `lib/utils.js` | Deprecated alias for `calcDelta` | Verify usage, then DELETE |
| `isLast` prop | `Sidebar.jsx:71` | Passed to `SubItem` but never used | DELETE |
| `settings.css` + `settings.js` | `admin/settings/` | Both files are empty | DELETE |
| `Db` class references | `trait-rest-sync.php`, `class-rest-api.php` | References non-existent class → fatal errors | FIX or DELETE |

### Stale Assets

| Item | Location | Action |
|------|----------|--------|
| Build output | `admin/backoffice/build/` | Committed to repo; missing `grok-color.svg` | Rebuild or add to `.gitignore` |
| Marker images | `elementor/assets/marker-*.png` | Not explicitly referenced but likely used by Leaflet convention | KEEP, verify |
| Logo JSON duplicates | `admin/backoffice/assets/` vs `src/assets/lottie/` | `assets/` dir is stale build output | Rebuild |

### CLAUDE.md Accuracy Issue

> CLAUDE.md describes an `includes/helpers.php` file with 6 functions (`sj_get_reviews`, `sj_stars_html`, `sj_relative_date`, etc.) that **do not exist in this codebase**. The doc references "SJ Reviews" architecture but the actual plugin is "BlackTender". The CLAUDE.md is outdated/aspirational and needs updating.

---

## 6. Architecture & File Structure

### Structural Issues

1. **No autoloading** — All classes manually `require_once`'d. Autoloader exists but doesn't handle traits.
2. **No tests** — Zero PHPUnit, Jest, or integration tests in the entire codebase.
3. **No Composer** — No PHP dependency management.
4. **No linting config** — No `.phpcs.xml`, `.eslintrc` (beyond Vite defaults), or CI pipeline.
5. **200K lines of vendor code** — Rey theme committed directly to repo.
6. **Build artifacts in VCS** — `admin/backoffice/build/` tracked in git.
7. **God classes** — `RestApi` (7 traits, 50+ routes), `ReservationDb` (1308 lines).

### Recommended Structure

```
blacktenderscore/
├── core/
│   ├── class-plugin.php              # Add PSR-4 autoloader
│   └── class-loader.php              # Fix trait file handling
├── elementor/
│   ├── class-abstract-bt-widget.php  # Keep
│   ├── traits/                       # Keep
│   ├── widgets/                      # Split itinerary; remove dead vars
│   └── assets/                       # Split monolithic CSS
├── admin/
│   ├── backoffice/
│   │   ├── class-rest-api.php        # Split into domain controllers
│   │   ├── class-reservation-db.php  # Split into 4 classes
│   │   └── src/
│   │       ├── pages/                # Split 3 monoliths → ~15 files
│   │       ├── components/
│   │       ├── hooks/                # Extract from pages
│   │       └── lib/                  # Consolidate api.js + chatApi.js
│   ├── settings/                     # Fix nonce, hook slug
│   └── meta-box/                     # Fix XSS
├── api/regiondo/                     # Migrate to wp_remote_request
├── tests/                            # NEW: PHPUnit + Jest
├── .gitignore                        # Add exports/, build/
└── CLAUDE.md                         # Update to match actual codebase
```

---

## 7. Package & Dependency Review

### npm (`admin/backoffice/package.json`)

| Package | Concern | Recommendation |
|---------|---------|----------------|
| `@lobehub/ui` | Externalized but in deps — will crash if not runtime-loaded | Bundle it or move to peerDeps |
| `antd`, `rc-util`, `rc-motion`, `@ant-design/icons` | Externalized peer deps not in package.json | Document runtime requirements |
| `lottie-react` | Heavy for 3 AI loading animations | Lazy-load with `React.lazy` or replace with SVGs |
| 7x `@fullcalendar/*` | Significant bundle weight | Lazy-load with `React.lazy` |
| `motion` (framer-motion fork) | Heavy animation lib | Evaluate if needed beyond simple transitions |
| `recharts` | Large charting library | Acceptable for analytics-heavy app |
| `@tanstack/react-table` | Good choice | KEEP |
| `date-fns` | Good lightweight date lib | KEEP |

### PHP

| Concern | Details |
|---------|---------|
| No Composer | No dependency management |
| Raw cURL | `class-client.php` bypasses `wp_remote_request()` |
| No PHPCS | No coding standards enforcement |

---

## 8. Final Summary

### Issue Counts by Severity

| Severity | Count |
|----------|-------|
| 🔴 CRITICAL | 12 |
| 🟠 MAJOR | 22 |
| 🟡 MINOR | 35 |
| 🔵 REFACTOR | 14 |
| **Total** | **83** |

### Top 5 Issues to Fix Immediately

| # | Issue | Impact |
|---|-------|--------|
| 1 | **C01 — CSRF on cache flush** (no nonce) | Any page can trigger admin actions |
| 2 | **C04 — SQL escaping with `addslashes()` fallback** | Database compromise on non-MySQL drivers |
| 3 | **C05 — Fatal error: undefined `Db` class** | `/bookings` endpoint crashes the site |
| 4 | **C02+C03 — Admin page + chat routes open to all users** | Information disclosure, unauthorized access |
| 5 | **C11 — Stale nonce after 24h** | All API calls fail for long-lived sessions |

### Files to Split (7 files → ~35 files)

| Current File | Lines | Split Into |
|-------------|-------|------------|
| `class-reservation-db.php` | 1308 | 4 classes |
| `class-rest-api.php` | 900+ | Domain-specific controllers |
| `Settings.jsx` | 1611 | 5 modules |
| `AIChat.jsx` | 1608 | 7 modules |
| `Reviews.jsx` | 1177 | 4 modules |
| `bt-elementor.css` | 2287 | Base + per-widget files |
| `class-itinerary.php` | 1117 | Widget + shortcode |

### Dead Code to Delete

| Item | Lines/Size |
|------|------------|
| `exports/` directory | 17MB, 1,660 files |
| `Calendar.jsx` | unused component |
| `BookingHoursChart.jsx` | unused component |
| `share_chat()` + `handle_shared_chat()` | ~60 lines |
| `settings.css` + `settings.js` | empty files |
| Dead variables (`$prompt`, `$style_id`, `abortRef`, `isLast`) | ~10 lines |

### Verdict

## 🚫 Do Not Merge — Critical Security & Stability Issues

**12 critical issues** including:
- CSRF vulnerability (no nonce on form)
- SQL injection via `addslashes()` fallback
- Fatal error on `/bookings` endpoint (undefined class)
- Admin pages accessible to subscribers
- XSS vectors in meta-box and map popups
- API keys stored in plaintext
- Stale nonces breaking sessions after 24h

**Additionally:**
- Zero tests across the entire codebase
- 7 monolithic files >1000 lines each
- 17MB of unrelated third-party code in repo
- Outdated CLAUDE.md describes non-existent architecture

**Priority remediation order:**
1. Fix CSRF + permission callbacks + nonce handling (security)
2. Fix fatal error: replace `Db` references (stability)
3. Replace `addslashes()` with `$wpdb->prepare()` (security)
4. Encrypt API keys at rest + separate encryption/HMAC keys (security)
5. Fix XSS in meta-box.js and map popups (security)
6. Add content hashing to Vite output (caching)
7. Delete `exports/` directory + dead code (repo hygiene)
8. Split monolithic files (maintainability)
9. Add basic test infrastructure (quality)
10. Update CLAUDE.md to match actual codebase (documentation)
