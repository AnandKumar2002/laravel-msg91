# Contributing to Laravel MSG91

First and foremost, **thank you** for considering contributing to `laravel-msg91`! It's people like you that make the open-source community such an incredible place to learn, inspire, and create.

This document serves as a comprehensive guide for contributing to this repository. By participating in this project, you agree to abide by our Code of Conduct and coding standards.

---

## 🧭 Code of Conduct

We are committed to providing a friendly, safe, and welcoming environment for all, regardless of experience level, gender identity, sexual orientation, disability, personal appearance, body size, race, ethnicity, age, religion, or nationality. 
Please be kind, constructive, and respectful in your issues and pull requests.

---

## 🛠️ Development Setup

To get started with local development, follow these steps to fork and clone the repository:

### 1. Prerequisites
- **PHP** `8.1` or higher
- **Composer** (v2+)
- A basic understanding of Laravel Package Development and Service Providers.

### 2. Installation
```bash
# Fork the repository on GitHub, then clone your fork:
git clone https://github.com/YOUR_USERNAME/laravel-msg91.git
cd laravel-msg91

# Install dependencies (including Testbench and PHPUnit)
composer install
```

---

## 🧪 Testing

We practice **Test-Driven Development (TDD)**. All new features and bug fixes **must** be accompanied by passing unit or feature tests. We use `orchestra/testbench` to simulate a Laravel environment.

To run the test suite:
```bash
# Run all tests natively
composer test

# Run tests with detailed, readable output (TestDox)
vendor/bin/phpunit --testdox

# Run a specific test file
vendor/bin/phpunit tests/Feature/SmsTest.php
```
> **Note:** If you add a new endpoint or feature, ensure you also update `Msg91Fake.php` so that users can mock your new feature in their own applications!

---

## 💅 Code Style & Formatting

This project strictly adheres to the **Laravel Coding Standard**, enforced by [Laravel Pint](https://laravel.com/docs/pint).

Before submitting a Pull Request, please ensure your code is perfectly formatted:

```bash
# Automatically fix all code style issues
composer format

# Or run it dry to see what would change
vendor/bin/pint --test
```

---

## 🏛️ Architecture & Coding Principles

If you are writing new code for this package, please adhere to these core principles:

1. **Strict Typing:** Every PHP file must start with `declare(strict_types=1);`.
2. **Immutability:** Use `readonly` properties for DTOs (Data Transfer Objects) wherever possible.
3. **API-First:** This package does *not* ship with UI views, Livewire components, or frontend assets. Keep all PRs strictly focused on backend API integration.
4. **Feature Flags:** If you add a new MSG91 channel (e.g., RCS), ensure it respects a feature flag in `config/msg91.php` and throws a `FeatureDisabledException` if accessed while disabled.
5. **Throttling & Retries:** All external HTTP calls must be wrapped in `$this->withRetry(...)` and checked against `$this->checkThrottle(...)`.

---

## 🚀 Pull Request Process

When you are ready to submit your code, follow this workflow:

1. **Create a Feature Branch:** Always branch off `main`. 
   ```bash
   git checkout -b feature/your-amazing-feature
   ```
2. **Commit Convention:** Write clear, concise commit messages. We prefer conventional commits (e.g., `feat: Add webhook support`, `fix: Resolve rate limit bug`).
3. **Keep PRs Focused:** Please do not bundle multiple unrelated features into a single PR. Create separate PRs for separate features.
4. **Update Documentation:** If your PR changes the public API (like adding a new method to the `Msg91` facade), you **must** update the `README.md` to document its usage.
5. **Submit:** Push your branch to your fork and submit a PR against our `main` branch. 

*A maintainer will review your code. We may request some changes before it gets merged. Don't worry—this is normal and helps keep the codebase robust!*

---

## 🐛 Reporting Bugs

If you find a bug, please create an issue on GitHub. To help us resolve it quickly, please include:
- Your PHP and Laravel versions.
- The `parvion/laravel-msg91` package version.
- **Steps to reproduce:** A minimal code snippet showing how you triggered the bug.
- **Expected vs Actual behavior.**

---

## 🔒 Security Vulnerabilities

If you discover a security vulnerability within this package, **please do not open a public issue.** 
Instead, send an email directly to Anand Kumar at [anandkumar101002@gmail.com](mailto:anandkumar101002@gmail.com). All security vulnerabilities will be promptly addressed.

---

## 📜 License

By contributing your code, you agree to license your contribution under the terms of the MIT License.