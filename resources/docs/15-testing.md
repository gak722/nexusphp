# 15. Testing Framework & Assertions

NexusPHP includes an isolated, zero-dependency unit and integration testing suite runner (`tests/`) that executes without PHPUnit or external dependencies.

---

## 1. Writing Test Cases

Test classes extend `Nexus\Testing\TestCase`:

```php
namespace Tests\Unit;

use Nexus\Testing\TestCase;
use App\Support\Markdown;
use Nexus\Security\Jwt;

class MarkdownTest extends TestCase
{
    public function testMarkdownHeadingConversion(): void
    {
        $html = Markdown::parse('# Hello World');
        $this->assertStringContains('<h1 id="hello-world">Hello World', $html);
    }

    public function testJwtTokenEncodingAndDecoding(): void
    {
        $payload = ['user_id' => 42];
        $key = 'secret_test_key_123';

        $token = Jwt::encode($payload, $key);
        $decoded = Jwt::decode($token, $key);

        $this->assertEquals(42, $decoded['user_id']);
    }
}
```

---

## 2. Available Assertion Methods

- `$this->assertTrue($condition)`
- `$this->assertFalse($condition)`
- `$this->assertEquals($expected, $actual)`
- `$this->assertNotEquals($expected, $actual)`
- `$this->assertNull($value)`
- `$this->assertNotNull($value)`
- `$this->assertStringContains($needle, $haystack)`

---

## 3. Running the Test Suite

Run all test cases using the `nexus` CLI executable:

```bash
php nexus test
```

Output:

```text
NexusPHP Zero-Dependency Test Runner v1.0
─────────────────────────────────────────────
 PASS  Tests\Unit\MarkdownTest
  ✓ testMarkdownHeadingConversion
  ✓ testJwtTokenEncodingAndDecoding

Tests: 2 passed, 0 failed, 2 total
Time:  0.012s (Memory: 2.1 MB)
```

---

## 4. Next Steps

Explore application architecture patterns starting with [16. Architecture Pattern: RESTful API (SPA Backend)](16-arch-rest-api.md).
