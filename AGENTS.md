# Repository Guidelines

## Project Structure & Module Organization
- Root directory contains HTML pages (e.g., index.html, aboutus.html) and PHP scripts (e.g., addmission.php, contact.php) for server-side processing.
- `assets/` directory holds static assets such as images, CSS, and JavaScript.
- Supabase-related files: Supabase_Migration_Guide.md (documentation), supabase_rest_config.php (configuration), and Supabase_Schema.sql (database schema).

## Build, Test, and Development Commands
- No build system is configured. To run locally:
  - Start a PHP server: php -S localhost:8000 (ensure PHP is installed).
  - Supabase services must be configured via environment variables (see .env.example); the Supabase project must be set up separately.
- Testing is not automated; manual testing via browser and PHP CLI is expected.

## Coding Style & Naming Conventions
- HTML: Use lowercase tags, indent with 2 spaces, and follow semantic structure.
- PHP: Follow PSR-12 standards (indentation 4 spaces, camelCase for variables, PascalCase for classes).
- File naming: Use lowercase with hyphens for HTML (e.g., contact-us.php) and underscores for PHP (e.g., supabase_rest_config.php) to match existing patterns.
- CSS/JavaScript: Not present in assets; if added, follow existing conventions in the assets folder.

## Testing Guidelines
- No testing framework is currently in place.
- Recommend adding PHPUnit for backend tests and using a tool like Cypress or Selenium for frontend tests.
- Test naming: Use descriptive names ending in Test.php for PHPUnit.
- Coverage: Aim for 80%+ on critical paths.

## Commit & Pull Request Guidelines
- Commit messages: Use imperative mood, short summary (50 chars or less), and optional body.
- Pull requests: Must include a clear description, link to any related issues, and screenshots if UI changes are involved.
- Ensure code passes any existing linting checks (to be added) and manual testing.

## Additional Notes
- Supabase configuration: Set environment variables `SUPABASE_URL` and `SUPABASE_KEY` (see .env.example). Refer to Supabase_Migration_Guide.md for setup.
- Security: Never commit sensitive credentials; use environment variables or secure vaults.

