# 🧪 Nawala Checker - Testing Guide

Complete testing documentation for the Nawala Checker application.

---

## 📊 Test Coverage Summary

### Total Test Cases: 54

**Feature Tests**: 38 test cases
- TargetFeatureTest: 12 tests
- ShortlinkFeatureTest: 12 tests
- SecurityTest: 14 tests

**Unit Tests**: 16 test cases
- CheckRunnerServiceTest: 8 tests
- ShortlinkRotationServiceTest: 8 tests

---

## 🎯 Test Categories

### 1. CRUD Operations (24 tests)

**Target CRUD** (12 tests):
- ✅ List targets with pagination
- ✅ Create target with validation
- ✅ Show target details
- ✅ Update target
- ✅ Delete target (soft delete)
- ✅ Toggle target status
- ✅ Run check manually
- ✅ Filter by group
- ✅ Filter by tags
- ✅ Search functionality
- ✅ Sanitize input on create
- ✅ Require authentication

**Shortlink CRUD** (12 tests):
- ✅ List shortlinks
- ✅ Create shortlink with multiple targets
- ✅ Validate slug uniqueness
- ✅ Require minimum 2 targets
- ✅ Show shortlink details
- ✅ Force rotate shortlink
- ✅ Rollback shortlink
- ✅ Delete shortlink
- ✅ Sanitize input on create
- ✅ Prevent rotation during cooldown
- ✅ Require authentication
- ✅ Cascade delete targets

### 2. Security Tests (14 tests)

**Authentication** (1 test):
- ✅ Require authentication for all routes

**XSS Prevention** (3 tests):
- ✅ Sanitize XSS in target creation
- ✅ Sanitize XSS in target update
- ✅ Escape output in responses

**SQL Injection** (1 test):
- ✅ Prevent SQL injection in search

**Validation** (4 tests):
- ✅ Validate domain format
- ✅ Validate URL format
- ✅ Validate check interval range
- ✅ Prevent mass assignment vulnerabilities

**Rate Limiting** (2 tests):
- ✅ Enforce rate limiting on target creation
- ✅ Enforce rate limiting on check execution

**Authorization** (1 test):
- ✅ Prevent unauthorized target modification

**Output Security** (1 test):
- ✅ Escape output in responses

**Input Validation** (1 test):
- ✅ Validate dangerous protocols (javascript:, data:, file:, ftp:)

### 3. Business Logic Tests (16 tests)

**DNS/HTTP Checking** (8 tests):
- ✅ Resolve DNS successfully
- ✅ Detect blocked IP addresses
- ✅ Check HTTP accessibility
- ✅ Detect block pages by content
- ✅ Fuse verdicts from multiple resolvers
- ✅ Calculate confidence scores
- ✅ Handle DNS resolution failures
- ✅ Handle HTTP request failures

**Shortlink Rotation** (8 tests):
- ✅ Rotate to next available target
- ✅ Respect priority ordering
- ✅ Check rotation threshold
- ✅ Enforce cooldown period
- ✅ Rollback to original target
- ✅ Auto-rotate all shortlinks
- ✅ Select target by weight
- ✅ Handle no available targets

---

## 🚀 Running Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit
```

### Run Specific Test File

```bash
# Target tests
php artisan test tests/Feature/NawalaChecker/TargetFeatureTest.php

# Shortlink tests
php artisan test tests/Feature/NawalaChecker/ShortlinkFeatureTest.php

# Security tests
php artisan test tests/Feature/NawalaChecker/SecurityTest.php

# Check runner tests
php artisan test tests/Unit/NawalaChecker/CheckRunnerServiceTest.php

# Rotation tests
php artisan test tests/Unit/NawalaChecker/ShortlinkRotationServiceTest.php
```

### Run Specific Test Method

```bash
php artisan test --filter=it_can_create_target
```

### Run with Coverage

```bash
php artisan test --coverage
```

### Run with Detailed Output

```bash
php artisan test --verbose
```

---

## 📝 Test File Locations

```
tests/
├── Feature/
│   └── NawalaChecker/
│       ├── TargetFeatureTest.php       (12 tests)
│       ├── ShortlinkFeatureTest.php    (12 tests)
│       └── SecurityTest.php            (14 tests)
└── Unit/
    └── NawalaChecker/
        ├── CheckRunnerServiceTest.php  (8 tests)
        └── ShortlinkRotationServiceTest.php (8 tests)
```

---

## 🔍 Test Details

### TargetFeatureTest.php

Tests all target-related functionality:
- CRUD operations
- Validation rules
- Search and filtering
- Status toggling
- Manual check execution
- Input sanitization
- Authentication requirements

### ShortlinkFeatureTest.php

Tests all shortlink-related functionality:
- CRUD operations
- Multi-target management
- Rotation logic
- Rollback functionality
- Cooldown enforcement
- Input sanitization
- Authentication requirements

### SecurityTest.php

Tests security measures:
- XSS prevention
- SQL injection prevention
- Rate limiting
- Input validation
- Mass assignment protection
- Output escaping
- Protocol validation

### CheckRunnerServiceTest.php

Tests DNS/HTTP checking logic:
- DNS resolution (standard, DoH, DoT)
- IP-based block detection
- HTTP accessibility checks
- Content-based block detection
- Verdict fusion algorithm
- Confidence calculation
- Error handling

### ShortlinkRotationServiceTest.php

Tests auto-rotation logic:
- Target selection by priority
- Threshold checking
- Cooldown management
- Rollback functionality
- Auto-rotation for all shortlinks
- Weight-based selection
- Edge case handling

---

## ✅ Test Assertions

### Common Assertions Used

- `assertStatus(200)` - HTTP status codes
- `assertRedirect()` - Redirects
- `assertSessionHasErrors()` - Validation errors
- `assertDatabaseHas()` - Database records exist
- `assertDatabaseMissing()` - Database records don't exist
- `assertDatabaseCount()` - Count records
- `assertInertia()` - Inertia responses
- `assertEquals()` - Value equality
- `assertTrue()` / `assertFalse()` - Boolean values
- `assertStringContains()` - String content
- `assertStringNotContains()` - String doesn't contain

---

## 🛠️ Test Setup

### Database

Tests use in-memory SQLite database for speed:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
}
```

### Factories

All models have factories for easy test data generation:

```php
Target::factory()->create();
Shortlink::factory()->count(3)->create();
```

### Authentication

Tests authenticate users when needed:

```php
$user = User::factory()->create();
$this->actingAs($user);
```

---

## 📈 Coverage Goals

- **Overall Coverage**: 80%+
- **Critical Paths**: 100%
- **Security Features**: 100%
- **Business Logic**: 90%+

---

## 🐛 Debugging Tests

### View Test Output

```bash
php artisan test --verbose
```

### Stop on First Failure

```bash
php artisan test --stop-on-failure
```

### Run Single Test

```bash
php artisan test --filter=test_method_name
```

### Enable Debug Mode

```bash
APP_DEBUG=true php artisan test
```

---

## 📚 Writing New Tests

### Feature Test Template

```php
<?php

namespace Tests\Feature\NawalaChecker;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_does_something()
    {
        $response = $this->actingAs($this->user)
            ->get('/some-route');

        $response->assertStatus(200);
    }
}
```

### Unit Test Template

```php
<?php

namespace Tests\Unit\NawalaChecker;

use Tests\TestCase;

class MyUnitTest extends TestCase
{
    /** @test */
    public function it_calculates_correctly()
    {
        $service = new MyService();
        $result = $service->calculate(10);
        
        $this->assertEquals(20, $result);
    }
}
```

---

## ✅ Continuous Integration

Tests should be run on every commit:

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

---

**All tests passing! ✅**

