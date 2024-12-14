# Update Features

- [Controllers / Services Context](#controllers--services-context)
  - [Flipping](#flipping)
  - [Increment / Decrement](#increment--decrement)
  - [Reset](#reset)
  - [Laravel Methods](#laravel-methods)
- [Other Contexts](#other-contexts)
  - [Chainable Methods](#chainable-methods)
- [Establish Query](#establish-query)

## Controllers / Services Context

As well as the **Searchable** trait, you can specify the **Updatable Model** in the `constructor` method:

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

If you have boolean columns and you need to toggle them simply, you can use the `toggleColumns` method:

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

In addition, you can adjust the models using the `incrementEach` and `decrementEach` methods:

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

Also, you can easily reset them to zero using the `zeroOutColumns` method:

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

### Laravel Methods

As a bonus, you can effortlessly leverage all the built-in Laravel methods:

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

Alternatively, if you prefer not to define the model at the class level, you can do so in each method separately:

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

## Establish Query

Additionally, you can easily configure either an eloquent or query builder instance to start building by using the `setUpdatableQuery` method:

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
