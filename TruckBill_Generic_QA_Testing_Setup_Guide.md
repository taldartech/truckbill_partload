**TRUCKBILL**

# **Generic QA Testing Setup** **& Implementation Guide**

**Pest / PHPUnit  •  Xdebug  •  Playwright  •  GitHub Actions**

A reusable team guide for Laravel web applications and APIs  
on macOS, Linux, and Windows.

Version 1.0  \|  Team implementation document

# **1\. Purpose and scope**

This document defines a generic testing setup for Laravel-based web applications and APIs. It is intended to be reusable across multiple projects. It describes the recommended tools, environment setup, test types, database safety, coding conventions, CI workflow, and phased adoption plan.

* Support local development on macOS, Linux, and Windows.  
* Use Pest or PHPUnit for automated PHP, Laravel feature, integration, and API tests.  
* Use Xdebug for local PHP debugging and code coverage where required.  
* Use Playwright for browser-based UI and end-to-end tests.  
* Use GitHub Actions to run repeatable quality checks on pushes and pull requests.  
* Keep test environments isolated from production and make test results reproducible.

| Generic baseline  This document does not assume any specific repository version, existing test count, current dependency state, project-specific audit result, or existing environment configuration. Verify each repository before applying commands. |
| :---- |

# **2\. Recommended stack**

| Tool | Recommendation | Purpose |
| :---- | :---- | :---- |
| Pest or PHPUnit | Required; choose based on the repository baseline | Unit, feature, integration, and HTTP/API tests. Preserve existing working tests and avoid unnecessary framework migrations. |
| Laravel testing utilities | Use | HTTP assertions, auth helpers, factories, database traits, and fakes for mail, events, queues, and storage. |
| Xdebug | Recommended for development | PHP breakpoints, stack traces, debugging, and code coverage. |
| Playwright Test \+ TypeScript | Use for UI/E2E | Automated browser journeys, form validation, navigation, role-based UI, and regression checks. |
| GitHub Actions | Use for CI | Run automated tests on pushes and pull requests; retain useful failure diagnostics. |
| MySQL or the application's supported database | Use an isolated test database | Realistic integration tests and verification of persisted data. |
| PCOV | Optional | Coverage-only alternative when CI coverage speed is important; not a replacement for Xdebug debugging. |
| Postman | Optional | Manual API exploration and troubleshooting; avoid duplicating all automated regression tests. |
| PHPStan / Larastan | Later phase | Static analysis to identify type and code-quality issues. |
| Docker Compose | Optional | Standardize local services such as the database when useful. |

| Framework decision  If a project already uses PHPUnit, keep it initially. Add Pest only after checking compatibility and agreeing that its syntax improves team productivity. Pest can run on top of PHPUnit; it is not necessary to migrate every test to use it. |
| :---- |

# **3\. Testing layers**

| Layer | Tool | Example |
| :---- | :---- | :---- |
| Unit | Pest / PHPUnit | A calculation or domain rule returns the expected result for normal and boundary inputs. |
| Feature / integration | Pest / PHPUnit \+ Laravel | A request creates the expected database records and triggers the intended application behavior. |
| API | Laravel HTTP tests | Authentication, response schema, validation, authorization, status codes, and side effects. |
| End-to-end | Playwright | A user logs in, completes a workflow, saves data, and sees the correct result. |
| Regression | All relevant layers | A previously fixed defect does not return after a code change. |
| Compatibility | CI matrix \+ Playwright browser projects | Selected checks run on supported OS/browser combinations. |
| Security and isolation | Targeted automated tests \+ review | A user cannot access or modify records outside their permissions. |
| Performance / accessibility | Specialized tools and manual checks as needed | Critical endpoint behavior under load and usable keyboard/focus/error behavior. |

Start with unit, feature/integration, API, authorization, and a small number of critical E2E tests. Add performance, accessibility automation, and specialist security checks as the suite matures.

# **4\. Repository audit before installation**

Run these commands from each repository root. They are intended for discovery; review output before sharing and never expose secrets.

git status \--short  
php \-v  
composer \--version  
php artisan \--version  
composer show \--locked phpunit/phpunit  
composer show \--locked pestphp/pest  
php \-m  
php artisan test \--help  
git ls-files tests  
git ls-files phpunit.xml phpunit.xml.dist composer.json composer.lock  
git ls-files ".github/workflows/\*"  
composer run-script \--list

Record the actual PHP/Laravel versions, locked testing dependencies, existing test structure, test commands, and CI configuration. If a command fails because a package is absent, record the result; do not immediately install or upgrade dependencies.

## **4.1 Preserve the baseline**

* Check git status and preserve existing work.  
* Inspect the PHPUnit/Pest configuration and base test class.  
* Inspect database testing traits, factories, seeders, and CI workflows.  
* Verify the test database target before executing tests.  
* Run the existing suite only after test-database safety is confirmed, then record pass/fail results.

# **5\. Cross-platform setup**

## **5.1 Prerequisites**

* Git and repository access.  
* PHP and required extensions matching the repository's composer.json.  
* Composer compatible with the project.  
* Node.js LTS and npm for Playwright.  
* The application's supported database engine/version, installed locally or provided by a container.  
* A code editor/IDE with PHP and TypeScript support.

Use the repository's lock files and documented version requirements. Do not assume all projects share the same PHP, Laravel, Node, or database versions. On Windows, PowerShell or Git Bash can be used; executable paths may differ from macOS/Linux.

## **5.2 Install project dependencies**

\# PHP dependencies; use the committed lock file  
composer install

\# JavaScript dependencies; only if package.json exists  
npm ci

Do not run composer update as part of initial setup unless a dependency upgrade is an explicit, reviewed task. Do not initialize a new Playwright package over an existing package.json without first reviewing it.

# **6\. Safe test database and environment**

* Create a dedicated test database per application/repository. Never point tests to production.  
* Use test-only credentials with access limited to the test database.  
* Configure APP\_ENV=testing and an explicit test DB connection/database in phpunit.xml and/or .env.testing as appropriate for the application.  
* Use factories and deterministic fixtures rather than production records.  
* Fake mail, SMS, payment gateways, storage, queues, and third-party APIs in ordinary tests.  
* Use a sandbox only for separate provider integration tests.  
* Do not enable parallel tests until the database isolation and cleanup strategy is proven.  
* Do not run destructive commands such as db:wipe, migrate:fresh, \--recreate-databases, or parallel tests until the test target is confirmed.

## **6.1 Example test environment**

\# Illustrative .env.testing values only  
APP\_ENV=testing  
CACHE\_STORE=array  
SESSION\_DRIVER=array  
QUEUE\_CONNECTION=sync  
MAIL\_MAILER=array

DB\_CONNECTION=mysql  
DB\_HOST=127.0.0.1  
DB\_PORT=3306  
DB\_DATABASE=application\_testing  
DB\_USERNAME=application\_test  
DB\_PASSWORD=local\_test\_password

Replace example values with the project's approved local test configuration. Never commit real credentials. A committed .env.testing.example should contain placeholders only. Check the configuration file and environment precedence before running tests.

# **7\. Writing maintainable tests**

## **7.1 Test case structure**

| Element | Guidance |
| :---- | :---- |
| Title | Describe the behavior, e.g. 'Unauthenticated user cannot access protected endpoint'. |
| Arrange | Create required users, roles, records, and configuration. |
| Act | Call the function, submit the HTTP request, or perform browser actions. |
| Assert | Verify response, calculations, persisted state, permissions, and side effects. |
| Negative cases | Invalid inputs, missing permission, duplicate requests, and failure paths. |
| Isolation | No dependency on test order, uncontrolled external services, or shared mutable data. |

## **7.2 PHPUnit example**

\<?php

namespace Tests\\Unit;

use PHPUnit\\Framework\\TestCase;

final class PercentageCalculationTest extends TestCase  
{  
    public function test\_calculates\_percentage\_for\_explicit\_inputs(): void  
    {  
        \$amount \= 10000.00;  
        \$ratePercent \= 1.00;

        \$result \= \$amount \* (\$ratePercent / 100);

        \$this-\>assertEquals(100.00, \$result);  
    }  
}

This is a syntax example, not a recommended way to test real business logic by duplicating the calculation in the test. For production tests, call the application's actual service and verify its expected behavior, including rounding and boundary rules.

## **7.3 Laravel HTTP/API example**

public function test\_unauthenticated\_user\_cannot\_access\_protected\_api(): void  
{  
    \$response \= \$this-\>getJson('/api/example-protected-resource');

    \$response-\>assertUnauthorized();  
}

Use an actual protected route and the status code appropriate to the application's authentication middleware. Browser requests may redirect to login instead of returning 401\.

# **8\. Xdebug setup and coverage**

Xdebug is useful for stepping through PHP execution, inspecting variables, understanding stack traces, and collecting coverage. Install it for the PHP runtime used by the CLI/IDE. Different PHP versions or installations can load different configuration files.

php \-v  
which php  
php \--ini  
php \-m  
php \--ri xdebug

On Windows, use the equivalent executable/configuration commands for the active PHP installation. Follow the current official Xdebug installation instructions for the PHP version and operating system. Do not copy a compiled extension from another PHP version.

## **8.1 Coverage**

\# When a compatible Xdebug coverage driver is installed/configured  
XDEBUG\_MODE=coverage php artisan test \--coverage

Use the appropriate environment-variable syntax for the developer's shell. Coverage mode is separate from debugging mode. Enable coverage only for coverage runs to avoid unnecessary overhead. PCOV is an optional coverage-only driver for environments where debugging is not needed.

## **8.2 Coverage policy**

* Establish a baseline before setting a global coverage percentage gate.  
* Prioritize critical business rules, authorization, data integrity, and failure paths.  
* Review coverage of changed code and high-risk areas, not only a project-wide percentage.  
* A covered line is not proof of correct behavior; include meaningful assertions and edge cases.

# **9\. Playwright setup**

Playwright Test uses TypeScript and browser automation. Keep it in a dedicated directory or approved shared QA repository. Review existing package.json before initializing.

\# If the repository has no existing Playwright setup  
npm init playwright@latest

\# Install browser binaries for the pinned Playwright package  
npx playwright install

\# Run browser tests and open the report  
npx playwright test  
npx playwright show-report

## **9.1 Browser test standards**

* Configure baseURL and credentials through environment variables.  
* Use dedicated test users and data with minimum necessary permissions.  
* Prefer accessible locators such as getByRole and getByLabel; use data-testid where appropriate.  
* Use web-first assertions instead of fixed sleeps.  
* Keep tests independent and clean up created records.  
* Capture traces/screenshots on failure; keep artifacts free of sensitive data.  
* Start with Chromium smoke tests, then add selected Firefox/WebKit coverage.

## **9.2 Example Playwright test**

import { test, expect } from '@playwright/test';

test('login page is reachable', async ({ page }) \=\> {  
  await page.goto('/login');  
  await expect(  
    page.getByRole('heading', { name: /log in\|sign in/i })  
  ).toBeVisible();  
});

This assumes baseURL is configured and the page contains a matching accessible heading. Adapt it to the actual application's UI.

# **10\. GitHub Actions**

Run the PHP suite on pull requests and pushes. Begin with Linux CI, then add macOS/Windows matrix jobs where platform-specific behavior or team requirements justify them. Keep browser tests in a separate job once application boot and test-data setup are reliable.

name: Tests

on:  
  pull\_request:  
  push:  
    branches: \[main\]

jobs:  
  php-tests:  
    runs-on: ubuntu-latest  
    steps:  
      \- uses: actions/checkout@v4

      \- name: Set up PHP  
        uses: shivammathur/setup-php@v2  
        with:  
          php-version: '8.3'  
          coverage: none

      \- name: Install dependencies  
        run: composer install \--no-interaction \--prefer-dist

      \- name: Configure test environment  
        run: \|  
          cp .env.example .env  
          php artisan key:generate  
        \# Configure test-only DB values and services for this app.

      \- name: Run tests  
        env:  
          APP\_ENV: testing  
          \# Add explicit test-only database variables here.  
        run: php artisan test

| Template warning  This is an adaptable example, not a drop-in workflow. Set the PHP version and required extensions to match the repository, configure a test-only database service and credentials, and ensure no production credentials or network targets are available to the job. Review third-party Actions versions and pin them according to team policy. |
| :---- |

## **10.1 Suggested quality gates**

| Trigger | Checks |
| :---- | :---- |
| Pull request | PHP test suite, optional formatting/static analysis, critical browser smoke tests when stable. |
| Push to main | Required suite plus broader regression checks. |
| Scheduled run | Expanded browser matrix, coverage, longer workflows, and selected performance checks. |
| Before release | Full regression, critical authorization/data-integrity checks, migration checks, and manual exploratory sign-off. |

Use GitHub repository branch protection to require appropriate checks. Do not treat retries as a way to conceal flaky tests; diagnose the cause.

# **11\. Test reporting and diagnostics**

* PHP failures should include the test name, expected/actual values, and stack trace.  
* Playwright failures should retain trace and screenshot artifacts where appropriate.  
* Keep test reports and logs free of passwords, tokens, and customer data.  
* Record flaky tests and assign an owner; quarantine only through an explicit, time-limited process.  
* Track baseline pass rate, runtime, failure causes, and coverage of high-risk behavior.

# **12\. Phased implementation plan**

| Phase | Work | Exit criteria |
| :---- | :---- | :---- |
| 1\. Audit | Inspect versions, dependencies, test config, database setup, and CI in each repository. | A compatibility and gap list exists for every project. |
| 2\. Safe baseline | Verify isolated test DB; run existing tests and classify failures. | Baseline results recorded and repeatable. |
| 3\. Core PHP tests | Add/refine unit, feature, API, and authorization tests for high-risk behavior. | Critical tests pass locally. |
| 4\. Debugging and coverage | Install Xdebug; configure local debugging and coverage report. | Team can diagnose failures and inspect coverage. |
| 5\. Browser smoke suite | Add Playwright and one critical user journey. | E2E test is stable and documented. |
| 6\. CI enforcement | Add GitHub Actions, safe DB service, reports, and required checks. | PRs receive automated test results. |
| 7\. Expand and measure | Add broader workflows, OS/browser matrix, performance/accessibility/security checks as needed. | Owners and measurable goals are agreed. |

# **13\. Definition of done**

* The test runs on the documented local environment and in CI.  
* The test uses isolated test data and cannot access production.  
* The test has a meaningful assertion that would fail if the intended defect existed.  
* Relevant invalid-input, authorization, and failure cases are covered according to risk.  
* The test does not depend on execution order or uncontrolled external services.  
* Failure output is understandable and contains no sensitive data.  
* Dependencies and setup instructions are documented and reviewed.

# **14\. Troubleshooting**

| Issue | What to check |
| :---- | :---- |
| Database connection failure | Test environment variables, host/port, DB grants, database existence, and service health. |
| Coverage driver missing | Xdebug/PCOV is installed for the same PHP CLI and loaded by the active configuration. |
| Different local and CI results | PHP/extensions, database version, timezone, case-sensitive paths, environment, and lock files. |
| Order-dependent tests | Shared records, missing cleanup, global state, and reliance on another test. |
| Playwright browser missing | Install browsers for the locked Playwright version and required Linux dependencies. |
| Flaky browser tests | Use web-first assertions, stable locators, isolated test data, and trace diagnostics instead of fixed sleeps. |
| Dependency mismatch | Review composer.lock/package-lock.json and required runtime versions before upgrading. |

# **15\. Official documentation**

* **Laravel testing:** https\://laravel.com/docs/testing  
* **Laravel database:** https\://laravel.com/docs/database  
* **PHPUnit documentation:** https\://phpunit.de/documentation.html  
* **Pest documentation:** https\://pestphp.com/docs  
* **Xdebug installation:** https\://xdebug.org/docs/install  
* **Xdebug documentation:** https\://xdebug.org/docs  
* **Playwright introduction:** https\://playwright.dev/docs/intro  
* **Playwright CI:** https\://playwright.dev/docs/ci  
* **GitHub Actions documentation:** https\://docs.github.com/actions

| Team rollout rule  Audit each repository, protect the test database, establish a reliable baseline, and then add tools. Keep the strategy consistent while adapting exact versions and configuration to each project's actual requirements. |
| :---- |

