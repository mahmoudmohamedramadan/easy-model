# Easy Model

![Easy Model](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model.png "Easy Model")

![License](https://img.shields.io/packagist/l/ramadan/easy-model "License")
![Latest Version on Packagist](https://img.shields.io/packagist/v/ramadan/easy-model "Latest Version on Packagist")
![Total Downloads](https://img.shields.io/packagist/dt/ramadan/easy-model "Total Downloads")

 - - -

- [About](#about)
- [Installation](#installation)
- [Usage](#usage)
  - [Controllers / Services](#controllers--services)
  - [Chainable](#chainable)
  - [Models](#models)
  - [Advanced](#advanced)
- [Credits](#credits)
- [Support me](#support-me)

## About

 What makes this package featured?

- Improves the **query time** more than any package, even **Laravel** itself ([fig. 1.](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model-faster-than-laravel-01.png)).
- Gives you a `Query Builder` and `Eloquent Builder` instances via ONLY one syntax.

> The package was significantly FASTER than the Laravel query when tested on over **1k records** ([fig. 2.](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model-faster-than-laravel-02.png)). 🥵

## Installation

Install the package by using [Composer](https://getcomposer.org/):

```SHELL
composer require ramadan/easy-model
```

## Usage

### Controllers / Services

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
        // $this->setModel(new User);
        $this->setModel(User::class);
    }
}
```

After that, you can search in the model using the `addWheres`, and `addOrWheres` methods:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->addWheres([
            ['name', 'Mahmoud Ramadan']
        ])
        ->addOrWheres([
            ['email', 'LIKE', '%example.org%']
        ])
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> You must provide an array of arrays to these methods since the first element refers to the `column` and the second to the `operator` (The default value is `=` in case you do not provide this element), and the third to the `value`.

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
> Using the previous methods you can provide the relationship name as a key and a closure as a value or you can pass an array with four elements pointing to the `relationship` and the second to the `column` and the third to the `operator` (The default value is `=` in case you do not provide this element), and fourth to the `value`.

Furthermore, you can use the previous methods one time by passing a list of arrays to the `addAllWheres` and `addAllOrWheres` methods:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->addAllWheres(
            whereHas: [
                'posts>1'
            ],
            whereRelation: [
                'posts.comments' => fn($q) => $q->where('body', 'LIKE', '%sit%'),
            ]
        )
        ->execute()
        ->get();
}
```

### Chainable

On the other hand, if you do not like to specify the Model over the whole **Controller / Service** you can do so in each method separately using the `setChainableModel` method:

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

At last, you have control over these methods in the Model itself which enables you to use them in something like the [Local Scopes](https://laravel.com/docs/11.x/eloquent#local-scopes) methods:

```PHP
class Post extends Model
{
    use Searchable;

    /**
     * Get the posts that have more than two comments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     */
    public function scopeHasComments($q)
    {
        $this
            ->addAllWheres(
                whereHas: ['comments>2'],
                query: $q
            )
            ->execute();
    }
}
```

### Advanced

> [!TIP]
> Starting from **v1.0.2**, a new feature allows developers to specify the returning query type `Query Builder` or `Eloquent Builder` by passing a boolean value to the `execute` method.

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
            // 'posts.created_at',
            ['posts.comments.created_at' => 'desc']
        ])
        ->execute()
        ->get();
}
```

> [!IMPORTANT]
> The `addOrderBy` method accepts the column you need to be used in the order query (The default direction is `ASC`) and agrees with an array where the key is the column and the value is the direction.

## Credits

- [Mahmoud Ramadan](https://github.com/mahmoudmohamedramadan)
- [Contributors](https://github.com/mahmoudmohamedramadan/custom-fresh/graphs/contributors)

## Support me

- [PayPal](https://www.paypal.com/paypalme/mmramadan496)

## License

The MIT License (MIT).
