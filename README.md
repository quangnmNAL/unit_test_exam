# Order Processing Service

A PHP service for processing different types of orders with various business rules and validations.

## Requirements

- PHP 8.0 or higher
- Docker and Docker Compose
- Composer
- Xdebug (for code coverage)

## Installation


1. Install :
```bash
docker compose build
```

2. Start the Docker containers:
```bash
docker-compose up -d
```

3. Install dependencies:
```bash
docker-compose exec app bash
composer install
composer dump-autoload
```

## Project Structure

```
.
├── src/
│   ├── OrderProcessingService.php
│   ├── Order.php
│   ├── DatabaseService.php
│   ├── APIClient.php
│   └── Processors/
│       ├── TypeAOrderProcessor.php
│       ├── TypeBOrderProcessor.php
│       └── TypeCOrderProcessor.php
├── tests/
│   └── OrderProcessingServiceTest.php
├── docker-compose.yml
├── Dockerfile
├── composer.json
└── phpunit.xml
```

## Running Tests

### Run all tests:
```bash
docker-compose exec app vendor/bin/phpunit
```

### Run tests with coverage report:
```bash
docker-compose exec app vendor/bin/phpunit --coverage-html coverage
```

The coverage report will be generated in the `coverage` directory. You can view it by opening `coverage/index.html` in your browser.