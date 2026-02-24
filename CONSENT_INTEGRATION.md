# Barion Pixel Consent Integration Guide

## Overview

The Barion Pixel for WooCommerce plugin provides automatic integration with the Cookie Law Info plugin and manual hooks for other consent management systems.

## Automatic Integration with Cookie Law Info

**Recommended:** The plugin automatically integrates with the [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/) WordPress plugin.

### How It Works

1. The plugin checks if Cookie Law Info is active on the page
2. When a user accepts cookies via Cookie Law Info, Barion consent is automatically granted
3. If cookies are already accepted (returning visitor), consent is granted immediately on page load
4. No additional consent banner is needed - users only see the Cookie Law Info banner

### Configuration

No additional configuration is needed. Simply:

1. Install and configure Cookie Law Info plugin
2. Install and configure Barion Pixel for WooCommerce plugin
3. The integration works automatically

### Technical Details

The integration:
- Listens for the `cli_user_preference_set` event from Cookie Law Info
- Checks the `cookielawinfo-checkbox-necessary` cookie
- Automatically calls `bp('consent', 'grantConsent')` when consent is detected

## Manual Integration Methods

If you're using a different consent manager, use one of these methods:

### Method 1: JavaScript Function (Recommended)

Call the global JavaScript function when consent is granted:

```javascript
// Example: In your consent manager callback
function onConsentGranted() {
    // Your other consent logic...
    
    // Grant Barion consent
    if (typeof window.wcBarionGrantConsent === 'function') {
        window.wcBarionGrantConsent();
    }
}
```

### Method 2: Custom JavaScript Event

Dispatch a custom event to trigger consent:

```javascript
// Example: In your consent manager
function onMarketingConsentGranted() {
    document.dispatchEvent(new Event('wcBarionGrantConsent'));
}
```

### Method 3: WordPress Action Hook

For server-side integrations, use the WordPress action:

```php
// In your consent manager plugin or theme
add_action('wc_barion_pixel_footer_scripts', 'my_custom_consent_handler');

function my_custom_consent_handler() {
    // Your custom logic here
    // This runs in the footer after Barion Pixel is loaded
}
```

## Testing

1. Enable Debug Mode in **Settings > Barion Pixel**
2. Open browser console (F12)
3. Accept cookies via Cookie Law Info (or trigger your consent method)
4. Look for: `[Barion Pixel] Consent granted` or `[Barion Pixel] Consent auto-granted via Cookie Law Info` in the console

## Common Consent Managers

### Cookie Law Info (Automatic - Already Integrated)
No code needed - the plugin handles this automatically.

### CookieBot Example
```javascript
window.addEventListener('CookiebotOnAccept', function(e) {
    if (Cookiebot.consent.marketing) {
        window.wcBarionGrantConsent();
    }
});
```

### OneTrust Example
```javascript
function OptanonWrapper() {
    if (OnetrustActiveGroups.includes('C0004')) { // Marketing cookies
        window.wcBarionGrantConsent();
    }
}
```

## Important Notes

- The Base Barion Pixel loads immediately for fraud prevention purposes
- The `grantConsent` event should be called only when users explicitly accept marketing/analytics cookies
- Cookie Law Info integration is automatic and requires no additional configuration
- The function is safe to call multiple times (idempotent)
- Always test in Debug Mode before deploying to production

