# Antom Core Module

[![CI](https://github.com/ant-intl/ant-intl-plugin-magento2-core/workflows/CI/badge.svg)](https://github.com/ant-intl/ant-intl-plugin-magento2-core/actions)
[![codecov](https://codecov.io/github/ant-intl/ant-intl-plugin-magento2-core/branch/main/graph/badge.svg)](https://codecov.io/github/ant-intl/ant-intl-plugin-magento2-core)

Antom Payment Core Module for Magento 2. This module provides the core functionality for integrating Antom payment gateway with Magento 2.

## Features

- Payment gateway integration
- Order status management
- Payment redirect handling
- Logging and error handling
- Configurable payment methods

## Requirements

### Magento Version Support
- **Magento 2.4.2+: PHP 8.4+ recommended

### System Requirements
- **PHP**: ^7.4 or ^8.0
- **MySQL**: 5.7+ or 8.0+
- **Elasticsearch**: 6.x+ (for Magento 2.3.5+)
- **Composer**: 2.x

### Magento Dependencies
- Magento Framework 103.0+ (Magento 2.4.x+)
- Magento Sales Module 103.0+
- Magento Checkout Module 100.4+
- Magento Payment Module 100.4+
- Magento Quote Module 101.2+
- Magento Store Module 101.1+
- Magento Customer Module 102.0+

## Installation

### Via Composer (Recommended)
```bash
composer require ant-intl/plugin-magento2-core
```

### Manual Installation
1. Download the module
2. Extract to `app/code/Antom/Core`
3. Run:
   ```bash
   bin/magento module:enable Antom_Core
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento setup:static-content:deploy
   bin/magento cache:flush
   ```

## Configuration

Configure the module in Magento Admin:
1. Go to **Stores > Configuration > Sales > Payment Methods**
2. Find **Antom Payment** section
3. Configure your API credentials and settings

## Compatibility Matrix

| Magento Version | PHP Version | Status |
|-----------------|-------------|--------|
| 2.4.2 - 2.4.3   | 8.0         | ✅ Supported |
| 2.4.4+          | 8.1+        | ✅ Supported |

## License
This module is licensed under the MIT License.
