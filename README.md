# Easy Model

![Easy Model](https://github.com/user-attachments/assets/5516ee0d-5cfc-49af-9435-8a7a26a942b2 "Easy Model")

![License](https://img.shields.io/packagist/l/ramadan/easy-model "License")
![Latest Version on Packagist](https://img.shields.io/packagist/v/ramadan/easy-model "Latest Version on Packagist")
![Total Downloads](https://img.shields.io/packagist/dt/ramadan/easy-model "Total Downloads")

 - - -

> [!NOTE]
> This package is not responsible for getting the data using methods like `first`, `get`, and `paginate` but, gives you an elegant approach for easily managing the query.

- [Installation](#installation)
- [Usage](#usage)
  - [Controllers / Services](#controllers--services)
  - [Chainable](#chainable)
  - [Models](#models)
- [Credits](#credits)
- [Support me](#support-me)

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
        $this->setModel(User::class);
    }
}
```

After that, you can search in the Model relationships using the `addWhereHas`, and `addWhereDoesntHave` methods:

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
> You must provide an array to these methods since you can pass just the relationship name as a string, in addition, you can suffix the relationship name with the operator and count to specify the relationship count that the Model must have also, you can pass the relationship as the key and a closure as a value.

Also, you can use the `whereRelation` and `orWhereRelation`:

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
> Using the previous methods you can simply provide the relationship name as a key and a colsure as a value or you can pass an array with four elements pointing to the `relationship` and the second pointing to the `column` and the third to the `operator` (The default value is `=` in case you do not provide this element), and fourth to the `value`.

Furthermore, you can use the previous methods one time by passing a list of arrays to the `addWheres` and `addOrWhere` methods:

```PHP
/**
 * Display a listing of the resource.
 */
public function index()
{
    return $this
        ->addWheres(
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
     * Get visible posts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     */
    public function scopeIsVisible($q)
    {
        $this->addWheres([
            ['is_visible', true]
        ], $q)
            ->execute();
    }
}
```

## Credits

- [Mahmoud Ramadan](https://github.com/mahmoudmohamedramadan)
- [Contributors](https://github.com/mahmoudmohamedramadan/custom-fresh/graphs/contributors)

## Support me

- [PayPal](https://www.paypal.com/paypalme/mmramadan496)

## License

The MIT License (MIT).
