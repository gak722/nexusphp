<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Http\Request;
use Nexus\Http\Resources\JsonResource;
use Nexus\Log\Logger;
use Nexus\Security\Auth;
use Nexus\Security\Gate;
use Nexus\Session\SessionManager;
use Nexus\Support\Log;
use Nexus\Support\Session;
use Nexus\Support\Storage;
use Nexus\Database\Model;
use Nexus\Database\ORM\SoftDeletes;
use Nexus\Database\Factory;
use Nexus\Database\Connection;

class TestUserModel extends Model
{
    use SoftDeletes;
    protected string $table = 'test_users';
    protected array $fillable = ['id', 'name', 'deleted_at'];
}

class TestUserFactory extends Factory
{
    protected string $model = TestUserModel::class;

    public function definition(): array
    {
        return [
            'name' => 'Default Name',
        ];
    }
}

class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_admin' => $this->when($this->id === 1, true, false),
        ];
    }
}

use PHPUnit\Framework\TestCase;

class UnimplementedFeaturesTest extends TestCase
{
    public function testStorageAndRequestFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'test content');

        $_FILES['avatar'] = [
            'name' => 'avatar.png',
            'type' => 'image/png',
            'tmp_name' => $tmpFile,
            'error' => 0,
            'size' => 12,
            'is_testing' => true,
        ];

        $request = new Request('POST', '/upload', [], [], [], $_FILES, [], '');
        assert($request->hasFile('avatar') === true);
        assert($request->file('avatar')['name'] === 'avatar.png');

        $storedPath = Storage::putFile('uploads', $request->file('avatar'));
        assert($storedPath !== false);
        assert(Storage::exists($storedPath) === true);

        Storage::delete($storedPath);
        @unlink($tmpFile);
    }

    public function testSessionDotNotationAndFlash(): void
    {
        Session::put('user.profile.name', 'Alice');
        assert(Session::get('user.profile.name') === 'Alice');
        assert(Session::has('user.profile') === true);

        Session::flash('status', 'Success');
        assert(Session::get('status') === 'Success');
        Session::forget('user');
        assert(Session::has('user.profile.name') === false);
    }

    public function testLoggerPsr3Interpolation(): void
    {
        $logPath = sys_get_temp_dir() . '/test_app.log';
        $logger = new Logger($logPath);
        $logger->info('User {name} created with ID {id}', ['name' => 'Bob', 'id' => 42]);

        $content = file_get_contents($logPath);
        assert(str_contains($content, 'User Bob created with ID 42'));
        @unlink($logPath);
    }

    public function testJsonResourceEnhancements(): void
    {
        $user = new TestUserModel(['id' => 1, 'name' => 'Charlie']);
        $resource = new UserResource($user);
        $data = $resource->resolve();

        assert($data['id'] === 1);
        assert($data['name'] === 'Charlie');
        assert($data['is_admin'] === true);

        $nullResource = new UserResource(null);
        assert($nullResource->resolve() === []);
    }

    public function testGateAndAuthIntegration(): void
    {
        $user = new TestUserModel(['id' => 1, 'name' => 'Admin']);
        Auth::setUser($user);

        Gate::define('edit-settings', function ($currentUser) {
            return $currentUser !== null && $currentUser->id === 1;
        });

        assert(Gate::allows('edit-settings') === true);
        assert(Gate::denies('edit-settings') === false);

        Gate::before(function ($currentUser, $ability) {
            if ($currentUser && $currentUser->name === 'Admin') {
                return true;
            }
            return null;
        });

        assert(Gate::allows('unknown-ability') === true);
        Auth::logout();
    }

    public function testSoftDeletesAndFactory(): void
    {
        $conn = new Connection(['driver' => 'sqlite', 'database' => ':memory:']);
        $conn->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT, deleted_at TEXT)');
        Model::setConnectionResolver($conn);

        $factoryUser = TestUserFactory::new()
            ->state(['name' => 'Factory Created'])
            ->create();

        assert($factoryUser->name === 'Factory Created');
        assert($factoryUser->trashed() === false);

        $factoryUser->delete();
        assert($factoryUser->trashed() === true);

        $found = TestUserModel::find($factoryUser->id);
        assert($found === null);

        $foundWithTrashed = TestUserModel::withTrashed()->where('id', $factoryUser->id)->first();
        assert($foundWithTrashed !== null);

        $factoryUser->restore();
        assert($factoryUser->trashed() === false);
        assert(TestUserModel::find($factoryUser->id) !== null);
    }
}
