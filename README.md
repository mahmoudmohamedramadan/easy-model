# Easy Model

![Easy Model](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model.png "Easy Model")

![License](https://img.shields.io/packagist/l/ramadan/easy-model "License")
![Latest Version on Packagist](https://img.shields.io/packagist/v/ramadan/easy-model "Latest Version on Packagist")
![Total Downloads](https://img.shields.io/packagist/dt/ramadan/easy-model "Total Downloads")

 - - -

- [Overview](#overview)
- [Installation](#installation)
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
  - Effortlessly perform multiple updates using concise methods, consult [update.md](UPDATE.md).

- And more...

## Installation

Install the package by using [Composer](https://getcomposer.org/):

```SHELL
composer require ramadan/easy-model
```

## Usage

> [!WARNING]
> Do not use both traits together, as doing so may lead to unexpected output. However, if you do, the most priority will be given to the **Searchable** trait.

For comprehensive examples and in-depth usage guidelines, check out [search.md](SEARCH.md) and [update.md](UPDATE.md).

## Credits

- [Mahmoud Ramadan](https://github.com/mahmoudmohamedramadan)
- [Contributors](https://github.com/mahmoudmohamedramadan/easy-model/graphs/contributors)

## Support me

- [PayPal](https://www.paypal.com/paypalme/mmramadan496)

## License

The MIT License (MIT).
