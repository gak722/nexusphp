<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Support\Str;
use Nexus\Support\Arr;
use Nexus\Support\Collection;
use Nexus\Support\Collection\LazyCollection;
use Nexus\Support\Number\Number;
use Nexus\Support\Cast\Cast;
use Nexus\Support\Parser\IntegerParser;
use Nexus\Support\Parser\BooleanParser;
use Nexus\Support\Parser\DurationParser;
use Nexus\Support\DateTime\DateTime;
use Nexus\Support\Duration\Duration;
use Nexus\Support\Uuid\Uuid;
use Nexus\Support\Ulid\Ulid;
use Nexus\Support\Url\Url;
use Nexus\Support\Path\Path;
use Nexus\Support\Enum\Enum;

enum DemoStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
}

use PHPUnit\Framework\TestCase;

class SupportUtilityTest extends TestCase
{

    public function testStringUtilities(): void
    {
        $this->assertEquals('hello_world', Str::snake('HelloWorld'));
        $this->assertEquals('hello-world', Str::kebab('HelloWorld'));
        $this->assertEquals('HelloWorld', Str::studly('hello world'));
        $this->assertEquals('hello-world', Str::slug('Hello World!'));
        $this->assertTrue(Str::contains('NexusPHP Framework', 'Framework'));
        $this->assertTrue(Str::isEmail('user@example.com'));
        $this->assertTrue(Str::isUuid(Str::uuid()));
        $this->assertTrue(Str::isUlid(Str::ulid()));
        
        $this->assertEquals('hello-world', str('Hello World!')->slug()->value());
    }

    public function testParserAndCastUtilities(): void
    {
        $res = (new BooleanParser())->parse('yes');
        $this->assertTrue($res->isValid());
        $this->assertTrue($res->value());

        $intRes = (new IntegerParser())->parse('12345');
        $this->assertTrue($intRes->isValid());
        $this->assertEquals(12345, $intRes->value());

        $this->assertEquals(9000, (new DurationParser())->parse('2h 30m')->value());

        $this->assertTrue(Cast::toBool('on'));
        $this->assertEquals(100, Cast::toInt('100'));
        $this->assertEquals(1048576, Cast::toSize('1MB'));
        $this->assertEquals(DemoStatus::Active, Cast::toEnum(DemoStatus::class, 'active'));
    }

    public function testArrayAndCollectionUtilities(): void
    {
        $data = ['user' => ['profile' => ['name' => 'John', 'age' => 30]]];
        $this->assertEquals('John', Arr::get($data, 'user.profile.name'));
        $this->assertTrue(Arr::has($data, 'user.profile.age'));

        $users = collect([
            ['name' => 'Alice', 'role' => 'admin'],
            ['name' => 'Bob', 'role' => 'user'],
            ['name' => 'Charlie', 'role' => 'admin'],
        ]);

        $admins = $users->filter(fn($u) => $u['role'] === 'admin')
            ->sortBy('name')
            ->pluck('name')
            ->values()
            ->all();

        $this->assertEquals(['Alice', 'Charlie'], $admins);
    }

    public function testLazyCollection(): void
    {
        $lazy = LazyCollection::make(function () {
            for ($i = 1; $i <= 1000; $i++) {
                yield $i;
            }
        });

        $taken = $lazy->filter(fn($n) => $n % 2 === 0)
            ->take(3)
            ->all();

        $this->assertEquals([2, 4, 6], array_values($taken));
    }

    public function testDateTimeAndDuration(): void
    {
        $dt = DateTime::parse('2026-08-23 12:00:00', 'UTC');
        $this->assertEquals('2026-08-23 00:00:00', $dt->startOfDay()->format('Y-m-d H:i:s'));

        $duration = Duration::parse('1h 30m');
        $this->assertEquals(5400, $duration->toSeconds());
        $this->assertEquals('1.5h', $duration->human());
    }

    public function testNumberUtilities(): void
    {
        $this->assertEquals(50, Number::clamp(150, 0, 50));
        $this->assertEquals('$1,234.50', Number::currency(1234.50, 'USD', 'en_US'));
        $this->assertEquals('25%', Number::percentage(0.25));
    }

    public function testUrlAndPathUtilities(): void
    {
        $url = Url::parse('https://nexusphp.com/docs?version=1.0');
        $updated = $url->addQuery(['page' => '2']);
        $this->assertEquals('https://nexusphp.com/docs?version=1.0&page=2', $updated->toString());

        $this->assertEquals('/var/www/nexus/config', Path::normalize('/var/www/app/../nexus/config'));
    }

    public function testEnumUtilities(): void
    {
        $this->assertEquals(['active', 'pending'], Enum::values(DemoStatus::class));
        $this->assertEquals(['Active', 'Pending'], Enum::names(DemoStatus::class));
    }
}
