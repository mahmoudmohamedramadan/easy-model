# Update Features

- [Controllers / Services Context](#controllers--services-context)
  - [Models](#models)
  - [Relations](#relations)

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
        $this->setUpdatableModel(User::find(1));
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
        ], incrementEach: ['points' => 2])
        ->fetch();
}
```

Additionally, if you prefer not to specify the model at the class level, you can optionally pass the model like this:

```PHP
/**
 * Store a newly created resource in storage.
 */
public function store()
{
    return $this
        ->updateOrCreateModel(['name' => 'Mahmoud Ramadan', 'email' => 'easymodel@updatable.org'], [
            'password' => bcrypt('admin'),
        ], model: User::find(1), incrementEach: ['points' => 2])
        ->fetch();
}
```

### Relations

On top of that, you can update the relationship using the `updateOrCreateRelationship` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function Update()
{
    return $this
        ->updateOrCreateRelationship('posts', ['title' => 'nam nemo molestias', 'user_id' => 1], [
            'body' => 'Thats a nice title',
        ], decrementEach: ['views' => 3])
        ->fetch();
}
```
