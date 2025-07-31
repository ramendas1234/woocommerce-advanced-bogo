# WooCommerce Advanced BOGO Plugin

## Overview
This plugin adds advanced BOGO (Buy One Get One) functionality to WooCommerce with customizable templates and admin interface.

## Features
- **Advanced BOGO Rules**: Create complex buy X get Y discount rules
- **Multiple Templates**: 6 different BOGO message templates with color customization
- **Admin Interface**: User-friendly admin panel with WooCommerce-style tabs
- **AJAX Product Search**: Fast product selection using WooCommerce's enhanced select
- **Cart Integration**: BOGO hints in cart line items
- **Responsive Design**: Works on all devices

## Installation

### Tailwind CSS
The plugin uses Tailwind CSS v2.2.19 locally (no CDN dependency):

- **Location**: `assets/css/tailwind.min.css`
- **Size**: ~2.9MB
- **Version**: 2.2.19 (stable, earlier version for better compatibility)

### File Structure
```
woocommerce-advanced-bogo/
├── woocommerce-advanced-bogo.php    # Main plugin file
├── admin.js                         # Admin interface JavaScript
├── frontend.js                      # Frontend JavaScript
├── assets/
│   └── css/
│       └── tailwind.min.css        # Local Tailwind CSS
└── templates/
    ├── template1.php               # Classic Design
    ├── template2.php               # Modern Card
    ├── template3.php               # Dynamic Burst
    ├── template4.php               # Premium Banner
    ├── template5.php               # Minimal Clean
    └── template6.php               # Bold Statement
```

## Usage

### Admin Interface
1. Go to **WooCommerce > Advanced BOGO** in WordPress admin
2. **Rules Tab**: Create BOGO discount rules with product selection
3. **UI Settings Tab**: Customize template colors and appearance

### Frontend Display
- BOGO messages appear on product pages
- Cart hints show applicable offers
- Templates are fully responsive

## Technical Details

### CSS Loading
- **Local Tailwind**: No external dependencies
- **Custom Styles**: Inline CSS for BOGO-specific styling
- **Fallback**: Comprehensive fallback styles included

### JavaScript Features
- **AJAX Product Search**: WooCommerce enhanced select integration
- **Dynamic Rules**: Add/remove BOGO rules with real-time validation
- **Color Preview**: Instant template color changes
- **Cart Integration**: AJAX cart updates with BOGO offers

### PHP Integration
- **WordPress Hooks**: Proper integration with WooCommerce
- **Security**: Nonce verification for all AJAX requests
- **Performance**: Optimized database queries and caching

## Troubleshooting

### Common Issues
1. **Tailwind CSS Not Loading**: Ensure `assets/css/tailwind.min.css` exists
2. **Admin UI Broken**: Check browser console for JavaScript errors
3. **Product Search Not Working**: Verify WooCommerce is active and up-to-date

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Version History
- **v1.0.1**: Local Tailwind CSS, improved admin UI, cart integration
- **v1.0.0**: Initial release with CDN dependencies

## Support
For issues or questions, please check the WordPress admin console for error messages and ensure all files are properly uploaded.