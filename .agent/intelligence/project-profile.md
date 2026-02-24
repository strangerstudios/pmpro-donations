# Project Intelligence Profile

**Analyzed:** 2026-02-23
**Status:** Analyzed

## Project Overview
PMPro Donations is a WordPress plugin add-on for Paid Memberships Pro (PMPro) that adds donation functionality to the membership checkout flow. Members can add a one-time donation on top of their membership fee, or donate without a membership via "donation-only" levels. The plugin supports predefined dropdown amounts, custom "other" amounts, min/max validation, and integration with multiple payment gateways.

- **Plugin Slug**: `pmpro-donations`
- **Current Version**: 2.2
- **License**: GPL-2.0+
- **Repository**: https://github.com/strangerstudios/pmpro-donations

## Technology Stack
- **Language(s):** PHP (primary), inline JavaScript (jQuery), HTML
- **Framework(s):** WordPress Plugin API, Paid Memberships Pro (PMPro) hooks/filters
- **Package Manager:** None (no composer.json or package.json)
- **Build Tool(s):** None — no build step required
- **Runtime Requirements:** WordPress 5.0+, PHP 7.4+ (tested up to PHP 8.5), Paid Memberships Pro core plugin

## Project Structure
```
pmpro-donations/
├── pmpro-donations.php           # Main plugin bootstrap (defines constants, requires includes, registers meta links)
├── includes/
│   ├── common.php                # Shared utilities: get_level_settings(), get_price_components(), is_donations_only()
│   ├── checkout.php              # Checkout flow: form rendering, validation, price modification, email integration
│   ├── donation-only-level.php   # Donation-only level logic: preserve member's existing level after donation checkout
│   ├── level-settings.php        # Admin UI: donation settings on level edit page (enable, min/max, dropdown, help text)
│   └── admin.php                 # Admin features: order editing donation field, CSV export column
├── languages/
│   ├── pmpro-donations.pot       # Translation template
│   ├── pmpro-donations.po        # Translation source
│   ├── pmpro-donations.mo        # Compiled translations
│   └── gettext.sh                # Translation extraction script
├── readme.txt                    # WordPress.org plugin readme (changelog, FAQ, description)
├── readme.md                     # GitHub readme (installation, contributing)
├── AGENTS.md                     # Comprehensive agent instructions (architecture, conventions, data model)
├── PLAN.md                       # v2.3 roadmap with 16 tickets across 4 phases
├── pmpro-donations-banner.png    # Plugin banner image
└── .github/
    ├── CONTRIBUTING.md
    ├── PULL_REQUEST_TEMPLATE.MD
    ├── ISSUE_TEMPLATE/           # Bug report, enhancement, feature request, support templates
    └── workflows/
        ├── generate-translations.yml  # Manual workflow: generates .pot/.po/.mo files
        └── sync-labels.yml
```

**Key fact**: This is a lightweight plugin (~600 lines of PHP across 5 files). No standalone JavaScript or CSS files — all JS is inline in PHP output, all styling uses PMPro core CSS classes.

## Development Workflow
### Build
No build step required. This is a pure PHP WordPress plugin with no compilation, bundling, or transpilation.

### Test
No automated test suite. Testing is manual via a WordPress installation with PMPro:
1. Clone/symlink into `wp-content/plugins/`
2. Activate via WordPress admin or `wp plugin activate pmpro-donations`
3. Create membership levels with donation settings enabled
4. Test checkout flow with various gateways

### Lint
No linter configuration present. WordPress PHP Coding Standards should be followed (tabs for indentation, `snake_case` naming with `pmprodon_` prefix).

### Other Commands
- **Translation generation**: Manual GitHub Actions workflow (`generate-translations.yml`) using `strangerstudios/action-wp-pot-po-mo-generator`
- No npm, composer, or other dependency management commands

## Architecture & Patterns

### Hook-Based Architecture
The plugin operates entirely through WordPress/PMPro action and filter hooks. The checkout flow executes through 11 hooks in a specific priority order (documented in AGENTS.md).

### Data Storage
- **Level settings**: Stored as WordPress options with key `pmprodon_{level_id}` (serialized array)
- **Donation amounts**: Stored in PMPro order meta with key `donation_amount` (since v2.0)
- **Legacy data**: Older donations stored in order notes as `"Donation: XX.XX"` — auto-migrated on first access

### Key Execution Flow (Checkout)
1. Dropdown value initialization (priority 1)
2. PayPal Express donation restoration (priority 10)
3. SSL enforcement for donation levels
4. Donation form UI rendering (after user fields)
5. Price modification — donation added to `$level->initial_payment` (priority 99)
6. Validation (min/max/negative checks)
7. Donation-only level handling (prevent level switch, restore original level)
8. Order meta storage

### Payment Gateway Handling
- **Stripe**: Works normally
- **PayPal Express**: Requires special handling — donation restored from order meta on redirect return
- **Pay by Check**: Known bug (#86) — donation amount stored as 0
- **PayPal Standard/TwoCheckout**: No billing fields needed — JS hides billing fieldset

### Email Integration
- `!!donation!!` template variable replaced in all checkout emails
- Auto-injection of donation info before "Invoice" section if template doesn't use the variable
- Hooks: `pmpro_email_data`, `pmpro_email_filter`

## Conventions
- **Indentation**: Tabs (WordPress standard)
- **Naming**: `snake_case` for functions and variables, prefixed with `pmprodon_`
- **Hook function naming**: Mirrors the hook name (e.g., `pmpro_after_checkout` -> `pmprodon_pmpro_after_checkout`)
- **Strings**: Single quotes for simple strings, double quotes for interpolation
- **Input sanitization**: `sanitize_text_field()` + `preg_replace('/[^0-9\.]/', ...)` for numeric values
- **Output escaping**: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- **i18n**: All user-facing strings wrapped in `__()`, `_e()`, `esc_html_e()` with text domain `pmpro-donations`
- **DocBlocks**: PHPDoc with `@since`, `@param`, `@return`
- **No external dependencies**: Zero composer or npm dependencies
- **Branching**: `dev` is the main/default branch; PRs target `dev`

## Key Files
- `pmpro-donations.php` — Plugin entry point and bootstrap
- `includes/common.php` — Core utility functions (3 functions)
- `includes/checkout.php` — Checkout flow (12 functions, ~517 lines — largest file)
- `includes/donation-only-level.php` — Donation-only level logic (3 functions)
- `includes/level-settings.php` — Admin level settings UI (2 functions)
- `includes/admin.php` — Admin order editing and CSV export (4 functions)
- `AGENTS.md` — Comprehensive agent instructions and architecture reference
- `PLAN.md` — v2.3 roadmap with 16 tickets

## Notes
- The project has 4 open PRs pending review (#83, #84, #85, #90) documented in PLAN.md
- Known bugs include: Pay by Check gateway not saving donations (#86), whitespace in donation amounts (#89), donation-only levels replacing user's membership (#72, #91)
- The v2.3 roadmap includes significant new features: donation amount buttons, "cover fees" checkbox, donor note field, and a donations report page
- No automated tests exist — all testing is manual through a WordPress + PMPro installation
- The plugin is approximately ~600 lines of functional PHP code across 5 include files
