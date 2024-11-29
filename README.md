# Easy Model

![Easy Model](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model.png "Easy Model")

![License](https://img.shields.io/packagist/l/ramadan/easy-model "License")
![Latest Version on Packagist](https://img.shields.io/packagist/v/ramadan/easy-model "Latest Version on Packagist")
![Total Downloads](https://img.shields.io/packagist/dt/ramadan/easy-model "Total Downloads")

 - - -

- [Overview](#overview)
- [Installation](#installation)
- [Usage](#usage)
  - [Search Features](#search-features)
  - [Update Features](#update-features)
- [Credits](#credits)
- [Support Me](#support-me)

## Overview

What makes this package featured?

- **Accelerated Query Performance:**
  - Significantly  improved query performance compared to native Laravel ([fig. 1.](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model-vs-laravel-01.png), and [fig. 2.](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model-vs-laravel-02.png)).

- **Straightforward and Unified Syntax:**
  - Provides a unified, consistent syntax for `Query Builder` and `Eloquent Builder`.

- **Resolved Ambiguous Exception:**
  - Fixes the ambiguous exception that arises when using the same column in both models and their relationships during ordering.

- **Simplified Relationship Ordering:**
  - Easily order results by model relationships (`HasOne`, `HasMany`, `BelongsTo`, `BelongsToMany`) without referring to manual joins ([fig. 3.](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model-vs-laravel-03.png)).

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

### Search Features

Check out [search.md](SEARCH.md) for comprehensive examples and in-depth usage guidelines.

### Update Features

Check out [update.md](UPDATE.md) for comprehensive examples and in-depth usage guidelines.

## Credits

- [Mahmoud Ramadan](https://github.com/mahmoudmohamedramadan)
- [Contributors](https://github.com/mahmoudmohamedramadan/easy-model/graphs/contributors)

## Support me

- [PayPal](https://www.paypal.com/paypalme/mmramadan496)

## License

The MIT License (MIT).
