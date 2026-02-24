# Built-in donations report with filtering and CSV export

## Description

## Summary
Build a proper donations report page for the PMPro Reports dashboard. PR #83 by @vbuster01 provides a starting point but requires substantial rework to meet coding standards and security requirements before it can be merged.

## Product Context
- **Goal**: Give site admins visibility into donation activity through a dedicated report page integrated into PMPro's existing Reports dashboard.
- **User Flow**: Admin navigates to PMPro → Reports → Donations Report → sees totals, filters by month/year, views individual donations, exports CSV.

## Implementation (Rework of PR #83)
1. **File restructure**: Move from root to `includes/reports.php`
2. **Function naming**: Rename all `MY_pmpro_` prefixed functions to `pmprodon_` per conventions
3. **Security fixes**:
   - Add `$wpdb->prepare()` for ALL query parameters
   - Add `wp_verify_nonce()` for CSV export
   - Add capability checks
4. **WordPress standards**:
   - Replace `date()` with `wp_date()` or `gmdate()`
   - Proper escaping and sanitization on all output/input
   - Use `pmpro-donations` text domain (not `pmpro`)
5. **Integration**: Add `require_once` from main plugin file
6. **Report features**:
   - Report widget on PMPro Reports dashboard
   - Total donations, average donation, donation count, top donors
   - Month/year filtering with proper UI
   - Individual donation entries with member details
   - CSV export with nonce verification and capability check
7. **Styling**: Follow PMPro's existing report page patterns and styles

## Files
- New: `includes/reports.php` (replaces PR #83's `pmpro-donations-report.php`)
- Modified: Main plugin file (add require_once)

## Acceptance Criteria

1. Report widget appears on PMPro Reports dashboard
2. Report page shows total donations, count, and average for selected period
3. Individual donation entries listed with member details
4. Month/year filtering works correctly
5. CSV export works with proper nonce verification and capability check
6. All functions use pmprodon_ prefix
7. File located in includes/reports.php and required from main plugin file
8. Proper escaping and sanitization on all output and input
9. Text domain is pmpro-donations throughout
10. All queries use $wpdb->prepare()
11. Uses wp_date() or gmdate() instead of date()
12. Follows PMPro report page patterns and styles

## Metadata

- **Priority**: P1
- **Estimated Hours**: 24
- **Tags**: feature, reports, admin, security, pr-merge, phase-4
