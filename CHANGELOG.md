# Release Notes for 1.x

## [Unreleased](https://github.com/mahmoudmohamedramadan/easy-model/compare/v1.0.9...1.x)

## [v1.0.9](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.9)

- [1.x] Fixes the issue of ordering the result by the same model and its relationship column.
- [1.x] Adds the ability to pass closures to the `addWheres` and `addOrWheres`.

## [v1.0.8](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.8)

- [1.x] Adds the ability to order the result using `HasOne`, `HasMany`, `BelongsTo`, and `BelongsToMany` relationships.
- [1.x] Fixes the issue of ordering the result by model relationship.
- [1.x] Optimizes the query time.
- [1.x] Improves the code's readability.
- [1.x] Adds the ability to use the `Local Scopes` and `Global Scopes`.

## [v1.0.7](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.7)

- [1.x] Fixes the issue of searching in the relationship with an anonymous model.
- [1.x] Fixes the issue of searching within the model itself.
- [1.x] Renames the `addAllWheres` and `addOrAllWheres` to `addRelationConditions` and `addOrRelationConditions`.
- [1.x] Improves the code's readability.
- [1.x] Updates the `order by` query by replacing the foreign with the primary key.

## [v1.0.6](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.6)

- [1.x] Optimizes the query time.
- [1.x] Adds the `ext-pdo` to the `require` key.
- [1.x] Adds the `laravel/framework` to the `require` key.
- [1.x] Removes the `illuminate/database` from the `require` key.
- [1.x] Removes the `illuminate/contracts` from the `require` key.
- [1.x] Removes the `illuminate/pagination` from the `require` key.

## [v1.0.5](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.5)

- [1.x] Refactors the `getEloquentBuilder` and `getQueryBuilder` methods.
- [1.x] Improves the code's readability.
- [1.x] Fixes the search issue when providing a relationship.

## [v1.0.4](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.4)

- [1.x] Refactors the `addOrderBy` method.
- [1.x] Improves the code's readability.

## [v1.0.3](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.3)

- [1.x] Adds the ability to order the model by its relationships.
- [1.x] Improves the code's readability.

## [v1.0.2](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.2)

- [1.x] Adds the ability to easily switch between `Eloquent Builder` and `Query Builder`.
- [1.x] Executes an `Eloquent Builder` instead of a `Query Builder`.
- [1.x] Fixes assigning the correct query type issue.

## [v1.0.1](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.1)

- [1.x] Speeds up the query time using the `Query Builder` instead of `Eloquent Builder`.
- [1.x] Adds the ability to order the results using `addOrderBy`.
- [1.x] Renames the methods to be more descriptive.

## [v1.0.0](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.0)

- [1.x] Releases the package. 🎉

## [v1.0.0 (alpha.3)](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.0-alpha.3)

- [1.x] Renames the method that adds all relationships checking to `addAllWheres` and `addAllOrWheres`.
- [1.x] Adds the ability to search the model using `addWheres` and `addOrWheres`.
- [1.x] Adds the ability to search the model relationships using `addWheres` and `addOrWheres`.

## [v1.0.0 (alpha.2)](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.0-alpha.2)

- [1.x] Adds the ability to search in the model relationships using `addWhereRelation` and `addOrWhereRelation`.

## [v1.0.0 (alpha.1)](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.0-alpha.1)

- [1.x] Initials pre-release.
