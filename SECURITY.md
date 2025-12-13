# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in NeoCore, please report it by:

1. **Email**: Send details to security@neocore.dev (or create private security advisory on GitHub)
2. **Do NOT** open a public issue for security vulnerabilities
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

## Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Release**: Depends on severity (critical issues prioritized)

## Security Best Practices

When using NeoCore:

1. **Never commit `.env` files** - Contains sensitive credentials
2. **Use prepared statements** - Already implemented in Model class
3. **Validate all input** - Use validation libraries (Respect/Validation)
4. **Use HTTPS** - Always in production
5. **Keep dependencies updated** - Run `composer update` regularly
6. **Enable error logging** - But never display errors in production
7. **Use strong passwords** - For database and admin accounts
8. **Implement rate limiting** - Prevent abuse
9. **Sanitize output** - Prevent XSS attacks
10. **Use CSRF tokens** - For forms (implement in middleware)

## Known Security Considerations

NeoCore is designed with security in mind:

- ✅ PDO with prepared statements (SQL injection protection)
- ✅ No automatic mass assignment (explicit data handling)
- ✅ Request data validation recommended
- ⚠️ CSRF protection not built-in (add via middleware)
- ⚠️ Rate limiting not built-in (add via middleware)
- ⚠️ XSS protection not built-in (sanitize in views)

Thank you for helping keep NeoCore secure! 🔒
