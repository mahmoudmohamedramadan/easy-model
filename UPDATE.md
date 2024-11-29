# Update Features

- [Controllers / Services Context](#controllers--services-context)
  - [Models](#models)
  - [Relations](#relations)
- [Other Contexts](#other-contexts)
  - [Model Injection](#model-injection)

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

### Models

After that, you can start updating the models using the `updateOrCreateModel` method:

```PHP
/**
 * Store a newly created resource in storage.
 */
public function store()
{
    return $this
        ->updateOrCreateModel(['name' => 'Mahmoud Ramadan', 'email' => 'easymodel@updatable.org'], [
            'password' => bcrypt('admin'),
        ])
        ->incrementEach(['points' => 2])
        ->fetch();
}
```

After incrementing specific columns, you can easily reset them to zero using the `zeroOutColumns` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->updateOrCreateModel(['name' => 'Mahmoud Ramadan', 'email' => 'easymodel@updatable.org'], [
            'password' => bcrypt('admin'),
        ])
        ->zeroOutColumns(['points', 'views'])
        ->fetch();
}
```

Moreover, if you have boolean columns and you need to toggle them simply, you can use the `toggleColumns` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    return $this
        ->updateOrCreateModel(['name' => 'Mahmoud Ramadan'], [
            'password' => bcrypt('creator'),
        ])
        ->toggleColumns(['is_admin', 'is_available'])
        ->fetch();
}
```

### Relations

What's more, you can update the relationship using the `updateOrCreateRelationship` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function Update()
{
    return $this
        ->updateOrCreateRelationship('posts', ['title' => 'Welcome "Updatable" trait!', 'user_id' => 1], [
            'body' => 'Thats a nice title',
        ])
        ->decrementEach(['views' => 3])
        ->fetch();
}
```

## Other Contexts

### Model Injection

Alternatively, if you prefer not to define the model at the class level, you can optionally pass the model directly:

```PHP
/**
 * Store a newly created resource in storage.
 */
public function store()
{
    return $this
        ->updateOrCreateModel(['name' => 'Mahmoud Ramadan', 'email' => 'easymodel@updatable.org'], [
            'password' => bcrypt('admin'),
        ], User::class)
        ->incrementEach(['points' => 2])
        ->fetch();
}
```
