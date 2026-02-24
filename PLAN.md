# PMPro Donations — Roadmap (v2.3)

> Target version: **2.3**
> Branch: `dev-2.3` (create from `dev`)
> Last updated: 2026-02-23

This plan covers all open issues, ideas meeting items, and open PRs. Work is organized into phases. Each ticket includes acceptance criteria for HomurAI ingestion.

---

## Phase 0: PR Review & Merge

Merge existing open PRs into `dev-2.3` after review. These have been open since early-mid 2025 with no review comments.

### TICKET-001: Merge PR #90 — PHP 8.5 deprecation fix
- **Type**: Bug Fix
- **PR**: #90 by @dwanjuki (2026-01-09)
- **Branch**: `fix-php-8.5-notices` → `dev`
- **Changes**: Replace `(double)` casts with `(float)` in `checkout.php` (3 occurrences)
- **Risk**: Low — trivial cast rename, no behavior change
- **Action**: Merge as-is after confirming no conflicts with `dev`
- **Acceptance Criteria**:
  - [ ] No PHP deprecation notices on PHP 8.5+
  - [ ] All `(double)` casts replaced with `(float)` throughout the plugin
  - [ ] Checkout validation still works (min/max amounts, price formatting)

### TICKET-002: Merge PR #84 — Payment gateways for free donation levels
- **Type**: Bug Fix
- **PR**: #84 by @ipokkel (2025-04-16)
- **Resolves**: Issue #81 (PayPal Express not shown for free levels with donations)
- **Branch**: `payments-free-level` → `dev`
- **Changes**:
  - New function `pmprodon_enable_payments_for_free_level_donations()` hooked to `pmpro_is_level_free`
  - If a free level has donations enabled (with min_price > 0 or valid dropdown values), returns `false` so gateways render
  - Adds validation: if donation is required on free level, donation must be > 0
- **Review Notes**:
  - Logic is sound but the edge case where `dropdown_prices` contains only "other" needs testing — should still allow $0 donation in that case
  - The additional validation (`intval($level->initial_payment) === 0 && donation <= 0`) should only apply when `min_price > 0`, otherwise users should be able to donate $0 on a free level
- **Acceptance Criteria**:
  - [ ] PayPal Express and other gateways appear on checkout for free levels with donations enabled
  - [ ] Billing fields shown when donation amount > 0
  - [ ] Free level with donations but no min_price still allows $0 checkout
  - [ ] Free level with min_price > 0 requires a valid donation amount

### TICKET-003: Merge PR #85 — Restore level after donation-only checkout
- **Type**: Bug Fix
- **PR**: #85 by @ipokkel (2025-04-18)
- **Resolves**: Issue #72 (member level changes to donation level), Issue #91 (users keep donation level)
- **Branch**: `keep-level` → `dev`
- **Changes**: Complete rewrite of `donation-only-level.php`
  - Stores user's previous levels in user meta before checkout
  - After checkout, finds which level was in the same group as donation level
  - Cancels donation-only level via `pmpro_cancelMembershipLevel()`
  - Restores original level via `pmpro_changeMembershipLevel()` with full level data
- **Review Notes**:
  - The old approach (DELETE SQL query) was fragile. New approach using PMPro API functions is much better.
  - Need to verify: does `pmpro_cancelMembershipLevel()` trigger unwanted side effects (emails, hooks)?
  - The stored `pmprodon_previous_levels` user meta should be cleaned up even on failure paths
  - Test with: single level group, multiple level groups, no level groups, recurring subscription levels
- **Acceptance Criteria**:
  - [ ] User with Gold membership donates via donation-only level → keeps Gold membership
  - [ ] Donation-only level in same level group as user's level → user keeps original level
  - [ ] Donation-only level in separate level group → user keeps original level
  - [ ] Recurring subscription not cancelled at gateway after donation-only checkout
  - [ ] No orphaned `pmprodon_previous_levels` user meta after checkout

### TICKET-004: Review PR #83 — Donations report page
- **Type**: Feature
- **PR**: #83 by @vbuster01 (2025-04-04)
- **Branch**: `dev` → `dev` (⚠️ same branch name — needs rebase)
- **Changes**: New file `pmpro-donations-report.php` — adds a donations report to PMPro Reports dashboard with filtering and CSV export
- **Review Notes — Issues to Fix Before Merge**:
  - File should be in `includes/` directory, not root (follow existing structure)
  - Functions use `MY_pmpro_` prefix — must be renamed to `pmprodon_` prefix per conventions
  - Missing `$wpdb->prepare()` on some query parts — security review needed
  - Uses `date()` instead of `wp_date()` or `gmdate()` — WordPress standards
  - CSV export uses `$_GET` without nonce verification — needs `wp_verify_nonce()`
  - File is not `require_once`'d from main plugin file — needs integration
  - Report should use PMPro's existing report page patterns and styles
  - Hard-coded `'pmpro'` text domain should be `'pmpro-donations'`
- **Acceptance Criteria**:
  - [ ] Report widget appears on PMPro Reports dashboard
  - [ ] Report page shows total donations with month/year filtering
  - [ ] Individual donations listed with member details
  - [ ] CSV export works with proper nonce verification
  - [ ] All functions use `pmprodon_` prefix
  - [ ] File located in `includes/reports.php`
  - [ ] Proper escaping and sanitization on all output and input
  - [ ] Text domain is `pmpro-donations` throughout

---

## Phase 1: Bug Fixes

### TICKET-005: Fix donation amount not saved with Pay by Check gateway
- **Type**: Bug Fix
- **Issue**: #86
- **Priority**: High
- **Description**: When using Pay by Check gateway, donation amount is stored as 0 in order meta. Works correctly with Stripe and other gateways.
- **Root Cause Investigation**: The `pmprodon_store_donation_amount_in_order_meta()` function hooks `pmpro_after_checkout` and reads from `$_REQUEST['donation']`. Pay by Check may handle the checkout flow differently, clearing `$_REQUEST` before this hook fires. Compare the checkout flow for PBC vs Stripe to find where the donation value is lost.
- **Files**: `includes/checkout.php` (function `pmprodon_store_donation_amount_in_order_meta`)
- **Acceptance Criteria**:
  - [ ] Donation amount correctly saved to order meta when using Pay by Check gateway
  - [ ] Donation amount appears in "Additional Order Information" on admin order page
  - [ ] Invoice shows correct donation breakdown
  - [ ] No regression with Stripe, PayPal Express, or other gateways

### TICKET-006: Sanitize donation amounts — trim whitespace
- **Type**: Bug Fix
- **Issue**: #89
- **Priority**: Medium
- **Description**: Donation amounts with trailing whitespace (e.g., "10 ") are stored as strings with the space, causing them to be excluded from reports and numeric queries.
- **Fix**: Add `trim()` before storing donation amount. Apply in all storage paths:
  1. `pmprodon_store_donation_amount_in_order_meta()` in checkout.php
  2. `pmprodon_pmpro_updated_order()` in admin.php (admin order editing)
  3. `pmprodon_pmpro_checkout_level()` where donation is read from `$_REQUEST`
- **Files**: `includes/checkout.php`, `includes/admin.php`
- **Acceptance Criteria**:
  - [ ] Donation amounts are trimmed before storage (no leading/trailing whitespace)
  - [ ] Existing code's `preg_replace('/[^0-9\.]/', ...)` sanitization also strips whitespace — verify this catches the issue or add explicit `trim()`
  - [ ] Reports correctly count donations after fix
  - [ ] Admin-entered donation amounts also trimmed

### TICKET-007: Fix donation-only level replacing user's membership
- **Type**: Bug Fix
- **Issues**: #72, #91
- **Priority**: High
- **Description**: When a user with an existing membership checks out for a donation-only level in a level group, they end up keeping the donation level instead of their original. Two related reports: #72 (level changes to donation level) and #91 (users keep both levels).
- **Fix**: PR #85 addresses this. See TICKET-003 for merge plan.
- **Acceptance Criteria**: Same as TICKET-003

---

## Phase 2: Enhancements (from Issues)

### TICKET-008: Donation-specific confirmation email and page
- **Type**: Enhancement
- **Issue**: #62
- **Priority**: Medium
- **Description**: Checkout confirmation page and emails reference "membership" even for donation-only levels. Should reference "donation" instead.
- **Implementation**:
  - Filter `pmpro_confirmation_message` to replace membership language with donation language when checkout is for a donation-only level
  - Add a new email template (or filter existing checkout emails) for donation-only checkouts
  - Filter `pmpro_email_data` to provide donation-specific subject lines and body content
  - Use the existing `confirmation_message` level setting as a starting point
- **Files**: `includes/checkout.php`, `includes/donation-only-level.php`
- **Acceptance Criteria**:
  - [ ] Confirmation page says "Thank you for your donation" instead of "your membership is now active" for donation-only levels
  - [ ] Checkout email uses donation-appropriate language for donation-only levels
  - [ ] Regular donation levels (donation + membership) still show standard membership messaging
  - [ ] Site owner can customize the donation confirmation message per level (already exists)

### TICKET-009: Donor note field at checkout
- **Type**: Feature
- **Issue**: #82
- **Priority**: Medium
- **Description**: Add an optional "Donation Note" text field at checkout that appears on the invoice and in emails. Common use: "In memory of..." or "For the purpose of..."
- **Implementation**:
  - Add new level setting: `donation_note_enabled` (checkbox, optional per level)
  - Add new level setting: `donation_note_label` (custom label text, default "Donation Note")
  - Render textarea below donation amount field at checkout
  - Store note in order meta: key `donation_note`
  - Add email variable: `!!donation_note!!`
  - Display on invoice bullets and confirmation page
- **Files**: `includes/level-settings.php`, `includes/checkout.php`, `includes/admin.php`
- **Acceptance Criteria**:
  - [ ] Optional per-level setting to enable donation note field
  - [ ] Text field appears at checkout below donation amount when enabled
  - [ ] Note stored in order meta
  - [ ] Note appears on order invoice page
  - [ ] `!!donation_note!!` email variable works in templates
  - [ ] Note visible and editable in admin order edit page
  - [ ] Note included in CSV export

### TICKET-010: Admin donation field when adding orders
- **Type**: Enhancement
- **Issue**: #5
- **Priority**: Low
- **Description**: When admin creates an order via "Add New Order" or adds a member via "Add Member", there's no field to specify a donation amount. Currently only works through front-end checkout with payment gateways.
- **Implementation**:
  - Hook into `pmpro_after_order_settings` on the add-new-order admin page (may already exist for editing — check `admin.php`)
  - Show donation amount field when the selected level has donations enabled
  - Save donation to order meta on order creation
- **Files**: `includes/admin.php`
- **Acceptance Criteria**:
  - [ ] Donation amount field appears when creating new orders in admin
  - [ ] Field only shows for levels with donations enabled
  - [ ] Donation amount saved to order meta
  - [ ] Works with "Add Member" admin page if applicable

---

## Phase 3: Ideas Meeting Features

### TICKET-011: Big and beautiful donation option buttons
- **Type**: Feature (UI overhaul)
- **Source**: Ideas meeting (Jason)
- **Priority**: High
- **Description**: Replace the plain dropdown with visually appealing donation amount buttons at checkout. Think charity website donation forms — large, clickable amount tiles.
- **Implementation**:
  - New display mode when `dropdown_prices` are set: render as button grid instead of `<select>`
  - Each amount is a clickable card/tile showing the formatted price
  - "Other" option renders as a compact text input
  - Selected button gets active state styling
  - Falls back to text input if no dropdown prices configured
  - Needs custom CSS (first CSS file in the plugin) or inline styles using PMPro design tokens
  - Add level setting: `display_mode` (dropdown | buttons) — default buttons for new installs
- **Files**: `includes/checkout.php` (new rendering), new `css/` directory or inline styles, `includes/level-settings.php`
- **Acceptance Criteria**:
  - [ ] Donation amounts display as large, clickable button tiles at checkout
  - [ ] Selected amount highlighted with active state
  - [ ] "Other" option shows custom amount text input
  - [ ] Responsive — works on mobile
  - [ ] Accessible — keyboard navigable, ARIA labels, focus states
  - [ ] Level setting to choose between dropdown and button display modes
  - [ ] Integrates with PMPro's existing checkout styling
  - [ ] Works with all gateways (Stripe, PayPal, Check)

### TICKET-012: Cover fees checkbox at checkout
- **Type**: Feature
- **Source**: Ideas meeting (Jason)
- **Priority**: High
- **Description**: Add a "Cover processing fees" checkbox at checkout. When checked, the donation amount is increased to cover payment gateway fees so the organization receives the full intended donation.
- **Implementation**:
  - New level setting: `cover_fees_enabled` (checkbox)
  - New level setting: `cover_fees_percentage` (default 2.9 for Stripe)
  - New level setting: `cover_fees_flat` (default 0.30 for Stripe)
  - Render checkbox below donation amount: "Add $X.XX to cover processing fees"
  - JavaScript calculates fee amount dynamically as donation amount changes
  - Fee formula: `fee = (donation + flat) / (1 - percentage/100) - donation`
  - Store original donation and fee separately in order meta: `donation_fee_covered`
  - Show fee breakdown on invoice
- **Files**: `includes/checkout.php`, `includes/level-settings.php`, `includes/common.php`
- **Acceptance Criteria**:
  - [ ] "Cover processing fees" checkbox at checkout (off by default)
  - [ ] Fee amount calculated and displayed dynamically
  - [ ] When checked, total charge increased to cover fees
  - [ ] Fee percentage and flat amount configurable per level
  - [ ] Fee amount stored in order meta separately from donation
  - [ ] Invoice shows: Membership Cost, Donation, Processing Fee Covered
  - [ ] Works with dynamic amount changes (dropdown selection, custom input)

### TICKET-013: Better donations without a level
- **Type**: Enhancement
- **Source**: Ideas meeting (Jason)
- **Priority**: High
- **Description**: Improve the donation-only level experience. Current issues: wording still references "membership", levels get swapped in level groups, confirmation is confusing. Make donation-only feel like a proper donation flow, not a membership checkout hack.
- **Implementation** (builds on TICKET-003, TICKET-007, TICKET-008):
  - Filter all checkout page text for donation-only levels to use donation language
  - Hide membership-specific UI elements (account page level listing, expiration dates)
  - Ensure donation-only levels never replace existing levels (TICKET-003/PR #85)
  - Don't show donation-only level on membership account page
  - Custom confirmation page content for donation-only (TICKET-008)
  - Consider: should donation-only orders even create a membership_users row? Or just an order?
- **Files**: `includes/donation-only-level.php`, `includes/checkout.php`
- **Acceptance Criteria**:
  - [ ] Checkout page uses "donation" language instead of "membership" for donation-only levels
  - [ ] Donation-only level not shown on membership account page
  - [ ] Existing member's level not affected by donation-only checkout
  - [ ] Confirmation page appropriate for donations
  - [ ] No confusing "your membership level has changed" messaging

### TICKET-014: Different email/confirmation templates per level
- **Type**: Feature
- **Source**: Ideas meeting (Kim)
- **Priority**: Medium
- **Description**: Users increasingly want to use PMPro for generic purchases, not just memberships. Need a way to have different email confirmation templates and confirmation page content per level type (donation vs membership vs purchase).
- **Implementation**:
  - This may be better suited as a PMPro core feature or the Email Templates add-on
  - For donations specifically: use the existing `confirmation_message` setting (already per-level)
  - Add email template override per level: hook `pmpro_email_filter` and swap template when checkout level has donations
  - Add level setting: `email_template_override` — allows selecting a different email template
- **Dependencies**: May need coordination with pmpro-email-templates add-on
- **Acceptance Criteria**:
  - [ ] Donation-only levels can have fully customized confirmation emails
  - [ ] Confirmation page content customizable per level (already partially works)
  - [ ] Email uses `!!donation!!` and `!!donation_note!!` variables
  - [ ] Compatible with Email Templates add-on if installed

### TICKET-015: Guest donations (no account required)
- **Type**: Feature
- **Issue**: #22
- **Priority**: Low (complex, v3.0 candidate)
- **Description**: Allow donation-only checkout without creating a WordPress user account. For pure donation pages, requiring account creation is friction.
- **Implementation** (from issue #22):
  - Add level setting: `allow_guest_donations` (checkbox)
  - Dropdown on checkout: "Donate as Guest" or "Create Account"
  - Guest flow: collect email only, create temp user, process payment, delete user after confirmation
  - Generate random key for confirmation page access
  - Skip registration fields for guest checkout
  - Custom confirmation email (no account details)
- **Risk**: High complexity, touching core PMPro user creation flow
- **Acceptance Criteria**:
  - [ ] Guest can donate with only email address
  - [ ] No persistent WordPress user created for guest donations
  - [ ] Payment processed and order recorded
  - [ ] Confirmation page and email work without user account
  - [ ] Logged-in users don't see the guest/account toggle

### TICKET-016: Donation reminders
- **Type**: Feature
- **Issue**: #12
- **Priority**: Low
- **Description**: Send periodic reminders to members encouraging them to donate again. Useful for free levels with donation-only functionality.
- **Implementation**:
  - Add level setting: `reminder_interval` (none | monthly | quarterly | annually)
  - Cron job checks for members who donated more than X days ago
  - Sends reminder email with donation link
  - Track last donation date per user
  - Respect unsubscribe/opt-out
- **Dependencies**: Would benefit from PMPro's existing email infrastructure
- **Acceptance Criteria**:
  - [ ] Configurable reminder interval per level
  - [ ] Reminder emails sent on schedule
  - [ ] Links directly to donation checkout page
  - [ ] Tracks last donation date
  - [ ] Users can opt out of reminders

---

## Phase 4: Donations Report

### TICKET-017: Built-in donations report
- **Type**: Feature
- **Issue**: PR #83 (needs significant rework)
- **Priority**: Medium
- **Description**: Add a donations report page to PMPro Reports dashboard. PR #83 provides a starting point but needs substantial cleanup before merge.
- **Implementation** (rework of PR #83):
  - Move to `includes/reports.php`, rename all functions to `pmprodon_` prefix
  - Use `$wpdb->prepare()` for all queries
  - Add nonce verification for CSV export
  - Use `wp_date()` instead of `date()`
  - Follow PMPro report page patterns
  - Add report data: total donations, average donation, donation count, top donors
  - Month/year filtering with proper UI
  - CSV export with nonce verification
- **Acceptance Criteria**:
  - [ ] Report accessible from PMPro Reports dashboard
  - [ ] Shows total donations, count, average for selected period
  - [ ] Individual donation entries with member details
  - [ ] Month/year filtering
  - [ ] CSV export with security (nonce, capability check)
  - [ ] Follows WordPress coding standards and PMPro patterns

---

## Version & Release

**v2.3 Changelog Target**:
```
= 2.3 - 2026-XX-XX =
* BUG FIX: Fixed PHP 8.5 deprecation warnings for (double) cast. (#90)
* BUG FIX: Fixed PayPal Express and other gateways not showing for free levels with donations. (#81, #84)
* BUG FIX: Fixed donation-only level replacing member's existing membership in level groups. (#72, #91, #85)
* BUG FIX: Fixed donation amount not saved when using Pay by Check gateway. (#86)
* BUG FIX: Fixed donation amounts with whitespace not counted in reports. (#89)
* FEATURE: Big, beautiful donation amount buttons at checkout. (Ideas meeting)
* FEATURE: "Cover processing fees" checkbox at checkout. (Ideas meeting)
* FEATURE: Donor note field at checkout with invoice and email integration. (#82)
* ENHANCEMENT: Improved donation-only level experience with proper donation language. (#62)
* ENHANCEMENT: Built-in donations report page with filtering and CSV export. (#83)
* ENHANCEMENT: Donation amount field on admin Add New Order page. (#5)
```

**Ticket Priority Order** (recommended processing sequence):
1. TICKET-001 (PR #90 — trivial merge)
2. TICKET-006 (whitespace fix — simple)
3. TICKET-002 (PR #84 — gateway fix)
4. TICKET-003 (PR #85 — donation-only fix, biggest impact)
5. TICKET-005 (Pay by Check fix)
6. TICKET-008 (donation confirmation language)
7. TICKET-013 (better donation-only experience)
8. TICKET-011 (donation buttons — high visibility)
9. TICKET-012 (cover fees checkbox)
10. TICKET-009 (donor note field)
11. TICKET-004 (reports PR rework)
12. TICKET-017 (full reports feature)
13. TICKET-010 (admin donation field)
14. TICKET-014 (email templates per level)
15. TICKET-015 (guest donations — future)
16. TICKET-016 (donation reminders — future)
