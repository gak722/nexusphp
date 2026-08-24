# Validation & Data Binding

Ensuring incoming data is valid and correctly formatted is a critical component of building secure applications. NexusPHP provides a comprehensive, highly extensible validation engine that supports nested array validation (dot-notation), form requests, and robust error handling.

> [!IMPORTANT]
> This documentation strictly reflects the native `Nexus\Validation` subsystem. NexusPHP intentionally omits magical attribute-based validation (`#[Validate]`) or complex automatic model parameter binding in favor of strict, declarative array validation and manual explicit entity hydration, ensuring maximum performance and code clarity.

---

## Basic Validation

The primary validation engine is the `Nexus\Validation\Validator` class. You can manually instantiate it to validate any array of data.

```php
use Nexus\Validation\Validator;
use Nexus\Validation\ValidationException;

$data = [
    'email' => 'user@example.com',
    'age' => 25
];

$rules = [
    'email' => 'required|email|max:255',
    'age' => 'required|integer|min:18'
];

$validator = Validator::make($data, $rules);

if ($validator->fails()) {
    $errors = $validator->errors(); // Returns ValidationErrors instance
} else {
    $validatedData = $validator->validated();
}
```

If you prefer to let the framework handle exceptions automatically, you can call `validate()`. If validation fails, it throws a `ValidationException` (which the framework's Exception Handler catches and converts to a `422 Unprocessable Entity` JSON or Redirect response).

```php
$validatedData = $validator->validate();
```

---

## Form Request Validation

Instead of cluttering your controllers with manual validation logic, NexusPHP supports **Form Requests**. Form requests are custom request classes that encapsulate their own validation and authorization logic.

### Creating a Form Request

Create a class that extends `Nexus\Validation\FormRequest`. You must implement the `rules()` method, and optionally override `authorize()`.

```php
namespace App\Http\Requests;

use Nexus\Validation\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Example: Only allow if the user is authenticated
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min_length:8|confirmed'
        ];
    }
}
```

### Using Form Requests

When you inject a `FormRequest` into a controller method, NexusPHP automatically validates the incoming data (`query`, `post`, and `json` payloads combined) before your controller logic executes.

```php
public function store(StoreUserRequest $request)
{
    // If we reach here, the request is authorized and validated!
    
    // Retrieve only the data that was explicitly defined in the rules
    $validated = $request->validateResolved(); 
    
    // Data Binding (Hydration) is explicit:
    $user = new User();
    $user->name = $validated['name'];
    $user->email = $validated['email'];
    // ...
}
```

---

## Validation Rules

NexusPHP defines rules using a pipe-delimited string syntax or as an array of `RuleInterface` instances.

```php
// Pipe syntax
'username' => 'required|string|min_length:3'

// Array syntax (useful for custom rule instances)
'username' => ['required', 'string', new CustomRule()]
```

### Modifiers

- `nullable`: Allows the field to be `null` or empty without failing other rules.
- `sometimes`: Only validates the field if it is actually present in the data array.
- `bail`: Stops running further validation rules on the attribute after the first failure.

### Nested Data & Dot Notation

NexusPHP natively supports wildcard dot-notation (`*`) for validating nested arrays or JSON payloads.

```php
$rules = [
    'person' => 'required|array',
    'person.name' => 'required|string',
    
    // Validate an array of items
    'items' => 'required|array',
    'items.*.id' => 'required|integer',
    'items.*.price' => 'required|numeric|min:0'
];
```

### Available Rules

Here is a list of the core rules bundled with NexusPHP natively:

#### Presence Rules
- `required`: Field must be present and not empty.
- `required_if:field,value`: Required if another field equals a specific value.
- `required_unless:field,value`: Required unless another field equals a specific value.
- `required_with:foo,bar`: Required if *any* of the specified fields are present.
- `required_without:foo,bar`: Required if *any* of the specified fields are missing.

#### Type Rules
- `string`: Must be a string.
- `integer` / `int`: Must be an integer.
- `numeric`: Must be a number (float or int).
- `boolean` / `bool`: Must be a boolean (`true`, `false`, `1`, `0`, `'on'`, `'yes'`).
- `array`: Must be a PHP array.
- `object`: Must be an object (or associative array).

#### Sizing & Length Rules
- `length:value`: String length or Array count must exactly equal the value.
- `min_length:value` / `max_length:value`: String length or Array count constraints.
- `min:value` / `max:value`: Numeric boundaries. If the input is a string or array, it checks length/count instead.
- `between:min,max`: Must be between min and max inclusive.

#### Format Rules
- `email`: Must be a valid email format.
- `url`: Must be a valid URL.
- `uuid`: Must be a valid UUID.
- `ip`, `ipv4`, `ipv6`: Must be a valid IP address.
- `json`: Must be a valid JSON string.
- `date`: Must be a valid date (string parsed by `strtotime` or `DateTimeInterface`).
- `datetime`: Must strictly match `Y-m-d H:i:s`.
- `date_format:format`: Must match the provided format (e.g., `date_format:Y-m`).

#### Comparison Rules
- `same:field`: Must perfectly match the value of another field.
- `different:field`: Must not match the value of another field.
- `confirmed`: Must have a matching `{field}_confirmation` attribute present in the data.
- `in:a,b,c`: Value must exist in the given list.
- `not_in:a,b,c`: Value must not exist in the given list.

#### State Rules
- `accepted`: Must be `yes`, `on`, `1`, or `true` (perfect for Terms of Service checkboxes).

#### Database Rules
- `unique:table,column,except_id,id_column`: Fails if the value already exists in the given database table.
- `exists:table,column`: Fails if the value does *not* exist in the database table.

---

## Validation Errors

When validation fails, the `Validator` generates a `Nexus\Validation\ValidationErrors` object.

### Accessing Errors

```php
$errors = $validator->errors();

// Check if a specific field has errors (supports wildcards!)
if ($errors->has('items.*.price')) { ... }

// Get the first error message for a field
$msg = $errors->first('email');

// Get all error messages for a field
$emailErrors = $errors->get('email');

// Get a flat array of every single error message
$allErrors = $errors->all();
```

### Customizing Error Messages

You can provide custom error messages to the Validator upon instantiation, or define them in your Form Request.

```php
$messages = [
    'email.required' => 'We really need your email address!',
    'email.email' => 'That does not look like a valid email.',
    'age' => 'Something is wrong with your age.' // Fallback for all rules on 'age'
];

$validator = Validator::make($data, $rules, $messages);
```

You can also customize the display name of the attribute (so it doesn't say "The first_name field is required"):

```php
$attributes = [
    'first_name' => 'First Name'
];

$validator = Validator::make($data, $rules, [], $attributes);
```

---

## Custom Validation Rules

NexusPHP makes it extremely simple to define custom validation rules via the `Nexus\Validation\RuleRegistry`.

### Using Closures

For simple, single-use rules, you can register a closure globally (e.g., in a Service Provider):

```php
use Nexus\Validation\Validator;
use Nexus\Validation\ValidationContext;

Validator::extend('uppercase', function (string $attribute, mixed $value, ValidationContext $context) {
    return is_string($value) && strtoupper($value) === $value;
});
```

You can then use it in your arrays: `'name' => 'required|uppercase'`.

### Using Rule Classes

For reusable logic, create a class that implements `Nexus\Validation\RuleInterface`.

```php
namespace App\Rules;

use Nexus\Validation\RuleInterface;
use Nexus\Validation\ValidationContext;

class StrongPassword implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        return preg_match('/[A-Z]/', $value) && preg_match('/[0-9]/', $value);
    }

    public function message(string $attribute): string
    {
        return "The {$attribute} must contain at least one uppercase letter and one number.";
    }
}
```

You can then instantiate it directly in your rule arrays:

```php
'password' => ['required', new \App\Rules\StrongPassword()]
```

---

## Next Steps

With your data securely validated, learn how to process and persist it:

- [Requests & Responses](requests-responses.md): Understand the HTTP Request lifecycle.
- [Controllers](controllers.md): See how to inject Form Requests.
- [Models & ORM](orm.md): Learn how to persist your validated data.
