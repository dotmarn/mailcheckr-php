# Contributing to MailCheckr for Laravel

Thanks for helping improve the package. Bug reports, documentation fixes, tests, and code changes are welcome.

## Before opening a pull request

1. Search existing issues and pull requests. For a larger change, open an issue first to discuss the API and compatibility impact.
2. Keep the change focused and add PHPUnit coverage for changed behavior. Use Laravel's HTTP fake for API calls; tests must not require a MailCheckr account or live API key.
3. Run `composer test` and `composer validate --strict` locally. The pull request workflow checks supported Laravel and PHP versions.
4. Describe the behavior changed, why it changed, and how you tested it in the pull request.

Do not commit API keys, webhook secrets, customer email addresses, or other private data. If you find a security vulnerability, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

The `main` branch is protected. Submit changes through a pull request and wait for the required checks and review before merging.
