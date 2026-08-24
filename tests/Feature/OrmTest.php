<?php
declare(strict_types=1);

use Nexus\Database\Connection;
use Nexus\Database\Model;
use Nexus\Database\QueryBuilder;

class TestUser extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'status'];

    public function posts(): \Nexus\Database\Relations\HasMany
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }
}

class TestPost extends Model
{
    protected string $table = 'posts';
    protected array $fillable = ['user_id', 'title', 'content'];

    public function user(): \Nexus\Database\Relations\BelongsTo
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}

use PHPUnit\Framework\TestCase;

class OrmTest extends TestCase
{
    protected Connection $conn;

    protected function setUp(): void
    {
        $this->conn = new Connection(['driver' => 'sqlite', 'database' => ':memory:']);
        Model::setConnectionResolver($this->conn);

        $this->conn->statement("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, status TEXT);");
        $this->conn->statement("CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, content TEXT);");
    }

    public function testDatabaseQueryBuilding(): void
    {
        $this->conn->statement("INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.com'), ('Bob', 'bob@example.com');");

        $builder = new QueryBuilder($this->conn);
        $results = $builder->table('users')->where('name', '=', 'Alice')->get();

        if (count($results) !== 1) {
            throw new \RuntimeException("ORM query builder failed to fetch single row.");
        }

        if ($results[0]['name'] !== 'Alice') {
            throw new \RuntimeException("ORM query builder returned wrong row content.");
        }
    }

    public function testMedooStyleQueryBuilderFeatures(): void
    {
        $user1 = TestUser::create(['name' => 'Charlie', 'email' => 'charlie@test.com', 'status' => 'active']);
        $user2 = TestUser::create(['name' => 'David', 'email' => 'david@test.com', 'status' => 'inactive']);
        $user3 = TestUser::create(['name' => 'Eve', 'email' => 'eve@test.com', 'status' => 'active']);

        // Count aggregate
        $activeCount = TestUser::where('status', 'active')->count();
        if ($activeCount !== 2) {
            throw new \RuntimeException("Medoo QueryBuilder count aggregate failed.");
        }

        // whereIn & whereNotIn
        $inResults = TestUser::whereIn('name', ['Charlie', 'Eve'])->get();
        if (count($inResults) !== 2) {
            throw new \RuntimeException("Medoo QueryBuilder whereIn failed.");
        }

        $notInResults = TestUser::whereNotIn('name', ['Charlie', 'Eve'])->get();
        if (count($notInResults) < 1) {
            throw new \RuntimeException("Medoo QueryBuilder whereNotIn failed.");
        }
    }

    public function testLaravelStyleModelAndRedBeanState(): void
    {
        // Model::create mass assignment guarded fillable check
        $user = TestUser::create([
            'name' => 'Frank',
            'email' => 'frank@example.com',
            'status' => 'active',
            'is_admin' => 1 // Should be ignored via guarded
        ]);

        if ($user->name !== 'Frank' || $user->getKey() === null) {
            throw new \RuntimeException("Laravel Model::create failed.");
        }

        if (isset($user->is_admin)) {
            throw new \RuntimeException("Mass assignment security vulnerability detected.");
        }

        // RedBeanPHP Dynamic Property & Dirty State
        if ($user->isDirty()) {
            throw new \RuntimeException("Newly saved model should not be dirty.");
        }

        $user->name = 'Frank updated';
        if (!$user->isDirty('name')) {
            throw new \RuntimeException("RedBeanPHP dynamic state dirty tracking failed.");
        }

        $user->save();
        if ($user->isDirty('name')) {
            throw new \RuntimeException("Model save failed to sync original state.");
        }

        // Find and FindOrFail
        $found = TestUser::findOrFail($user->getKey());
        if ($found->name !== 'Frank updated') {
            throw new \RuntimeException("Laravel Model::findOrFail failed.");
        }

        // ArrayAccess API
        if ($found['email'] !== 'frank@example.com') {
            throw new \RuntimeException("Model ArrayAccess failed.");
        }
    }

    public function testModelRelationships(): void
    {
        $user = TestUser::create(['name' => 'Grace', 'email' => 'grace@test.com', 'status' => 'active']);
        $post1 = TestPost::create(['user_id' => $user->getKey(), 'title' => 'Post 1', 'content' => 'Content 1']);
        $post2 = TestPost::create(['user_id' => $user->getKey(), 'title' => 'Post 2', 'content' => 'Content 2']);

        // HasMany relationship lazy load
        $posts = $user->posts;
        if (count($posts) !== 2 || !$posts[0] instanceof TestPost) {
            throw new \RuntimeException("Model HasMany relationship failed.");
        }

        // BelongsTo relationship lazy load
        $postUser = $post1->user;
        if (!$postUser instanceof TestUser || $postUser->name !== 'Grace') {
            throw new \RuntimeException("Model BelongsTo relationship failed.");
        }

        if (!$post1->relationLoaded('user') || !$user->relationLoaded('posts')) {
            throw new \RuntimeException("Model relationLoaded verification failed.");
        }
    }

    public function testTransactions(): void
    {
        $initialCount = TestUser::query()->count();

        try {
            $this->conn->transaction(function () {
                TestUser::create(['name' => 'RollbackUser', 'email' => 'rollback@test.com']);
                throw new \Exception("Trigger rollback");
            });
        } catch (\Throwable $e) {
            // Expected exception
        }

        $postCount = TestUser::query()->count();
        if ($postCount !== $initialCount) {
            throw new \RuntimeException("Transaction rollback failed.");
        }
    }
}
