<?php
declare(strict_types=1);

use Nexus\Database\Connection;
use Nexus\Database\ORM\Attributes\BelongsTo;
use Nexus\Database\ORM\Attributes\Column;
use Nexus\Database\ORM\Attributes\ConcurrencyToken;
use Nexus\Database\ORM\Attributes\HasMany;
use Nexus\Database\ORM\Attributes\Key;
use Nexus\Database\ORM\Attributes\Table;
use Nexus\Database\ORM\DbContext;
use Nexus\Database\ORM\Tracking\EntityState;
use Nexus\Database\Exceptions\ConcurrencyException;

#[Table("ef_users")]
class EfUser
{
    #[Key]
    public int $id;

    #[Column]
    public string $name;

    #[Column]
    public string $email;

    #[ConcurrencyToken]
    public int $version = 1;

    #[HasMany(EfPost::class, foreignKey: 'ef_user_id')]
    public array $posts = [];
}

#[Table("ef_posts")]
class EfPost
{
    #[Key]
    public int $id;

    #[Column(name: 'ef_user_id')]
    public int $efUserId;

    #[Column]
    public string $title;

    #[BelongsTo(EfUser::class, foreignKey: 'ef_user_id')]
    public ?EfUser $user = null;
}

use PHPUnit\Framework\TestCase;

class EfCoreOrmTest extends TestCase
{
    protected Connection $conn;

    protected function setUp(): void
    {
        $this->conn = new Connection(['driver' => 'sqlite', 'database' => ':memory:']);

        $this->conn->statement("CREATE TABLE ef_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, version INTEGER DEFAULT 1);");
        $this->conn->statement("CREATE TABLE ef_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, ef_user_id INTEGER, title TEXT);");
    }

    public function testDbContextUnitOfWorkAndIdentityMap(): void
    {
        $context = new DbContext($this->conn);

        $user = new EfUser();
        $user->name = 'John Doe';
        $user->email = 'john@example.com';

        $context->add($user);
        $savedCount = $context->saveChanges();

        if ($savedCount !== 1 || $user->id <= 0) {
            throw new \RuntimeException("DbContext Unit of Work failed to save added entity.");
        }

        // Identity Map verification
        $fetched = $context->query(EfUser::class)->where('id', $user->id)->first();
        if ($fetched !== $user) {
            throw new \RuntimeException("DbContext Identity Map failed to return identical instance.");
        }
    }

    public function testDirtyTrackingAndUpdate(): void
    {
        $context = new DbContext($this->conn);

        $user = new EfUser();
        $user->name = 'Alice';
        $user->email = 'alice@example.com';
        $context->add($user);
        $context->saveChanges();

        // Mutate property
        $user->name = 'Alice Updated';
        $entry = $context->getChangeTracker()->getEntry($user);
        $context->getChangeTracker()->detectChanges($entry);

        if ($entry->state !== EntityState::Modified) {
            throw new \RuntimeException("ChangeTracker failed to detect modified entity state.");
        }

        $affected = $context->saveChanges();
        if ($affected !== 1) {
            throw new \RuntimeException("DbContext failed to persist modified entity.");
        }

        if ($user->version !== 2) {
            throw new \RuntimeException("Concurrency token auto-increment failed.");
        }
    }

    public function testEagerLoadingRelationships(): void
    {
        $context = new DbContext($this->conn);

        $user = new EfUser();
        $user->name = 'Author';
        $user->email = 'author@example.com';
        $context->add($user);
        $context->saveChanges();

        $post1 = new EfPost();
        $post1->efUserId = $user->id;
        $post1->title = 'First Post';
        $context->add($post1);

        $post2 = new EfPost();
        $post2->efUserId = $user->id;
        $post2->title = 'Second Post';
        $context->add($post2);

        $context->saveChanges();

        // Query with eager loading
        $context2 = new DbContext($this->conn);
        $userWithPosts = $context2->query(EfUser::class)
            ->with('posts')
            ->where('id', $user->id)
            ->firstOrFail();

        if (count($userWithPosts->posts) !== 2 || $userWithPosts->posts[0]->title !== 'First Post') {
            throw new \RuntimeException("DbContext eager loading relationship failed.");
        }
    }

    public function testOptimisticConcurrencyConflict(): void
    {
        $context1 = new DbContext($this->conn);
        $user = new EfUser();
        $user->name = 'Shared User';
        $user->email = 'shared@example.com';
        $user->version = 1;
        $context1->add($user);
        $context1->saveChanges();

        // Simulate concurrent fetch from another context
        $context2 = new DbContext($this->conn);
        $user1 = $context1->query(EfUser::class)->where('id', $user->id)->first();
        $user2 = $context2->query(EfUser::class)->where('id', $user->id)->first();

        // Context 1 updates first
        $user1->name = 'Name 1';
        $context1->saveChanges(); // version becomes 2

        // Context 2 tries to update with stale version 1
        $user2->name = 'Name 2';
        
        $conflictThrown = false;
        try {
            $context2->saveChanges();
        } catch (ConcurrencyException $e) {
            $conflictThrown = true;
        }

        if (!$conflictThrown) {
            throw new \RuntimeException("Optimistic concurrency failed to detect stale update conflict.");
        }
    }
}
