# Workflow rules before production enforcement

When `GED_ENFORCE_WORKFLOW_RULES=true`, every **status change** on a document is validated against rows in `workflow_rules` for that document’s `department_id`, with optional narrowing by `category_id` (null = applies to all categories in the department).

The table supports these fields for V2 governance:
- `category_id` (optional, wildcard when null)
- `level` (approval stage level)
- `approver_role` (business role label expected to act at this stage)

## Behaviour in code

- If **no** rules exist for the department, enforcement is **skipped** for that department (permissive migration path).
- If **at least one** rule exists for the department, a matching rule is required for each transition (`from_status` → `to_status`), considering rules where `category_id` is null **or** equals the document’s `category_id`.
- `level` and `approver_role` are stored and managed in CRUD so product rules can be tracked explicitly; transition blocking currently remains driven by status transitions (`from_status`/`to_status`) plus department/category matching.

## Before enabling enforcement in production

1. **Inventory** departments and main document flows (pending → approved/declined, declined → pending, approved → archived, etc.).
2. **Backfill** rules per department (and per category if you need different approvers or paths). You can use the UI under workflow rules, import SQL, or run:

   ```bash
   php artisan ged:seed-default-workflow-rules
   ```

   This command creates **department-wide** rules (`category_id` null) for common transitions only where a row does not already exist. It does not remove or overwrite custom rules.

3. **Smoke-test** transitions for each department (and a sample category-specific rule if you use `category_id`).
4. Set `GED_ENFORCE_WORKFLOW_RULES=true` in `.env` and deploy; run queue workers / Horizon as usual.

## Rollback

Set `GED_ENFORCE_WORKFLOW_RULES=false` to disable validation without deleting data.
