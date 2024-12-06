# Update Features

- [Controllers / Services Context](#controllers--services-context)
  - [Flipping Attributes](#flipping-attributes)
  - [Reset Attributes](#reset-attributes)
  - [Increment / Decrement Attributes](#increment--decrement-attributes)
  - [Laravel Methods](#laravel-methods)
- [Other Contexts](#other-contexts)
  - [Chainable Methods](#chainable-methods)
- [Establish Query](#establish-query)

## Controllers / Services Context

As well as the **Searchable** trait, you can specify the **Updatable Model** in the `constructor` method:

```PHP
use App\Models\User;
use Ramadan\EasyModel\Updatable;

class UserController extends Controller
{
    use Updatable;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setUpdatableModel(User::class);
    }
}
```

### Flipping Attributes

If you have boolean columns and you need to toggle them simply, you can use the `toggleColumns` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->toggleColumns(['is_admin', 'is_available'])
        ->fetch();
}
```

### Reset Attributes

Also, you can easily reset them to zero using the `zeroOutColumns` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->zeroOutColumns(['points', 'views'])
        ->fetch();
}
```

### Increment / Decrement Attributes

In addition, you can adjust the models using the `incrementEach` and `decrementEach` methods:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->incrementEach(['points' => 2])
        ->decrementEach(['views' => 3])
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
        ->insertGetId(
            ['name' => 'Mateo Stark', 'email' => 'mateostark@example.com', 'password' => bcrypt('mateostark')]
        );
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
        ->setUpdatableModel(Post::find(4))
        ->incrementEach(['views' => 3])
        ->fetch();
}
```

## Establish Query

Additionally, you can easily configure either an Eloquent or query builder to start building by using the `setUpdatableQuery` method:

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
        ->incrementEach(['prs' => 2])
        ->fetch();
}
```
