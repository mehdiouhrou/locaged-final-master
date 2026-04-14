# Audit legal evidence v2

This document defines the operational procedure to extract legal-grade evidence from audit logs.

## Objectives

- Export a signed, self-contained evidence package.
- Preserve package in immutable storage (WORM/Object Lock).
- Provide repeatable verification steps for legal or compliance requests.

## Package format

Each extraction creates one ZIP package containing:

- `manifest.json`: extraction metadata (actor, filters, counts, hash).
- `signature.json`: signature over the manifest.
- `document_logs.ndjson`: document-related audit entries.
- `authentication_logs.ndjson`: authentication audit entries.
- `README.txt`: quick verification instructions.

## Signature models

- Preferred: `rsa-sha256` when `AUDIT_EVIDENCE_PRIVATE_KEY` is configured.
- Fallback: `hmac-sha256` using `AUDIT_HMAC_KEY`.

## Immutable archive (WORM)

Set the following environment variables:

- `AUDIT_WORM_ENABLED=true`
- `AUDIT_WORM_DISK=s3`
- `AUDIT_WORM_PREFIX=audit-evidence/`
- `AUDIT_WORM_MODE=COMPLIANCE` (or GOVERNANCE)
- `AUDIT_WORM_RETENTION_YEARS=10` (or policy value)

Bucket prerequisites:

- S3 bucket must have Object Lock enabled at creation time.
- Versioning must be enabled.
- IAM policy must allow `s3:PutObjectRetention` and object write.

## Extraction procedure ("preuve")

1. Validate integrity chain on source:
   - `php artisan audit:verify-integrity`
2. Generate signed package:
   - UI: `Utilisateurs > Audit > Export preuve légale (signée)`
   - CLI: `php artisan audit:extract-proof <actor_user_id> --log-type=all --date-from=YYYY-MM-DD --date-to=YYYY-MM-DD`
3. Store extraction ticket:
   - Request ID, operator, timestamp, filters, package filename.
4. Verify package:
   - Check signature in `signature.json`.
   - Recompute hash over NDJSON payload and compare with `manifest.records_hash_sha256`.
5. Preserve:
   - Confirm immutable archive status (WORM path, retain-until).
   - Keep download copy only for legal handover.

## Chain of custody checklist

- Identify legal requester and scope.
- Record operator identity and access rights.
- Freeze filters before extraction.
- Perform extraction once and avoid manual edits.
- Transfer package via secure channel.
- Record transfer hash and recipient acknowledgment.
