# BlackTenders Core — Architecture Guide for AI Agents

> This file guides any AI (Claude, GPT, Copilot...) to build, maintain, and extend
> this WordPress plugin — Elementor widgets, Regiondo API integration, React backoffice.

---

## Table of Contents

| # | Section | When to jump here |
|---|---------|-------------------|
| 1 | [Git Workflow](#git-workflow) | Before any `git` operation |
| 2 | [Project Overview](#project-overview) | First time reading the project |
| 3 | [Directory Structure](#directory-structure) | Finding where a file lives |
| 4 | [How to Create a New Elementor Widget](#how-to-create-a-new-elementor-widget) | Adding a new widget |
| 5 | [SharedControls Trait](#sharedcontrols-trait--available-methods) | Adding style controls to a widget |
| 6 | [AbstractBtWidget — What It Provides](#abstractbtwidget--what-it-provides) | Understanding base class features |
| 7 | [Key Conventions](#key-conventions) | Naming, selectors, data flow |
| 8 | [Backoffice Architecture](#backoffice-architecture) | REST API, DB classes, React app |
| 9 | [Security Patterns](#security-patterns) | Encryption, SQL, XSS, CSRF |
| 10 | [Elementor Control Types — Pitfalls](#elementor-control-types--pitfalls--patterns) | ICONS bug, mode selector, CSS grid |

---

## Git Workflow

- ALWAYS commit directly to `main` (or the current active branch)
- NEVER create new branches under any circumstance
- NEVER use `git checkout -b`, `git switch -c`, or any branch creation command
- Commit directly: `git add` → `git commit` → done
- Do NOT open pull requests — push directly

---

## Project Overview

**Type:** WordPress plugin (Elementor + ACF + Regiondo API integration)
**Namespace:** `BlackTenders`
**Entry point:** `blacktenders.php` → `core/class-plugin.php`
**Text domain:** `blacktenderscore`
**Constants:** `BT_VERSION`, `BT_DIR`, `BT_URL`

**Key features:**
- 17 custom Elementor widgets (excursions, boats, galleries, maps, reviews)
- Regiondo booking API integration (sync, stats, planner)
- React admin dashboard (backoffice) with AI chat, analytics, customer CRM
- GDPR-compliant encrypted storage (AES-256-CBC with HMAC blind indexes)
- MCP server for AI tool integration

---

## Directory Structure

```
blacktenderscore/
├── blacktenders.php                   # Plugin bootstrap, defines BT_DIR/BT_URL
├── uninstall.php                      # Cleanup on plugin deletion
├── core/
│   ├── class-loader.php               # PSR-4 autoloader (CamelCase → kebab-case)
│   └── class-plugin.php               # Bootstraps REST API, Backoffice, Elementor, MetaBox
├── admin/
│   ├── backoffice/
│   │   ├── class-backoffice.php       # Admin menu registration + Vite asset enqueue
│   │   ├── class-rest-api.php         # Central REST controller (uses 7 traits below)
│   │   ├── trait-rest-stats.php       # Stats/charts endpoints
│   │   ├── trait-rest-sync.php        # Regiondo sync endpoints
│   │   ├── trait-rest-ai.php          # AI context/chat endpoints
│   │   ├── trait-rest-chat.php        # Chat CRUD endpoints
│   │   ├── trait-rest-settings.php    # Settings CRUD endpoints
│   │   ├── trait-rest-google.php      # Google Reviews endpoints
│   │   ├── trait-rest-translator.php  # Translation endpoints
│   │   ├── class-reservation-db.php   # DB CRUD for bt_reservations (schema, upsert, query)
│   │   ├── class-reservation-stats.php # Analytics queries (extends ReservationDb)
│   │   ├── class-reservation-sync.php # Maps Regiondo API → ReservationDb
│   │   ├── class-participation-db.php # DB for CSV-imported participations
│   │   ├── class-chat-db.php          # Chat/conversation storage
│   │   ├── class-events-db.php        # AI-generated events storage
│   │   ├── class-reviews-db.php       # Google reviews storage
│   │   ├── class-encryption.php       # AES-256-CBC + HMAC blind indexes
│   │   ├── class-ai.php              # SSE streaming for AI chat
│   │   ├── class-sync.php            # Regiondo product sync
│   │   ├── trait-ai-prompt.php       # System prompt builder
│   │   ├── trait-ai-streams.php      # SSE stream handling
│   │   ├── trait-ai-json.php         # JSON extraction from AI responses
│   │   ├── trait-ai-formatters.php   # Response formatting
│   │   └── src/                       # React app (Vite + Tailwind)
│   │       ├── main.jsx              # App entry point
│   │       ├── index.css             # Tailwind + custom styles
│   │       ├── pages/                # Route pages
│   │       │   ├── Settings.jsx      # Settings page (uses settings/ subdir)
│   │       │   ├── AIChat.jsx        # AI chat page (uses ai-chat/ subdir)
│   │       │   ├── Reviews.jsx       # Reviews page (uses reviews/ subdir)
│   │       │   ├── Dashboard.jsx     # Dashboard
│   │       │   ├── Planner.jsx       # Calendar planner (FullCalendar)
│   │       │   ├── Customers.jsx     # Customer CRM
│   │       │   └── ... (Bookings, Analytics, etc.)
│   │       └── components/           # Shared React components
│   ├── meta-box/
│   │   ├── class-meta-box.php        # Regiondo ticket meta box
│   │   ├── template.php              # Meta box HTML template
│   │   └── meta-box.js               # Meta box JS (product selection)
│   └── settings/
│       └── class-settings.php         # WP Settings API page
├── api/
│   └── regiondo/
│       ├── class-client.php           # HTTP client for Regiondo API
│       ├── class-auth.php             # API authentication
│       ├── class-cache.php            # Transient-based caching
│       └── api-map.php               # Endpoint mapping
├── elementor/
│   ├── class-abstract-bt-widget.php   # AbstractBtWidget — base for all widgets
│   ├── class-elementor-manager.php    # Widget/tag registration
│   ├── traits/
│   │   ├── class-bt-shared-controls.php  # Main SharedControls dispatcher
│   │   ├── trait-bt-typography-controls.php
│   │   ├── trait-bt-layout-controls.php
│   │   ├── trait-bt-heading-controls.php
│   │   ├── trait-bt-content-controls.php
│   │   └── trait-bt-nav-controls.php
│   ├── widgets/                       # 17 Elementor widgets
│   │   ├── class-google-map.php       # Google Maps (JS API + ACF integration)
│   │   ├── class-gallery.php          # Photo gallery
│   │   ├── class-itinerary.php        # Itinerary with Leaflet maps
│   │   ├── class-reviews.php          # Reviews display
│   │   ├── class-boat-pricing.php     # Pricing tables
│   │   └── ... (13 more widgets)
│   ├── dynamic-tags/                  # Elementor dynamic tags
│   ├── loop-queries/                  # Custom loop queries
│   └── assets/
│       ├── bt-gmaps-init.js           # Google Maps JS init
│       └── bt-leaflet-init.js         # Leaflet maps init
└── mcp/
    ├── index.js                       # MCP server for AI tools
    └── package.json
```

---

## Autoloader

`core/class-loader.php` implements PSR-4 autoloading:
- `BlackTenders\Admin\Backoffice\ReservationDb` → `admin/backoffice/class-reservation-db.php`
- CamelCase class names are converted to kebab-case file names
- All files must be prefixed with `class-` (or `trait-` for traits — loaded manually)

---

## How to Create a New Elementor Widget

### Step 1: Create the widget class

File: `elementor/widgets/class-{feature}.php`

```php
<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

class Feature extends AbstractBtWidget {
    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-feature',
            'title'    => 'BT — Feature Name',
            'icon'     => 'eicon-star',
            'keywords' => ['feature', 'bt'],
            'css'      => ['bt-feature'],   // optional style handle
            'js'       => ['bt-feature'],   // optional script handle
        ];
    }

    protected function register_controls(): void {
        // Content tab
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        // ... controls
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        // HTML output
    }
}
```

### Step 2: Register in ElementorManager

Widgets are auto-registered by `class-elementor-manager.php` which scans the `widgets/` directory.

---

## SharedControls Trait — Available Methods

Each method registers a complete Elementor SECTION with all related controls.

| Method | What it adds |
|--------|-------------|
| `register_typography_controls($prefix, $label, $selector)` | Font, size, weight, color, alignment, spacing |
| `register_box_controls($prefix, $label, $selector)` | Background, border, radius, padding, margin, shadow |
| `register_box_hover_controls($prefix, $label, $selector)` | Box with Normal/Hover tabs + transform |
| `register_button_controls($prefix, $label, $selector)` | Button with Normal/Hover tabs |
| `register_avatar_controls($prefix, $label, $selector)` | Image size, radius, object-fit, border |
| `register_stars_controls($prefix, $label, $selector)` | Star color, empty color, size, gap |
| `register_separator_controls($prefix, $label, $selector)` | Divider color, width, height |
| `register_layout_controls($prefix, $label, $selector)` | Flex gap, justify, align |
| `register_bar_controls($prefix, $label, $fill, $track)` | Progress bar fill/track |
| `register_pill_controls($prefix, $label, $sel, $active)` | Pill with Normal/Hover/Active tabs |

All methods accept an optional `$defaults` array.

---

## AbstractBtWidget — What It Provides

| Feature | How |
|---------|-----|
| `get_name()`, `get_title()`, `get_icon()` | Auto-derived from `get_bt_config()` |
| `get_style_depends()` / `get_script_depends()` | From config `css` / `js` arrays |

---

## Key Conventions

### Naming
- **Widget IDs:** `bt-{feature}` (e.g. `bt-google-map`, `bt-gallery`)
- **CSS classes:** `.bt-{feature}` with BEM: `__element`, `--modifier`
- **Control IDs:** `{prefix}_{property}` (e.g. `title_color`, `container_padding`)
- **PHP classes:** PascalCase (e.g. `GoogleMap`, `BoatPricing`)
- **Files:** `class-{feature}.php`, `bt-{feature}.css`

### CSS
- CSS provides LAYOUT defaults
- Elementor controls override via `{{WRAPPER}}` specificity
- No `!important`

---

## Backoffice Architecture

### REST API
Central controller: `class-rest-api.php` — uses 7 traits for different endpoint groups.
Namespace: `bt/v1`

Permission system: `bt_role_permissions` option + `ChatDb::role_has_permission()`.
Routes use `$perm('permission_key')` closures for role-based access.

### Database Classes
| Class | Table | Purpose |
|-------|-------|---------|
| `ReservationDb` | `bt_reservations` | Regiondo solditems CRUD |
| `ReservationStats` | (same table) | Analytics queries (extends ReservationDb) |
| `ParticipationDb` | `bt_participations` | CSV-imported participations |
| `ChatDb` | `bt_chats` | AI chat conversations |
| `EventsDb` | `bt_events` | AI-generated calendar events |
| `ReviewsDb` | `bt_reviews` | Google reviews |

### React App
Built with Vite + React + Tailwind CSS.
Source: `admin/backoffice/src/`
Build: `admin/backoffice/build/` (committed, required for plugin to work)

---

## Security Patterns

### SQL Injection Prevention
Always use `$wpdb->prepare()` with `%s`, `%d`, `%f` placeholders. Never interpolate user input.

### Encryption (GDPR)
`class-encryption.php` — AES-256-CBC with separate derived keys:
- `enc_key` = HMAC-SHA256("bt_encrypt", master_key) — for data encryption
- `hmac_key` = HMAC-SHA256("bt_hmac", master_key) — for blind index hashing
- Backward-compatible decryption with legacy single-key scheme

### XSS Prevention
- PHP: `esc_attr()`, `esc_html()`, `wp_kses()` on all output
- JS: DOM node creation (`textContent`) instead of `innerHTML` for user data
- `wp_kses` href protocols restricted to `['http', 'https', 'mailto']`

### CSRF
All admin forms use `wp_nonce_field()` + `check_admin_referer()`.

---

## Elementor Control Types — Pitfalls & Patterns

### ICONS control returns an array, not a string!
```php
$icon = $settings['my_icon'] ?? [];
if (is_array($icon) && !empty($icon['value'])) {
    Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
} elseif (is_string($icon) && $icon !== '') {
    echo esc_html($icon);  // legacy TEXT value
}
```

### "Can't unselect" — Mode selector pattern
Add a SELECT gate control before an ICONS control:
```php
$this->add_control('icon_mode', [
    'type'    => Controls_Manager::SELECT,
    'options' => ['icon' => 'Show icon', 'none' => 'Show nothing'],
    'default' => 'icon',
]);
$this->add_control('the_icon', [
    'type'      => Controls_Manager::ICONS,
    'condition' => ['icon_mode' => 'icon'],
]);
```

### CSS Grid: height vs aspect-ratio
Never set `height: 100%` on an element with `aspect-ratio`. Use absolute positioning:
```css
.grid-item { aspect-ratio: 4/3; position: relative; }
.grid-item__img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
```
