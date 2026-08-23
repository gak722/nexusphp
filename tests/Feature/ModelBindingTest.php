<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Binding\Binder;
use Nexus\Binding\BindingContext;
use Nexus\Binding\BindingException;
use Nexus\Database\Model;
use Nexus\Http\Request;
use Nexus\Validation\Validate;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
}

class AddressDTO
{
    public string $city;
    public string $street;
}

class UserDTO
{
    public string $name;
    public int $age;
    public bool $active;
    public UserRole $role;
    public \DateTimeInterface $createdAt;
    public AddressDTO $address;
}

class BindableUserModel extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'age', 'role'];
    protected array $guarded = ['id', 'is_admin'];

    #[Validate(['required', 'string'])]
    public string $name;

    #[Validate(['required', 'email'])]
    public string $email;
}

class ModelBindingTest
{
    public function testModelBindingAndMassAssignmentProtection(): void
    {
        $user = new BindableUserModel();
        $user->fill([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'is_admin' => 1, // Guarded
        ]);

        $this->assertEquals('Alice', $user->getAttribute('name'));
        $this->assertEquals('alice@example.com', $user->getAttribute('email'));
        $this->assertNull($user->getAttribute('is_admin')); // Protected against mass assignment
    }

    public function testTypeAwareDtoBinding(): void
    {
        $data = [
            'name' => 'Bob',
            'age' => '30',
            'active' => 'true',
            'role' => 'admin',
            'createdAt' => '2026-08-23 12:00:00',
            'address' => [
                'city' => 'Mumbai',
                'street' => 'Marine Drive',
            ],
        ];

        $binder = new Binder();
        /** @var UserDTO $dto */
        $dto = $binder->bind(UserDTO::class, $data);

        $this->assertEquals('Bob', $dto->name);
        $this->assertEquals(30, $dto->age);
        $this->assertTrue($dto->active);
        $this->assertEquals(UserRole::ADMIN, $dto->role);
        $this->assertEquals('2026-08-23', $dto->createdAt->format('Y-m-d'));
        $this->assertEquals('Mumbai', $dto->address->city);
    }

    public function testRequestValidateAndBindIntegration(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/users',
            headers: ['Content-Type' => 'application/json'],
            query: [],
            post: [],
            files: [],
            cookies: [],
            rawBody: json_encode([
                'name' => 'Charlie',
                'email' => 'charlie@example.com',
                'is_admin' => true,
            ])
        );

        $user = BindableUserModel::validateAndBind($request);

        $this->assertEquals('Charlie', $user->getAttribute('name'));
        $this->assertEquals('charlie@example.com', $user->getAttribute('email'));
        $this->assertNull($user->getAttribute('is_admin'));
    }

    protected function assertTrue(bool $condition, string $msg = ''): void
    {
        if (!$condition) {
            throw new \RuntimeException($msg ?: 'Failed asserting true condition.');
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($msg ?: "Failed asserting that " . json_encode($actual) . " equals " . json_encode($expected));
        }
    }

    protected function assertNull(mixed $actual): void
    {
        if ($actual !== null) {
            throw new \RuntimeException("Expected null, got " . json_encode($actual));
        }
    }
}
