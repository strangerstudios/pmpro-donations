# Big and beautiful donation amount buttons UI at checkout

## Description

## Summary
Replace the plain dropdown with visually appealing, large, clickable donation amount button tiles at checkout — inspired by modern charity donation forms. This is the first CSS file in the plugin.

## Product Context
- **Goal**: Make the donation selection experience feel modern and inviting, increasing conversion.
- **User Flow**: Donor arrives at checkout → sees large, attractive amount buttons ($10, $25, $50, $100, Other) → clicks desired amount → button highlights → proceeds to checkout.
- **Source**: Ideas meeting (Jason), High Priority

## Implementation
1. **New display mode** when `dropdown_prices` are set:
   - Render as button grid instead of `<select>` dropdown
   - Each amount is a clickable card/tile showing the formatted price
   - "Other" option renders as a compact text input
   - Selected button gets active state styling

2. **Level settings** (`includes/level-settings.php`):
   - New setting: `display_mode` (dropdown | buttons)
   - Default to buttons for new installs

3. **Styling**:
   - New `css/` directory or inline styles using PMPro design tokens
   - This is the plugin's first CSS file — establish good patterns
   - Responsive design for mobile
   - Accessible: keyboard navigable, ARIA labels, focus states

4. **Fallback**: Falls back to text input if no dropdown prices configured

## Files
- `includes/checkout.php` — new rendering logic
- New `css/` directory with stylesheet
- `includes/level-settings.php` — display_mode setting

## Acceptance Criteria

1. Donation amounts display as large, clickable button tiles at checkout when display_mode is 'buttons'
2. Selected amount highlighted with active state styling
3. 'Other' option shows custom amount text input inline
4. Responsive design works on mobile devices
5. Accessible: keyboard navigable, ARIA labels, proper focus states
6. Level setting to choose between dropdown and button display modes
7. Default display mode is 'buttons' for new configurations
8. Integrates with PMPro's existing checkout styling and design tokens
9. Works with all gateways (Stripe, PayPal, Check)
10. Falls back to text input when no dropdown prices are configured
11. CSS follows established patterns (first CSS file in plugin)

## Metadata

- **Priority**: P1
- **Estimated Hours**: 24
- **Tags**: feature, ui, checkout, css, accessibility, phase-3
