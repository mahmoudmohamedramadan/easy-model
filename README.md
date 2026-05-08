# Easy Model

![Easy Model](/art/logo.png "Easy Model")

![Latest Version](https://img.shields.io/packagist/v/ramadan/easy-model?style=flat-square&logo=packagist)
![Total Downloads](https://img.shields.io/packagist/dt/ramadan/easy-model?style=flat-square)
![PHP](https://img.shields.io/badge/php-%5E8.2-777BB4?logo=php&style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0%7C%5E12.0%7C%5E13.0-FF2D20?logo=laravel&style=flat-square)
![License](https://img.shields.io/packagist/l/ramadan/easy-model?style=flat-square)

 - - -

- [Overview](#overview)
- [Installation](#installation)
- [Testing the upcoming release](#testing-the-upcoming-release)
- [Usage](#usage)
- [Credits](#credits)
- [Support Me](#support-me)

## Overview

Why this package?

**I am focused on simplifying the syntax to match my vision, making it easier for you to perform tasks that typically require more lines of code or effort in Laravel. I am also addressing issues that Laravel still faces. The package is actively maintained, and I regularly review closed PRs in Laravel to find methods to help achieve this.**

What makes this package featured?

- **Accelerated Query Performance:**
  - Significantly improved query performance compared to native Laravel.

- **Straightforward and Unified Syntax:**
  - Provides a unified, consistent syntax for `Query Builder` and `Eloquent Builder`. For more details, see [Establish Query](SEARCH.md#establish-query).

- **Resolved Ambiguous Exception:**
  - Fixes the ambiguous exception that arises when using the same column in both models and their relationships during ordering.

- **Simplified Relationship Ordering:**
  - Easily order results by model relationships (`HasOne`, `HasMany`, `BelongsTo`, `BelongsToMany`) without referring to manual joins. Check out [Order Results](SEARCH.md#order-results) to learn more.

- **Streamline Batch Updates:**
  - Effortlessly perform multiple updates using concise methods, consult [UPDATE.md](UPDATE.md).

- **Polymorphic & Through Relationship Ordering:**
  - Adds first-class ordering support for `MorphOne`, `MorphMany`, `MorphToMany`, `HasOneThrough`, and `HasManyThrough` relationships using safe `LEFT JOIN` handling and automatic per-query join deduplication.

- **Aggregate Ordering Helpers:**
  - Order results by `COUNT`, `SUM`, `AVG`, `MIN`, or `MAX` of a related relationship via `addOrderByCount` / `addOrderByAggregate`.

- **Keyword Search Across Columns:**
  - Run a single grouped `LIKE`/`=` search across multiple columns with `addKeywordSearch`.

- **Drop-in Facade:**
  - Skip the trait entirely and use `EasyModel::for(User::class)->...` from anywhere.

- And more...

## Installation

Install the package by using [Composer](https://getcomposer.org/):

```SHELL
composer require ramadan/easy-model
```

## Testing the upcoming release

Try the upcoming line in a Laravel app alongside your existing code paths:

```SHELL
composer require ramadan/easy-model:1.2.0-alpha.1
```

See the **[v1.2.0-alpha.1](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.2.0-alpha.1)** release notes in [CHANGELOG.md](CHANGELOG.md) for included changes.

## Usage

> [!WARNING]
> Do not use both traits together, as doing so may lead to unexpected output. However, if you do, the highest priority will be given to the **Searchable** trait.

For comprehensive examples and in-depth usage guidelines, check out [SEARCH.md](SEARCH.md) and [UPDATE.md](UPDATE.md).

## Credits

- [Mahmoud Ramadan](https://github.com/mahmoudmohamedramadan)
- [Contributors](https://github.com/mahmoudmohamedramadan/easy-model/graphs/contributors)

## Support me

- [PayPal](https://paypal.com/paypalme/mmramadan496)

## License

The MIT License (MIT).
