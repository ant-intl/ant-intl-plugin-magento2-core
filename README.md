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
- **Magento 2.3.x**: PHP 7.4 required
- **Magento 2.4.x**: PHP 8.0+ recommended

### System Requirements
- **PHP**: ^7.4 || ^8.0
- **MySQL**: 5.7+ or 8.0+
- **Elasticsearch**: 6.x+ (for Magento 2.3.5+)
- **Composer**: 2.x

### Magento Dependencies
- Magento Framework 101.0+ (2.3.x+)
- Magento Sales Module 101.0+
- Magento Checkout Module 100.0+
- Magento Payment Module 100.0+
- Magento Quote Module 100.0+
- Magento Store Module 100.0+
- Magento Customer Module 101.0+

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
| 2.3.0 - 2.3.4   | 7.4         | ✅ Supported |
| 2.3.5 - 2.3.7   | 7.4         | ✅ Supported |
| 2.4.0 - 2.4.3   | 8.0         | ✅ Supported |
| 2.4.4+          | 8.1+        | ✅ Supported |

## Testing

### Running Tests
```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test suite
./vendor/bin/phpunit app/code/Antom/Core/Test/Unit/Controller/Notification/IndexTest.php
```

### Test Coverage
- 36 comprehensive unit tests
- 107 assertions
- 100% test pass rate

## Development

### CI/CD Setup
To enable code coverage reporting:

1. **Codecov Integration**:
   - Visit [Codecov GitHub App](https://github.com/apps/codecov)
   - Install the app for the `ant-intl` organization
   - Ensure the repository is selected during installation

2. **GitHub Actions**:
   - The CI workflow is already configured in `.github/workflows/ci.yml`
   - Pushes to `main` and `develop` branches will trigger automatic testing

### Project Structure
```
Antom/Core/
├── Controller/          # Controllers
├── Helper/             # Helper classes
├── Model/              # Models
├── Test/               # Unit tests
├── etc/                # Configuration files
├── registration.php    # Module registration
├── composer.json       # Composer configuration
└── README.md          # This file
```

### CI/CD
This module includes:
- **Travis CI** configuration for automated testing
- **PHPUnit** for unit testing
- **Code coverage** reporting

## Troubleshooting

### Common Issues
1. **PHP Version Error**: Ensure PHP 7.4+ is installed
2. **Dependency Error**: Run `composer install` to install dependencies
3. **Module Not Found**: Check if module is enabled with `bin/magento module:status`

### Support
For issues and questions, please refer to the official documentation or create an issue in the repository.

## License
This module is licensed under the MIT License.
