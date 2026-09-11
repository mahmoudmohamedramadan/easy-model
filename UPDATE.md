# Update Features

- [Controllers / Services Context](#controllers--services-context)
  - [Flipping](#flipping)
  - [Increment / Decrement](#increment--decrement)
  - [Reset](#reset)
  - [Insert / Upsert](#insert--upsert)
  - [Model Events](#model-events)
  - [Laravel Methods](#laravel-methods)
- [Other Contexts](#other-contexts)
  - [Chainable Methods](#chainable-methods)
- [Establish Query](#establish-query)
- [Reset State](#reset-state)
- [Facade](#facade)

## Controllers / Services Context

Just like the **Searchable** trait, register the **Updatable Model** in the `constructor` method:

```PHP
use App\Models\Car;
use Ramadan\EasyModel\Updatable;

class CarController extends Controller
{
    use Updatable;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setUpdatableModel(Car::class);
    }
}
```

### Flipping

To toggle boolean columns, use the `toggleColumns` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->toggleColumns(['is_new', 'is_automatic'])
        ->fetch();
}
```

### Increment / Decrement

To adjust numeric columns, use the `incrementEach` and `decrementEach` methods:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->incrementEach(['stock_count' => 100])
        ->decrementEach(['discount_percentage' => 5])
        ->fetch();
}
```

### Reset

To reset numeric columns to zero, use the `zeroOutColumns` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->zeroOutColumns(['stock_count', 'discount_percentage'])
        ->fetch();
}
```

### Insert / Upsert

Create a single row through Eloquent, bulk-insert a list of rows, or upsert by unique columns:

```PHP
public function store()
{
    return $this
        ->setUpdatableModel(Car::class)
        ->performInsert([
            'make'  => 'Toyota',
            'model' => 'Corolla',
            'color' => 'red',
        ]);
}
```

```PHP
public function sync()
{
    return $this
        ->setUpdatableModel(Car::class)
        ->performUpsert(
            [
                ['sku' => 'COROLLA', 'stock_count' => 12],
                ['sku' => 'CIVIC', 'stock_count' => 4],
            ],
            ['sku']
        );
}
```

A list of associative arrays is treated as a bulk `insert`. A single associative array uses Eloquent `create`.

To restore or permanently delete soft-deleted rows:

```PHP
$this->setSearchableModel(Car::class)->onlyTrashed()->restore();
$this->setSearchableModel(Car::class)->addWheres([['id', 4]])->forceDelete();
```

### Model Events

Mass `update` / `incrementEach` / `delete` queries skip model observers, casts, and mutators. Chain `usingModelEvents()` to walk matching rows in chunks and persist each through the model:

```PHP
public function update()
{
    return $this
        ->setSearchableModel(Car::class)
        ->addWheres([
            ['make', 'Toyota'],
        ])
        ->usingModelEvents()
        ->incrementEach(['stock_count' => 10])
        ->fetch();
}
```

### Laravel Methods

You can also fall back to any native Laravel method on the underlying builder:

```PHP
/**
 * Store a newly created resource in storage.
 */
public function store()
{
    return $this
        ->fetchBuilder()
        ->insertGetId([
            'make'  => 'Toyota',
            'model' => 'Corolla',
            'color' => 'red', 
        ]);
}
```

## Other Contexts

### Chainable Methods

If you'd rather not pin the model at the class level, set it inside each method instead:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->setUpdatableModel(Car::find(4))
        ->incrementEach(['discount_percentage' => 3])
        ->fetch();
}
```

You can also update a single model instance directly via `performUpdateQuery`:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->setUpdatableModel(Car::find(4))
        ->performUpdateQuery(['color' => 'black']);
}
```

## Establish Query

You can also start from an existing eloquent or query builder by passing it to the `setUpdatableQuery` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    $query = DB::table('projects')->where('name', 'Easy Model');

    return $this
        ->setUpdatableModel(Project::class)
        ->setUpdatableQuery($query)
        ->incrementEach(['prs' => 3])
        ->fetch();
}
```

## Reset State

To safely reuse the same instance across unrelated update operations, call `flushUpdatable` to clear any leftover state:

```PHP
use Ramadan\EasyModel\Updatable;

class OrderService
{
    use Updatable;

    public function reset()
    {
        $this->flushUpdatable();
    }
}
```

## Facade

For ad-hoc usage outside a controller — queued jobs, scheduled commands, webhook handlers, etc. — the `EasyModel` Facade exposes the same fluent API on any model:

```PHP
use App\Models\Article;
use Ramadan\EasyModel\Facades\EasyModel;

return EasyModel::for(Article::class)
    ->addWheres([
        ['published', true],
    ])
    ->addWhereBetween([
        ['published_at' => [now()->startOfDay(), now()->endOfDay()]],
    ])
    ->incrementEach(['views' => 1])
    ->fetch();
```

> [!NOTE]
> `EasyModel::for($model)` returns a fresh, single-use builder instance, so you do not need to worry about state leaking between unrelated update operations — every call starts clean.
