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
- [Establish Query](#establish-query)
- [Facade](#facade)

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
            // function ($q) {
            //     $q
            //         ->where('email', 'LIKE', '%.org%')
            //         ->orWhere('phone', 'LIKE', '45%');
            // },
            fn($q) => $q->whereNotNull('email_verified_at'),
        ])
        ->addOrWheres([
            ['email', 'LIKE', '%@easymodel.org']
        ])
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> You must provide an array of arrays or closures to these methods since the first element of the array refers to the `column` and the second to the `operator` (default value is `=` in case you do not provide this element), and the third to the `value` in the array structure.

On top of the generic `addWheres` clause, you can use specialized helpers for common conditions: `addWhereIn`, `addWhereNotIn`, `addWhereBetween`, `addWhereNull`, `addWhereNotNull`, and `addKeywordSearch`.

Suppose you want to load only the verified users that belong to a known set of countries:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setSearchableModel(User::class)
        ->addWhereIn([
            ['country_id' => [1, 2, 3]],
            // ['plan_id' => Plan::active()->pluck('id')->all()],
        ])
        ->addWhereNotNull(['email_verified_at'])
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> Each entry passed to `addWhereIn`, `addWhereNotIn`, and `addWhereBetween` must be a `[column => values]` pair. Pass as many pairs as you like; they are combined with `AND`.

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
            'posts' => fn($q) => $q->where('title', 'LIKE', 'It\'s finally here! 🚀'),
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
            'platforms' => fn($q) => $q->where('name', 'DEV'),
        ])
        ->addOrWhereRelation([
            ['platforms', 'joined_on', 'Jan 15, 2024']
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
                'posts.tags' => fn($q) => $q->where('name', 'laravel'),
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
        ->setSearchableModel(Contributor::first())
        ->setRelationship('packages')
        ->addWheres([
            ['is_public', true]
        ])
        ->addWhereRelation([
            ['pullRequests', 'title', 'LIKE', '[1.x]%']
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
        ->setSearchableModel(new Influencer)
        ->addWhereRelation([
            ['articles', 'share_count', '>', 5000]
        ])
        ->addOrderBy([
            'name',
            // ['created_at' => 'desc']
        ])
        ->execute(false)
        ->get();
}
```

> [!IMPORTANT]
> The `addOrderBy` method accepts the column you need to be used in the order query (default direction is `ASC`) and agrees with an array where the key is the column and the value is the direction.

Besides, you can amazingly order the model by its relationships:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setSearchableModel(Influencer::class)
        ->addWhereHas([
            'articles>200'
        ])
        ->addOrderBy([
            // 'created_at',
            ['articles.comments.created_at' => 'desc']
        ])
        ->execute()
        ->get();
}
```

> [!NOTE]
> By default, this method resolves the issue of ambiguous columns by assuming that you need to order by the **searchable model** (e.g., `Influencer::class`). However, you can modify this behavior if necessary.

In addition, you can rank records by the **count** of a relationship using the `addOrderByCount` method — perfect for "most active", "top contributors", or "most engaged" listings:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setSearchableModel(Influencer::class)
        ->addOrderByCount('articles', 'desc')
        ->execute()
        ->get();
}
```

For more advanced ranking, the `addOrderByAggregate` method lets you order by any aggregate (`count`, `sum`, `avg`, `min`, or `max`) over a column on a related table. The example below ranks influencers by the **total share count** across all their articles:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setSearchableModel(Influencer::class)
        ->addOrderByAggregate('articles', 'share_count', 'sum', 'desc')
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> Both methods rely on Laravel's `withCount` / `withAggregate` under the hood, so the aggregate value is exposed on each result as a column alias (e.g., `articles_count` or `articles_sum_share_count`) and can be re-used in your views or API responses.

### Scopes

According to **Scopes**, it enables you to use the Local and Global Scopes together in an extremely awesome approach via the `usingScopes` method:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setSearchableModel(Developer::class)
        ->addWheres([
            ['specialize', 'Back-end']
        ])
        ->usingScopes([
            HasManyUpvotesScope::class,
            // 'isActive', // Local Scope does not require additional parameters
            'askQuestions' => [true, fn($q) => $q->has('answers')], // Local Scope requires additional parameters
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
        ->setSearchableModel(Merchant::class)
        ->addWheres([
            ['rate_avg', '>=', 3]
        ])
        ->usingScopes([
            HasManyBranchesScope::class,
        ])
        ->ignoreGlobalScopes([ManagerIsYoungScope::class])
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
        ->setSearchableModel(Admin::class)
        ->addWheres([
            ['email', 'LIKE', '%.net']
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
        ->setSearchableModel(User::class)
        ->usingScopes([
            BadgesScope::class,
        ])
        ->execute()
        ->chunk(50, function ($users) {
            foreach ($users as $user) {
                // do something here...
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
        ->setSearchableModel(Admin::class)
        ->addWheres([
            ['role_id', 2]
        ])
        // ->performUpdateQuery(['is_blocked' => true])
        ->performDeleteQuery(true);
}
```

## Other Contexts

### Chainable Methods

On the other hand, if you do not like to specify the model over the whole **Controller / Service** you can do so in each method separately:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->setSearchableModel(User::class)
        ->addWhereRelation([
            ['interests', 'slug', 'open-source']
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

## Establish Query

As an added bonus, you can effortlessly set a eloquent or query builder instance to begin building by using the `setSearchableQuery` method:

```PHP
/**
 * Update the specified resource in storage.
 */
public function update()
{
    $query = DB::table('contributors')->where('name', 'Taylor Otwell');

    return $this
        ->setSearchableModel(Contributor::class)
        ->setSearchableQuery($query)
        ->addWhereRelation([
            ['projects', 'name', 'Laravel']
        ])
        ->incrementEach(['commits' => 12])
        ->fetch();
}
```

## Facade

For ad-hoc usage outside a controller — console commands, queued jobs, route closures, etc. — the `EasyModel` Facade exposes the same fluent API on any model:

```PHP
use App\Models\Order;
use Ramadan\EasyModel\Facades\EasyModel;

return EasyModel::for(Order::class)
    ->addWhereHas(['items'])
    ->addWheres([
        function ($query) {
            $query
                ->whereNull('paid_at')
                ->orWhere('reference', 'LIKE', '%PRIORITY-%');
        },
    ])
    ->addWhereBetween([
        ['total'     => [100, 1000]],
        ['placed_at' => ['2026-01-01', '2026-01-31']],
    ])
    ->addKeywordSearch('john', ['customer_name', 'customer_email'])
    // ->addOrderBy([
    //     ['placed_at' => 'desc'],
    // ])
    ->addOrderByCount('items', 'desc')
    ->execute()
    ->with('items')
    ->paginate(15);
```

> [!NOTE]
> `EasyModel::for($model)` returns a fresh, single-use builder instance, so you do not need to worry about state leaking between unrelated queries — every call starts clean.
