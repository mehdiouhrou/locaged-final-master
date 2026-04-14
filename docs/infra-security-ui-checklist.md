## Infra Security and UI Consistency Checklist

### Security headers (production)

Run these checks against the production URL after deployment:

1. Confirm clickjacking protection:
   - `curl -I https://your-domain.tld | rg "X-Frame-Options"`
   - Expected: `X-Frame-Options: DENY`
2. Confirm strict transport policy:
   - `curl -I https://your-domain.tld | rg "Strict-Transport-Security"`
3. Confirm CSP is present:
   - `curl -I https://your-domain.tld | rg "Content-Security-Policy"`
4. Confirm MIME sniff protection:
   - `curl -I https://your-domain.tld | rg "X-Content-Type-Options"`
5. Confirm no PHP leakage:
   - `curl -I https://your-domain.tld | rg "X-Powered-By"`
   - Expected: no output

### Document status visual consistency

In `Documents` and `Documents by category` screens, verify that status badges use the same semantic colors:

- `pending`: warning
- `approved`: success
- `declined/refused`: danger
- `expired`: secondary
- `destroyed`: dark

### Search UX consistency

From header search:

1. Saved search can be created and reused by the same user.
2. Another user cannot see or use someone else's saved search.
3. Query matches are highlighted in result title and OCR snippet.
