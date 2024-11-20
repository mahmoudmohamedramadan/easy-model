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

- Improves the **query time** more than any package, even **Laravel** itself ([fig. 1.](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model-vs-laravel-01.png)).
- Gives you a `Query Builder` and `Eloquent Builder` instances via **ONLY one syntax**.
- Fixes the **Ambiguous Exception** thrown by Laravel when the same column is used from the model and its relationship in the "order" query.
- IMO, The most wonderful feature is that it enables you to order the result by the model relationships (`HasOne`, `HasMany`, `BelongsTo`, and `BelongsToMany`) and keeps you away from performing the "join" manually ([fig. 3.](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model-vs-laravel-03.png)).

> The package was significantly FASTER than the Laravel query when tested on over **1k records** ([fig. 2.](https://raw.githubusercontent.com/mahmoudmohamedramadan/easy-model/refs/heads/main/assets/easy-model-vs-laravel-02.png)). 🥵

## Installation

Install the package by using [Composer](https://getcomposer.org/):

```SHELL
composer require ramadan/easy-model
```

## Usage

> [!WARNING]
> Do not use both traits together, as doing so may lead to unexpected issues.

### Search Features

Check out [SEARCH.md](SEARCH.md) for comprehensive examples and in-depth usage guidelines.

### Update Features

Check out [UPDATE.md](UPDATE.md) for comprehensive examples and in-depth usage guidelines.

## Credits

- [Mahmoud Ramadan](https://github.com/mahmoudmohamedramadan)
- [Contributors](https://github.com/mahmoudmohamedramadan/custom-fresh/graphs/contributors)

## Support me

- [PayPal](https://www.paypal.com/paypalme/mmramadan496)

## License

The MIT License (MIT).
