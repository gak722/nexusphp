# Collections

The NexusPHP `Collection` class provides a fluent, immutable wrapper for working with arrays of data. Unlike native PHP arrays which require wrapping functions inside out (e.g., `array_map(array_filter(...))`), Collections allow you to chain methods sequentially, producing code that is highly readable and expressive.

For processing massive datasets that exceed memory limits, NexusPHP also natively provides a `LazyCollection` implementation powered by PHP Generators.

> [!IMPORTANT]
> The NexusPHP `Collection` implementation is explicitly **Immutable**. Data transformation methods (like `map` and `filter`) do not modify the original collection; instead, they return a pristine new `Collection` instance containing the modified data.

---

## Creating Collections

In NexusPHP, Collections are instantiated explicitly via the class constructor or the static `make` method. 

You can also use the global `collect()` helper function:

```php
$collection = collect([1, 2, 3]);
```

```php
use Nexus\Support\Collection;

// Using the constructor
$collection = new Collection([1, 2, 3]);

// Using the static make method
$collection = Collection::make(['name' => 'John', 'age' => 25]);
```

---

## Available Collection Methods

The `Nexus\Support\Collection` class implements `ArrayAccess`, `Countable`, `IteratorAggregate`, and `JsonSerializable`. 

Below is the verified list of methods available for data manipulation:

### Basic Operations

- **`all(): array`**: Returns the raw underlying PHP array.
- **`count(): int`**: Returns the total number of items in the collection.
- **`isEmpty(): bool`**: Returns `true` if the collection has zero items.
- **`isNotEmpty(): bool`**: Returns `true` if the collection has one or more items.
- **`jsonSerialize(): mixed`**: Prepares the collection for JSON encoding, recursively calling `jsonSerialize()` on nested objects if they implement the interface.

### Accessing & Retrieving

- **`first(?callable $callback = null, mixed $default = null): mixed`**: Retrieves the first item. If a callback is provided, it returns the first item that passes the truth test.
- **`last(?callable $callback = null, mixed $default = null): mixed`**: Retrieves the last item. If a callback is provided, it returns the last item that passes the truth test.
- **`get(mixed $key, mixed $default = null): mixed`**: Retrieves an item by its array key using dot-notation via the underlying `Arr` helper.
- **`has(mixed $key): bool`**: Checks if a specific key exists in the collection.
- **`keys(): static`**: Returns a new collection containing only the keys of the original collection.
- **`values(): static`**: Returns a new collection with the values re-indexed numerically.

### Filtering & Transformation

- **`filter(?callable $callback = null): static`**: Returns a new collection containing only the items that pass the given callback. If no callback is provided, it filters out all "falsy" values.
- **`map(callable $callback): static`**: Returns a new collection by passing every item through the given callback. The returned value replaces the original value.
- **`flatMap(callable $callback): static`**: Similar to `map`, but immediately flattens the resulting collection by one level.
- **`collapse(): static`**: Collapses a collection of arrays (or nested Collections) into a single, flat, new Collection.

### Searching & Plucking

- **`pluck(string $value, ?string $key = null): static`**: Extracts all values for a given key across the entire collection. You may optionally pass a second argument to use as the index key for the resulting collection.

### Grouping & Sorting

- **`groupBy(string|callable $groupBy): static`**: Groups the collection's items by a given key or callback. Returns a new Collection of new Collections.
- **`keyBy(string|callable $keyBy): static`**: Keys the collection by a specific key or callback. If multiple items share the key, only the last one appears in the new collection.
- **`sortBy(string|callable $callback, int $options = SORT_REGULAR, bool $descending = false): static`**: Sorts the collection by the given key or callback.

### Utilities

- **`chunk(int $size): static`**: Breaks the collection into multiple, smaller collections of a given size.
- **`each(callable $callback): static`**: Iterates over the items in the collection, passing them to the callback. Returning `false` from the callback will halt the iteration. (Note: This method returns the original `$this` instance, not a new instance).


---

## Lazy Collections

For processing massive datasets that would otherwise trigger memory exhaustion (such as reading multi-gigabyte log files or looping over enormous database tables), NexusPHP provides the `Nexus\Support\Collection\LazyCollection`.

A `LazyCollection` leverages **PHP Generators** to defer memory allocation. Instead of loading all items into memory at once, it evaluates them one by one only as they are yielded.

### Creating a Lazy Collection

You can instantiate a Lazy Collection by passing a generator closure (or a standard array) to the constructor or `make` method.

```php
use Nexus\Support\Collection\LazyCollection;

$lazy = LazyCollection::make(function () {
    $handle = fopen('massive_log.csv', 'r');
    while (($line = fgetcsv($handle)) !== false) {
        yield $line;
    }
});
```

### Lazy Operations

The `LazyCollection` supports a subset of methods that preserve the deferred generator state. Methods like `map` and `filter` return a new `LazyCollection` wrapping the previous generator pipeline, executing **nothing** until the collection is finally iterated or resolved.

- **`map(callable $callback): static`**
- **`filter(callable $callback): static`**
- **`take(int $limit): static`**: Halts the generator once the limit is reached.
- **`each(callable $callback): static`**: Iterates and executes the pipeline.
- **`all(): array`**: Executes the entire deferred pipeline and loads the final result into an array via `iterator_to_array()`.
- **`count(): int`**: Executes the pipeline specifically to count the yielded items.

### Example: Efficient Processing

```php
// No files are opened and no data is loaded into memory yet
$lazy = LazyCollection::make(function () {
    for ($i = 0; $i < 1000000; $i++) {
        yield $i;
    }
})->filter(function ($i) {
    return $i % 2 === 0;
})->take(5);

// The generator only runs enough to find the first 5 even numbers!
$results = $lazy->all(); // [0, 2, 4, 6, 8]
```

---

## Best Practices

1. **Use `LazyCollection` for Database Results**: When processing thousands of ORM entities, always use `chunk()` on your query builder, or ideally, integrate a generator yielding to `LazyCollection` to keep memory pressure minimal.
2. **Chain Fluent Operations**: Because `Collection` is immutable, chain your operations sequentially. 
3. **Beware of Key Preservation**: Operations like `filter()` preserve the original array keys. If you need a cleanly numerically indexed array afterward, chain the `values()` method at the end (`$collection->filter(...)->values()`).

---

## Next Steps

Explore where Collections are natively returned in the framework:

- [Models & Relationships](orm.md): Learn how the `EntityQueryBuilder` hydrates rows into Collections of models.
- [Database Connections](database.md): Learn about the raw database connection wrapper.
