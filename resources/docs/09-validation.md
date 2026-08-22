# 09. Validation Engine & Form Requests

NexusPHP includes a strict, zero-dependency validation suite (`Nexus\Validation\Validator`) to sanitize and validate request parameters before processing business logic.

---

## 1. Using the Validator Direct API

Validate data arrays against rule strings:

```php
use Nexus\Validation\Validator;
use Nexus\Validation\ValidationException;

$data = [
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'age' => 25
];

$rules = [
    'name' => ['required', 'min:2', 'max:50'],
    'email' => ['required', 'email'],
    'age' => ['required', 'min:18'],
];

$validator = new Validator();
$validated = $validator->validate($data, $rules);

if ($validator->fails()) {
    $errors = $validator->errors();
    // Returns array of validation error messages
}
```

---

## 2. Available Built-In Rules

| Rule | Parameter | Description |
| :--- | :--- | :--- |
| `required` | None | Value must not be empty or null. |
| `email` | None | Value must be a valid email format. |
| `min:N` | Integer N | String length or integer value must be >= N. |
| `max:N` | Integer N | String length or integer value must be <= N. |
| `unique:table,column` | Table, Col | Checks database table to ensure value does not already exist. |

---

## 3. Form Requests (`Nexus\Validation\FormRequest`)

Form requests encapsulate authorization and validation rules in dedicated classes:

```php
namespace App\Http\Requests;

use Nexus\Validation\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Return true if authenticated user is authorized to perform action
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ];
    }
}
```

Injecting Form Requests in Controller Actions:

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Response;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;

class UserController extends Controller
{
    public function store(StoreUserRequest $request): Response
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => password_hash($validated['password'], PASSWORD_DEFAULT),
        ]);

        return $this->json(['message' => 'User created successfully', 'user' => $user], 201);
    }
}
```

---

## 4. Next Steps

Explore authentication, CSRF, JWT, and encryption features in [10. Security Subsystem](10-security.md).
