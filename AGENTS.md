# PMPro Donations — Agent Instructions

> Paid Memberships Pro add-on for accepting donations at checkout. This file is the primary instruction reference for any AI agent working in this repo.

## What This Is

PMPro Donations adds donation functionality to the Paid Memberships Pro checkout flow. Members can add a one-time donation on top of their membership fee, or donate without a membership via "donation-only" levels.

- **Plugin Slug**: `pmpro-donations`
- **Current Version**: 2.2
- **Requires**: Paid Memberships Pro (core plugin)
- **WordPress Tested Up To**: 6.8
- **PHP**: 7.4+ (supports up to 8.5)
- **Text Domain**: `pmpro-donations`
- **License**: GPL-2.0+
- **Repo**: https://github.com/strangerstudios/pmpro-donations

## Repo Layout

```
pmpro-donations/
├── pmpro-donations.php           # Main plugin file (bootstrap, includes, meta links)
├── includes/
│   ├── common.php                # Shared utilities (get settings, get price components)
│   ├── checkout.php              # Checkout flow: form display, validation, price modification
│   ├── donation-only-level.php   # Donation-only level logic (keep member's existing level)
│   ├── level-settings.php        # Admin: level edit page donation settings UI
│   └── admin.php                 # Admin: order editing, CSV export column
├── languages/
│   ├── pmpro-donations.pot       # Translation template
│   ├── pmpro-donations.po        # Translation source
│   ├── pmpro-donations.mo        # Compiled translations
│   └── gettext.sh                # Translation extraction script
├── readme.txt                    # WordPress.org plugin readme
├── readme.md                     # GitHub readme
├── AGENTS.md                     # This file
├── PLAN.md                       # Current roadmap and next steps
└── .github/                      # GitHub templates and workflows
```

**Key fact**: This is a lightweight plugin (~600 lines of PHP across 5 files). No standalone JavaScript or CSS files — all JS is inline in PHP, all styling uses PMPro core CSS classes.

## Architecture

### Checkout Flow (Execution Order)

When a user checks out on a level with donations enabled:

| # | Hook | Function | File | Purpose |
|---|------|----------|------|---------|
| 1 | `pmpro_checkout_preheader_before_get_level_at_checkout` (pri 1) | `pmprodon_init_dropdown_values()` | checkout.php | Copy dropdown selection to `$_REQUEST['donation']` |
| 2 | `pmpro_checkout_preheader_before_get_level_at_checkout` (pri 10) | `pmprodon_ppe_add_donation_to_request()` | checkout.php | PayPal Express: restore donation from order meta on return |
| 3 | `pmpro_checkout_preheader` | `pmprodon_pmpro_checkout_preheader()` | checkout.php | Force SSL for donation levels |
| 4 | `pmpro_checkout_before_form` | `pmprodon_hook_pmpro_level_cost_text()` | checkout.php | Add cost text filter |
| 5 | `pmpro_checkout_after_user_fields` | `pmprodon_pmpro_checkout_after_user_fields()` | checkout.php | **Render donation form UI** (dropdown or text input) |
| 6 | `pmpro_checkout_level` (pri 99) | `pmprodon_pmpro_checkout_level()` | checkout.php | **Add donation to `$level->initial_payment`** |
| 7 | `pmpro_checkout_after_level_cost` | `pmprodon_unhook_pmpro_level_cost_text()` | checkout.php | Remove cost text filter |
| 8 | `pmpro_registration_checks` | `pmprodon_pmpro_registration_checks()` | checkout.php | **Validate** donation (min, max, negative) |
| 9 | `pmpro_checkout_before_change_membership_level` (pri 1) | `pmprodon_pmpro_checkout_before_change_membership_level()` | donation-only-level.php | Donation-only: prevent level switching |
| 10 | `pmpro_after_checkout` | `pmprodon_store_donation_amount_in_order_meta()` | checkout.php | **Save donation to order meta** |
| 11 | `pmpro_after_checkout` | `pmprodon_pmpro_after_checkout()` | donation-only-level.php | Donation-only: restore member's previous level |

### Data Storage

**Donation Settings** (per level):
```php
// Option: pmprodon_{level_id}
get_option( 'pmprodon_' . $level_id );
// Returns:
array(
    'donations'            => 0|1,       // Enable donations
    'donations_only'       => 0|1,       // Donation-only level
    'min_price'            => '5.00',    // Minimum donation
    'max_price'            => '500.00',  // Maximum donation
    'dropdown_prices'      => '10,25,50,other',  // Comma-separated
    'text'                 => '<p>Help text HTML</p>',
    'confirmation_message' => '<p>Thank you HTML</p>'
)
```

**Donation Amount** (per order):
```php
// Primary (v2.0+): Order meta
update_pmpro_membership_order_meta( $order->id, 'donation_amount', $amount );
get_pmpro_membership_order_meta( $order->id, 'donation_amount', true );

// Legacy (v1.x): Order notes — auto-migrated on first access
// Pattern: "Donation: 25.00" in $order->notes
```

### Key Utility Functions

| Function | File | Purpose |
|----------|------|---------|
| `pmprodon_get_level_settings( $level_id )` | common.php | Get donation settings for a level (returns array with defaults) |
| `pmprodon_is_donations_only( $level_id )` | common.php | Check if level is donation-only |
| `pmprodon_get_price_components( $order )` | common.php | Split order total into `['price' => float, 'donation' => float]` |

### Filters for Customization

| Filter | Purpose |
|--------|---------|
| `pmpro_donations_get_price_components` | Customize how order total is split into price + donation |
| `pmpro_donations_invoice_bullets` | Customize invoice bullet points |

### Email Integration

- **Variable**: `!!donation!!` — replaced with formatted donation amount in all checkout emails
- **Auto-injection**: If email template doesn't use `!!donation!!`, donation info is injected before the "Invoice" section
- **Hook**: `pmpro_email_data` and `pmpro_email_filter`

### Donation-Only Levels

Special levels where existing members can donate without losing their current membership:

1. Admin enables "Donations-Only Level" checkbox in level settings
2. At checkout, plugin prevents cancellation of existing subscriptions (`pmpro_cancel_previous_subscriptions` → `__return_false`)
3. After checkout, the donation-only level row is removed from `pmpro_memberships_users`
4. User keeps their original level; donation is recorded on the order

### Inline JavaScript

All JS lives in two places:

**checkout.php** (lines 124-204):
- `pmprodon_toggleOther()` — show/hide custom amount input when dropdown changes
- `pmprodon_checkForFree()` — show/hide billing fields based on donation amount and gateway
- Event listeners on `#donation_dropdown`, `#donation`, and `input[name=gateway]`

**level-settings.php** (lines 85-104):
- `toggleDonFields()` — show/hide admin settings based on "Enable Donations" checkbox

### CSS Classes Used (from PMPro core)

The plugin uses PMPro's built-in CSS classes — no custom stylesheet:
- `pmpro_form_fieldset`, `pmpro_card`, `pmpro_card_content`
- `pmpro_form_field-donation`, `pmpro_form_input-select`, `pmpro_form_input-text`
- `pmpro_alter_price` — triggers PMPro's price change detection (v2.0+)

## Development Setup

### Local WordPress Environment

This plugin requires an active WordPress installation with Paid Memberships Pro:

```bash
# Clone into wp-content/plugins/
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/strangerstudios/pmpro-donations.git

# Or symlink from repos dir
ln -s ~/repos/strangerstudios/pmpro-donations /path/to/wordpress/wp-content/plugins/pmpro-donations

# Activate via WP CLI
wp plugin activate pmpro-donations
```

### Testing Donations

1. Create a membership level in PMPro (Memberships > Settings > Levels)
2. Edit the level, scroll to "Donation Settings"
3. Check "Enable Donations", set min/max/dropdown values
4. Visit the checkout page for that level
5. For donation-only testing: also check "Donations-Only Level"

### Branching

- **`dev`** — Main development branch (default). PRs target this.
- **Feature branches** — Named descriptively (e.g., `keep-level`, `payments-free-level`)
- **No `main`/`master`** — This repo uses `dev` as the default branch

## Coding Conventions

### WordPress Coding Standards

This plugin follows [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

- **Indentation**: Tabs (not spaces)
- **Naming**: `snake_case` for functions and variables, prefixed with `pmprodon_`
- **Strings**: Single quotes for simple strings, double quotes when interpolation needed
- **Sanitization**: All `$_REQUEST`/`$_GET`/`$_POST` values sanitized with `sanitize_text_field()` and `preg_replace()` for numeric values
- **Escaping**: Output escaped with `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- **i18n**: All user-facing strings wrapped in `__()`, `_e()`, `esc_html_e()`, etc. with text domain `pmpro-donations`
- **DocBlocks**: PHPDoc on all functions with `@since`, `@param`, `@return`

### Function Prefix

All functions use the `pmprodon_` prefix:
```php
pmprodon_get_level_settings()
pmprodon_pmpro_checkout_level()
pmprodon_is_donations_only()
```

### Hook Naming

When hooking into PMPro, the function name mirrors the hook:
```php
// Hook: pmpro_after_checkout → Function: pmprodon_pmpro_after_checkout
add_action( 'pmpro_after_checkout', 'pmprodon_pmpro_after_checkout' );
```

### No External Dependencies

The plugin has zero composer or npm dependencies. No build step. PHP only.

## Gateway Considerations

Different payment gateways behave differently with donations:

| Gateway | Behavior | Special Handling |
|---------|----------|-----------------|
| **Stripe** | Works normally — donation added to total | None |
| **PayPal Express** | Donation lost on redirect return | `pmprodon_ppe_add_donation_to_request()` restores from order meta |
| **Pay by Check** | Known bug: donation amount not saved | Issue #86 — needs fix |
| **PayPal Standard** | No billing fields needed | JS hides billing fieldset |

Gateways that don't require billing info (handled in checkout JS):
```javascript
var no_billing_gateways = ['paypalexpress', 'twocheckout', 'check', 'paypalstandard'];
```

## Key Issue Patterns

Common areas where bugs appear:

1. **Donation-only levels + level groups** — The interaction between donation-only levels and PMPro's level group system (which controls how many levels a user can hold) is complex and fragile.
2. **Gateway-specific behavior** — Each payment gateway handles the checkout flow differently, especially around redirects (PayPal) and free levels.
3. **Price display vs. actual charge** — The plugin modifies `$level->initial_payment` at priority 99 but needs to show the original price in the cost text, requiring careful hook/unhook timing.
4. **Data format consistency** — Donation amounts should always be stored as clean numeric values. Watch for whitespace and string formatting issues.
