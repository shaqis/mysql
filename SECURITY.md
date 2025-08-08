# Security Overview

This document summarizes the current security controls and recommended practices for this MySQL-to-CSV export utility.

## Implemented Controls

### Authentication & Authorization
- Single user credential (username + bcrypt hashed password) loaded from environment variables (`APP_USER`, `APP_PASS_HASH`).
- Password verification via `password_verify()`; outdated hashes logged using `password_needs_rehash()` (manual rotation required since hash stored in environment).
- Session fixation mitigation: session ID regenerated on successful login.
- Session fingerprint (user-agent hash) bound to session to reduce hijack impact.

### Session Hardening
- Strict mode enabled (`session.use_strict_mode=1`).
- Secure cookie flags: `HttpOnly`, `SameSite=Strict`, `Secure` (when HTTPS detected).
- 30-minute idle timeout + periodic regeneration.
- CSRF tokens (random 32-byte hex) for login form and one-time token for export confirmation POST.

### Export Endpoint Protection
- Export changed from direct GET to POST-only workflow with confirmation form and CSRF token.
- Optional allowlist of tables using `EXPORT_TABLES` (comma-separated); `EXPORT_TABLE` must appear in allowlist if set.
- Table name validated against strict regex `^[a-zA-Z0-9_]+$`.
- CSV output streamed (unbuffered query) to mitigate memory pressure on large datasets.

### CSV Injection Mitigation
- Each cell sanitized: prefixes dangerous characters (`=`, `+`, `-`, `@`) or leading control chars (TAB/CR/LF) with a single quote to prevent spreadsheet formula execution.

### Headers & Output Controls
- Security headers: `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` added on relevant pages.
- Minimal output on errors; detailed messages logged via `error_log()` only.

### Rate Limiting (Login)
- In-memory (session) counter: max 10 failed attempts per 15-minute window before temporary blocking.
- Micro sleep delay on blocked attempts to slow brute force.

## Environment Variables
| Variable | Purpose | Required |
|----------|---------|----------|
| `DB_HOST` | Database host | Yes |
| `DB_NAME` | Database name | Yes |
| `DB_USER` | Database user | Yes |
| `DB_PASS` | Database password | Yes |
| `DB_CHARSET` | Charset (default `utf8mb4`) | No |
| `APP_USER` | Login username | Yes |
| `APP_PASS_HASH` | Bcrypt password hash (generate with `php make_hash.php <password>`) | Yes |
| `EXPORT_TABLE` | Table to export | Yes |
| `EXPORT_TABLES` | Comma-separated allowlist (optional but recommended) | No |

## Operational Recommendations
1. Enforce HTTPS; behind a reverse proxy ensure `Secure` cookies are preserved.
2. Restrict `.env` file permissions (e.g., `chmod 600`) and keep outside web root if using a different document root.
3. Run PHP with `display_errors=Off` in production; log errors to a protected location.
4. Rotate credentials and password hash periodically; when rehash is recommended, regenerate and update `APP_PASS_HASH`.
5. Add persistent (e.g., file or database) logging for:
   - Successful logins
   - Failed login attempts
   - Export events (timestamp, table, row count)
6. Consider integrating an application firewall or reverse proxy rate limiting for stronger brute-force mitigation.
7. Optionally implement IP-based throttling or fail2ban integration parsing logs.
8. Monitor file integrity (e.g., with tripwire/AIDE) if deployed in sensitive environments.

## Potential Future Enhancements
- Multi-user system with role-based access control.
- OAuth/OpenID Connect integration instead of static credentials.
- Download scoping (column filtering, row-level filtering) with parameter validation.
- Hash-based signed URLs with short expiry for one-time export links.
- Pagination / chunked export (e.g., splitting large datasets into parts, optional gzip compression).
- Encryption at rest for temporary export data if any staging ever added.

## Threat Considerations
| Threat | Mitigation |
|--------|------------|
| Session hijack | Secure cookies, fixation prevention, fingerprint binding, idle timeout |
| CSRF export | POST + one-time token confirmation |
| Credential stuffing | Session-based rate limiting (augment with IP-based) |
| SQL injection (table) | Strict regex + allowlist |
| Spreadsheet formula injection | CSV field sanitization |
| Sensitive config exposure | Use environment variables, secure `.env` permissions |

## Usage Notes
- Always generate password hash with the provided `make_hash.php` script and set `APP_PASS_HASH`. Never store plaintext passwords.
- After changing security-related environment variables, restart PHP-FPM / web server to ensure new values load (depending on process manager caching).

## Incident Response Basics
- Revoke access: change `APP_PASS_HASH`, rotate DB credentials, invalidate existing sessions (restart PHP / clear session storage).
- Review logs for unusual export frequency or size.
- If compromise suspected, rotate all secrets and review server integrity.

---
For questions or to propose enhancements, create an issue or pull request referencing this document.
