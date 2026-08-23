<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Database\Connection;
use Nexus\Database\Model;
use Nexus\Http\Request;
use Nexus\Validation\RuleRegistry;
use Nexus\Validation\Validate;
use Nexus\Validation\ValidationErrors;
use Nexus\Validation\ValidationException;
use Nexus\Validation\Validator;

class SampleUserModel extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'age', 'is_admin'];

    #[Validate(['required', 'string', 'min_length:3'])]
    public string $name;

    #[Validate(['required', 'email'])]
    public string $email;
}

class ValidationTest
{
    public function testBasicRulesAndErrors(): void
    {
        $data = [
            'name' => 'Jo',
            'email' => 'invalid-email',
            'age' => 'twenty',
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min_length:3'],
            'email' => ['required', 'email'],
            'age' => ['nullable', 'integer'],
        ]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();

        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('email'));
        $this->assertTrue($errors->has('age'));
        $this->assertEquals("The name field must be at least 3 characters.", $errors->first('name'));
        $this->assertCount(3, $errors->toArray());
    }

    public function testAllValidationRules(): void
    {
        $data = [
            'str' => 'hello',
            'int' => 42,
            'num' => '12.34',
            'bool' => 'true',
            'arr' => [1, 2, 3],
            'url' => 'https://example.com',
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'date' => '2026-08-23',
            'regex' => 'ABC123',
            'in' => 'active',
            'confirmed' => 'pass123',
            'confirmed_confirmation' => 'pass123',
            'ip' => '192.168.1.1',
            'json' => '{"foo":"bar"}',
        ];

        $validator = Validator::make($data, [
            'str' => ['string', 'min_length:2', 'max_length:10'],
            'int' => ['integer', 'between:1,100'],
            'num' => ['numeric'],
            'bool' => ['boolean'],
            'arr' => ['array', 'length:3'],
            'url' => ['url'],
            'uuid' => ['uuid'],
            'date' => ['date', 'date_format:Y-m-d'],
            'regex' => ['regex:/^[A-Z]{3}[0-9]{3}$/'],
            'in' => ['in:active,pending'],
            'confirmed' => ['confirmed'],
            'ip' => ['ip', 'ipv4'],
            'json' => ['json'],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testNestedAndWildcardValidation(): void
    {
        $data = [
            'address' => [
                'street' => 'Main St',
                'city' => 'Mumbai',
            ],
            'phones' => [
                ['type' => 'mobile', 'number' => '12345'],
                ['type' => 'home', 'number' => ''],
            ],
        ];

        $validator = Validator::make($data, [
            'address.city' => ['required', 'string'],
            'phones.*.number' => ['required', 'numeric'],
        ]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();

        $this->assertFalse($errors->has('address.city'));
        $this->assertTrue($errors->has('phones.1.number'));
        $this->assertTrue($errors->has('phones.*.number'));
    }

    public function testCustomRuleAndMessages(): void
    {
        Validator::extend('even', function ($attribute, $value) {
            return is_numeric($value) && ((int)$value % 2 === 0);
        });

        $validator = Validator::make(['score' => 5], [
            'score' => ['required', 'even'],
        ], [
            'score.even' => 'Score must be an even number!',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('Score must be an even number!', $validator->errors()->first('score'));
    }

    public function testContextAwareExceptionHandling(): void
    {
        $middleware = new \Nexus\Http\Middleware\ExceptionHandlerMiddleware();
        $exception = new ValidationException(['email' => 'The email field is required.']);

        // Test JSON request context
        $jsonReq = new Request('POST', '/api/users', ['Content-Type' => 'application/json'], [], [], [], [], '');
        $jsonResp = $middleware->handle($jsonReq, function () use ($exception) {
            throw $exception;
        });

        $this->assertEquals(422, $jsonResp->getStatusCode());
        $this->assertTrue(str_contains($jsonResp->getContent(), '"errors"'));

        // Test HTML request context
        $htmlReq = new Request('GET', '/users/create', ['Accept' => 'text/html'], [], [], [], [], '');
        $htmlResp = $middleware->handle($htmlReq, function () use ($exception) {
            throw $exception;
        });

        $this->assertEquals(422, $htmlResp->getStatusCode());
        $this->assertTrue(str_contains($htmlResp->getContent(), '422 Unprocessable Entity'));
        $this->assertTrue(str_contains($htmlResp->getContent(), '<li>The email field is required.</li>'));
    }

    public function testDatabaseUniqueAndExistsRules(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT)');
        $pdo->exec("INSERT INTO users (id, email) VALUES (1, 'john@example.com')");

        $connection = new Connection($pdo);
        Model::setConnectionResolver($connection);

        // Test Unique fails for existing email
        $validator1 = Validator::make(['email' => 'john@example.com'], [
            'email' => ['required', 'unique:users,email'],
        ]);
        $this->assertTrue($validator1->fails());

        // Test Unique passes when ignoring ID 1
        $validator2 = Validator::make(['email' => 'john@example.com'], [
            'email' => ['required', 'unique:users,email,1'],
        ]);
        $this->assertFalse($validator2->fails());

        // Test Exists passes for existing ID
        $validator3 = Validator::make(['user_id' => 1], [
            'user_id' => ['required', 'exists:users,id'],
        ]);
        $this->assertFalse($validator3->fails());

        // Test Exists fails for missing ID
        $validator4 = Validator::make(['user_id' => 999], [
            'user_id' => ['required', 'exists:users,id'],
        ]);
        $this->assertTrue($validator4->fails());
    }

    protected function assertTrue(bool $condition, string $msg = ''): void
    {
        if (!$condition) {
            throw new \RuntimeException($msg ?: 'Failed asserting true condition.');
        }
    }

    protected function assertFalse(bool $condition, string $msg = ''): void
    {
        if ($condition) {
            throw new \RuntimeException($msg ?: 'Failed asserting false condition.');
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($msg ?: "Failed asserting that " . json_encode($actual) . " equals " . json_encode($expected));
        }
    }

    protected function assertCount(int $expectedCount, array $array): void
    {
        if (count($array) !== $expectedCount) {
            throw new \RuntimeException("Expected count {$expectedCount}, got " . count($array));
        }
    }
}
