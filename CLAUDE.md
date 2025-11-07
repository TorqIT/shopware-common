# TorqShopwareCommon Plugin

## Purpose

Shared Shopware plugin providing reusable functionality across all Torq Shopware projects. Distributed as a Git submodule. See README.md for installation and setup instructions.

## When to Add Features Here

**Add features when:**
- Functionality will be used across multiple Torq Shopware projects
- Feature is generic and doesn't contain project-specific business logic
- Feature enhances core Shopware functionality in a reusable way

**Do NOT add when:**
- Functionality is specific to a single project
- Feature contains project-specific business logic or configuration
- Feature is experimental and not yet proven

## Plugin Namespace

```php
Torq\Shopware\Common
```

## Key Feature Areas

- **Employee Impersonation** - Customer account impersonation for support (`Checkout/Customer/`, `Storefront/Controller/ImitateEmployeeController.php`)
- **Security & Encryption** - Encryption wrapper using `defuse/php-encryption` (`Security/Encryption/EncryptionHandler.php`)
- **Product Filtering** - Enhanced filtering for product listings (`Service/Filter/`, `Subscriber/ProductListingFilterSubscriber.php`)
- **Quick Add** - Quick add-to-cart with autocomplete (`Storefront/Controller/QuickAddController.php`)
- **Custom Rules** - Extended Shopware rule system (`Rule/CustomerAddressCustomFieldRule.php`)
- **Entity Import/Export** - CLI tools for entity data (`Command/EntityExportCommand.php`, `Command/EntityImportCommand.php`)
- **CMS Extensions** - Custom CMS blocks/elements (`Resources/app/administration/src/module/sw-cms/`)
- **System Config Extensions** - Enhanced configuration (`SystemConfig/SystemConfigExtensions.php`)
- **Database Sessions** - Database-backed session storage (`Migration/Migration1738249793DatabaseSessionSupport.php`)
- **Admin Security** - Admin access controls (`Subscriber/AdminAccessBlockerSubscriber.php`)
- **Utilities** - General helpers (`Utilities/`, `Twig/StringTemplateRenderer.php`)

## Directory Structure

```
src/
├── Checkout/              # Customer checkout extensions
├── Command/               # CLI commands
├── Core/                  # Core Shopware extensions
├── Entity/                # Entity utilities
├── Resources/
│   ├── app/
│   │   ├── administration/  # Admin UI (Vue components, extensions)
│   │   └── storefront/      # Storefront JS plugins
│   ├── config/           # services.xml, routes.xml, config.xml
│   ├── snippet/          # Translations
│   └── views/            # Twig templates
├── Rule/                  # Custom rule conditions
├── Security/              # Encryption and security
├── Service/               # Business logic
├── Storefront/            # Storefront controllers
├── Subscriber/            # Event subscribers
└── Utilities/             # Helper utilities
```

## Development Conventions

- Follow Shopware and Symfony best practices
- Use dependency injection via `services.xml`
- Use PSR-4 autoloading standards
- Type hint parameters and return types
- Write unit tests for new functionality in `tests/`
- Document complex functionality

## Important Notes

- **Encryption**: Requires `TORQ_SHOPWARE_COMMON_ENCRYPTION_SECRET` environment variable (see README for security rules)
- **Testing**: Run tests with `vendor/bin/phpunit --configuration phpunit.xml`
- **Version branches**: v3.x for Shopware 6.6.x, v4.x for Shopware 6.7.x
