# Search Features

- [Controllers / Services Context](#controllers--services-context)
  - [Where Clauses](#where-clauses)
  - [Relations](#relations)
  - [Order Results](#order-results)
  - [Scopes](#scopes)
  - [Soft Deletes](#soft-deletes)
  - [Laravel Methods](#laravel-methods)
  - [Update Operations](#update-operations)
- [Other Contexts](#other-contexts)
  - [Chainable Methods](#chainable-methods)
  - [Models](#models)

## Controllers / Services Context

In the beginning, you can specify the **Searchable Model** in the `constructor` method:

```PHP
use App\Models\User;
use Ramadan\EasyModel\Searchable;

class UserController extends Controller
{
    use Searchable;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->setSearchableModel(User::first());
        $this->setSearchableModel(new User);
    }
}
```

### Where Clauses

After that, you can search in the model using the `addWheres`, and `addOrWheres` methods:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->addWheres([
            ['name', 'Mahmoud Ramadan'],
             fn($q) => $q->whereNotNull('email_verified_at')
        ])
        ->addOrWheres([
            ['email', 'LIKE', '%example.org%']
        ])
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> You must provide an array of arrays or closures to these methods since the first element of the array refers to the `column` and the second to the `operator` (default value is `=` in case you do not provide this element), and the third to the `value` in the array structure.

Also, you can search in the model relationships using the `addWhereHas`, and `addWhereDoesntHave` methods:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->addWhereHas([
            // 'posts>2',
            'posts' => fn($q) => $q->where('title', 'LIKE','%Foo%'),
        ])
        ->addWhereDoesntHave([
            'comments'
        ])
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> You must provide an array to these methods since you can pass just the relationship name as a string, in addition, you can suffix the relationship name with the operator and count to specify the relationship count that the model must have also, you can pass the relationship as the key and a closure as a value.

In addition, you can use the `addWhereRelation` and `addOrWhereRelation`:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->addWhereRelation([
            'posts' => fn($q) => $q->where('title', 'LIKE','%Foo%'),
        ])
        ->addOrWhereRelation([
            ['comments', 'body', 'LIKE', '%Easy Model%']
        ])
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> Using the previous methods you can provide the relationship name as a key and a closure as a value or you can pass an array with four elements pointing to the `relationship` and the second to the `column` and the third to the `operator` (default value is `=` in case you do not provide this element), and fourth to the `value`.

Furthermore, you can use the previous methods one time by passing a list of arrays to the `addRelationConditions` and `addOrRelationConditions` methods:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->addRelationConditions(
            has: [
                'posts>1'
            ],
            relation: [
                'posts.comments' => fn($q) => $q->where('body', 'LIKE', '%sit%'),
            ]
        )
        ->execute()
        ->get();
}
```

### Relations

It enables you also to search in the model relationship using the `setRelationship` method:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setChainableModel(User::first())
        ->setRelationship('posts')
        ->addWheres([
            ['title', 'Easy Model']
        ])
        ->addWhereRelation([
            ['comments', 'body', 'LIKE', '%Laravel%']
        ])
        ->execute()
        ->get();
}
```

### Order Results

Moreover, you can order the result by using the `addOrderBy` method:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setChainableModel(new User)
        ->addWhereRelation([
            ['posts', 'title', 'LIKE', '%Easy Model%']
        ])
        ->addOrderBy([
            'name',
            // ['created_at' => 'desc']
        ])
        ->execute(false)
        ->get();
}
```

Besides, you can amazingly order the model by its relationships:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setChainableModel(new User)
        ->addWhereHas([
            'posts>1'
        ])
        ->addOrderBy([
            // 'created_at',
            ['posts.comments.created_at' => 'desc']
        ])
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> The `addOrderBy` method accepts the column you need to be used in the order query (default direction is `ASC`) and agrees with an array where the key is the column and the value is the direction.

### Scopes

According to **Scopes**, it enables you to use the Local and Global Scopes together in an extremely awesome approach via the `usingScopes` method:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setChainableModel(User::class)
        ->addWheres([
            ['name', 'Mahmoud Ramadan']
        ])
        ->usingScopes([
            // new EmailVerifiedScope, // Global Scope in object
            EmailVerifiedScope::class, // Global Scope in string
            // 'isActive', // Local Scope method does not require additional parameters
            'isAdmin' => [false, fn($q) => $q->where('id', '>', 2)], // Local Scope requires additional parameters
        ])
        ->execute()
        ->get();
}
```

> [!NOTE]
> The `usingScopes` method never overrides the [Global Scopes](https://laravel.com/docs/11.x/eloquent#applying-global-scopes) you already use in the model.

Furthermore, you can ignore specific Global Scopes using the `ignoreGlobalScopes` method:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setChainableModel(User::class)
        ->addWheres([
            ['name', 'Mahmoud Ramadan']
        ])
        ->usingScopes([
            UserIsAdminScope::class,
            EmailVerifiedScope::class,
        ])
        ->ignoreGlobalScopes([UserIsAdminScope::class])
        ->execute()
        ->get();
}
```

### Soft Deletes

By default, the result excludes soft-deleted records. However, you can explicitly include them by using the `includeSoftDeleted` method:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setChainableModel(User::class)
        ->addWheres([
            ['name', 'Mahmoud Ramadan']
        ])
        ->includeSoftDeleted()
        ->execute()
        ->get();
}
```

### Laravel Methods

On top of that, you can seamlessly take advantage of all Laravel methods:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setChainableModel(User::class)
        ->usingScopes([
            EmailVerifiedScope::class,
        ])
        ->execute()
        ->chunk(50, function ($users) {
            foreach ($users as $user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['credits' => 3]);
            }
        });
}
```

### Update Operations

The `Searchable` trait also includes the methods from the [`Updatable`](UPDATE.md) trait:

```PHP
/**
 * Remove the specified resource from storage.
 */
public function destroy()
{
    return $this
        ->setChainableModel(User::class)
        ->addWheres([
            ['id', '>', 1]
        ])
        // ->performUpdateQuery(['name' => fake()->name()])
        ->performDeleteQuery();
}
```

## Other Contexts

### Chainable Methods

On the other hand, if you do not like to specify the model over the whole **Controller / Service** you can do so in each method separately using the `setChainableModel` method:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setChainableModel(User::class)
        ->addWhereRelation([
            ['posts', 'title', 'Easy Model']
        ])
        ->execute()
        ->get();
}
```

### Models

At last, you have control over these methods directly within the model, allowing you to use them in contexts such as [Local Scopes](https://laravel.com/docs/11.x/eloquent#local-scopes) methods:

```PHP
class Post extends Model
{
    use Searchable;

    /**
     * Get the posts that have more than two comments.
     */
    public function scopeHasComments($q)
    {
        $this
            ->addRelationConditions(
                has: ['comments>2'],
                query: $q
            )
            ->execute();
    }
}
```