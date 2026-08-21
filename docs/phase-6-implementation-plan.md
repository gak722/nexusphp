# Phase 6: Validation Engine & Form Requests

**Duration:** Week 8

---

## 1. What to Build

Phase 6 provides data validation and input sanitation. It includes a rule-based validation engine, structured error bag normalization, custom rule contracts, and automatic FormRequest validation handlers.

### Core Deliverables:

- **`framework/Validation/Validator.php`** — Main validation runner parsing pipe-separated or array rule definitions.
- **`framework/Validation/RuleInterface.php`** — Contract for custom validation rules.
- **`framework/Validation/ValidationException.php`** — Specialized HTTP 422 exception carrying validation error messages.
- **`framework/Validation/Rules/Required.php`** — Rule asserting non-empty presence.
- **`framework/Validation/Rules/Email.php`** — Rule asserting valid email format.
- **`framework/Validation/Rules/Min.php`** — Rule asserting minimum length or numeric magnitude.
- **`framework/Validation/Rules/Max.php`** — Rule asserting maximum length or numeric magnitude.
- **`framework/Validation/Rules/Unique.php`** — Database unique record validator.
- **`framework/Validation/FormRequest.php`** — Base class for auto-validating HTTP request objects.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Request Integration:** Phase 1's `Request::validate()` delegates directly to `Validator`.
- **Database Integration:** The `Unique` rule utilizes Phase 4's `Connection` / `QueryBuilder` to query table column uniqueness.
- **Exception Interception:** `ValidationException` is caught by Phase 1's `ExceptionHandlerMiddleware`, returning formatted 422 JSON or redirecting back with flashed input/errors.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Validation/ValidationException.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Validation;

   class ValidationException extends \RuntimeException
   {
       public function __construct(public readonly array $errors)
       {
           parent::__construct('The given data was invalid.', 422);
       }
   }
   ```

2. **`framework/Validation/Validator.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Validation;

   class Validator
   {
       protected array $errors = [];

       public function __construct(
           protected array $data,
           protected array $rules
       ) {}

       public static function make(array $data, array $rules): static
       {
           return new static($data, $rules);
       }

       public function validate(): array
       {
           if ($this->fails()) {
               throw new ValidationException($this->errors);
           }
           return array_intersect_key($this->data, $this->rules);
       }

       public function fails(): bool
       {
           $this->errors = [];

           foreach ($this->rules as $field => $ruleset) {
               $rulesList = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;
               $value = $this->data[$field] ?? null;

               foreach ($rulesList as $rule) {
                   $this->validateRule($field, $value, $rule);
               }
           }

           return !empty($this->errors);
       }

       protected function validateRule(string $field, mixed $value, string $rule): void
       {
           $params = [];
           if (str_contains($rule, ':')) {
               [$ruleName, $paramStr] = explode(':', $rule, 2);
               $params = explode(',', $paramStr);
           } else {
               $ruleName = $rule;
           }

           switch ($ruleName) {
               case 'required':
                   if ($value === null || $value === '' || $value === []) {
                       $this->errors[$field][] = "The {$field} field is required.";
                   }
                   break;
               case 'email':
                   if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                       $this->errors[$field][] = "The {$field} field must be a valid email address.";
                   }
                   break;
               case 'min':
                   $min = (int) ($params[0] ?? 0);
                   if ($value !== null && (is_numeric($value) ? $value < $min : strlen((string)$value) < $min)) {
                       $this->errors[$field][] = "The {$field} field must be at least {$min}.";
                   }
                   break;
               case 'max':
                   $max = (int) ($params[0] ?? 0);
                   if ($value !== null && (is_numeric($value) ? $value > $max : strlen((string)$value) > $max)) {
                       $this->errors[$field][] = "The {$field} field must not exceed {$max}.";
                   }
                   break;
           }
       }

       public function errors(): array
       {
           return $this->errors;
       }
   }
   ```

3. **`framework/Validation/FormRequest.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Validation;

   use Nexus\Http\Request;

   abstract class FormRequest extends Request
   {
       abstract public function rules(): array;

       public function authorize(): bool
       {
           return true;
       }

       public function validateResolved(): array
       {
           if (!$this->authorize()) {
               throw new \RuntimeException('Unauthorized request action.', 403);
           }

           $validator = Validator::make($this->json() ?: $this->post, $this->rules());
           return $validator->validate();
       }
   }
   ```

---

## 4. Success Criteria

- [ ] Validator evaluates data using rule pipelines (`required|email|min:8`).
- [ ] Failed validation throws `ValidationException` containing field-keyed error message arrays.
- [ ] Exception handler formats errors as HTTP 422 JSON payloads for API requests.
- [ ] `FormRequest` auto-authorizes and auto-validates inputs prior to controller method invocation.
