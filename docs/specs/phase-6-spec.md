# Copilot Spec: Phase 6 — Validation Engine & Auto-Validating Form Requests

## Objective
Build rule parsing validation engine (`Validator`), custom rule contracts, field error bags, HTTP 422 exception handler integration, and `FormRequest` auto-authorizing wrappers.

## Target Files to Create / Modify
- `framework/Validation/Validator.php`
- `framework/Validation/RuleInterface.php`
- `framework/Validation/ValidationException.php`
- `framework/Validation/Rules/Required.php`
- `framework/Validation/Rules/Email.php`
- `framework/Validation/Rules/Min.php`
- `framework/Validation/Rules/Max.php`
- `framework/Validation/Rules/Unique.php`
- `framework/Validation/FormRequest.php`

---

## Detailed Specifications

### 1. `framework/Validation/Validator.php`
- Accepts `$data` and `$rules` arrays.
- Rule syntax support: `'email' => 'required|email|min:6'` or array of rule instances.
- Standard built-in rule parsers: `required`, `email`, `min`, `max`, `unique`, `confirmed`.
- Throws `ValidationException` carrying `$errors` array if `fails()` is true.

### 2. `framework/Validation/FormRequest.php`
- Extends `Nexus\Http\Request`.
- Defines `authorize(): bool` (default true) and `rules(): array`.
- `validateResolved(): array` verifies authorization and executes validation automatically.

---

## Copilot Validation Rules
- [ ] Error messages must return indexed by field name (`['email' => ['The email field is required.']]`).
- [ ] `ValidationException` must default to HTTP status code 422.
