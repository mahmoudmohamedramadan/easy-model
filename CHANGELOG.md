# Release Notes for 1.x

## [Unreleased](https://github.com/mahmoudmohamedramadan/easy-model/compare/v1.1.7...1.x)

## [v1.1.7](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.7)

- [1.x] Fixes deprecated declarations of nullable parameters.

## [v1.1.6](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.6)

- [1.x] Adds the compatibility to support **Laravel v12**.

## [v1.1.5](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.5)

- [1.x] Restores the `resolveModel` method.

## [v1.1.4](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.4)

- [1.x] Removes the `resolveModel` method.

## [v1.1.3](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.3)

- [1.x] Adds the `fetchBuilder` method.
- [1.x] Adds the `setSearchableQuery` method.
- [1.x] Adds the `setUpdatableQuery` method.
- [1.x] Adds the ability to specify the builder type that is getting back.
- [1.x] Removes the `updateOrCreateModel` and `updateOrCreateRelationship` methods.
- [1.x] Removes the `setChainableModel` method.
- [1.x] Updates the return type of the `setUpdatableModel` method.
- [1.x] Fixes updating an empty array of togglable columns.
- [1.x] Fixes the model serialization issue encountered in the `toggleColumns` method.
- [1.x] Fixes populating the `updated_at` column when incrementing and decrementing values.
- [1.x] Fixes toggling many columns at a bunch of records.
- [1.x] Fixes updating single model instances.

## [v1.1.2](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.2)

- [1.x] Adds the `zeroOutColumns` method.
- [1.x] Adds the `toggleColumns` method.
- [1.x] Adds the ability to execute update operations using `Query Builder` and `Eloquent Builder`.
- [1.x] Updates the functionality of incrementing and decrementing the columns.

## [v1.1.1](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.1)

- [1.x] Adds the `Updatable` trait.
- [1.x] Adds the `ignoreGlobalScopes` method.
- [1.x] Adds the `includeSoftDeleted` method.
- [1.x] Renames the `setModel` method to `setSearchableModel`.
- [1.x] Refactors the code.
- [1.x] Improves the code's readability.

## [v1.1.0](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.0)

- [1.x] Refactors the code.
- [1.x] Fixes the providing columns and values only to the `addWheres` and `addOrWheres` methods.

## [v1.0.9](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.9)

- [1.x] Adds the ability to pass closures to `addWheres` and `addOrWheres` methods.
- [1.x] Fixes the result of ordering by the same column in the model and its relationship.

## [v1.0.8](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.8)

- [1.x] Adds the ability to order the result using `HasOne`, `HasMany`, `BelongsTo`, and `BelongsToMany` relationships.
- [1.x] Adds the ability to use `Local Scopes` and `Global Scopes`.
- [1.x] Improves the code's readability.
- [1.x] Optimizes the query time.
- [1.x] Fixes the result of ordering by model relationship.

## [v1.0.7](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.7)

- [1.x] Updates the `order by` query by replacing the foreign with the primary key.
- [1.x] Improves the code's readability.
- [1.x] Renames the `addAllWheres` and `addOrAllWheres` methods to `addRelationConditions` and `addOrRelationConditions`.
- [1.x] Fixes searching in the model itself.
- [1.x] Fixes searching in the relationship with an anonymous model.

## [v1.0.6](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.6)

- [1.x] Adds the `ext-pdo` to `require` key.
- [1.x] Adds the `laravel/framework` to `require` key.
- [1.x] Optimizes the query time.
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

- [1.x] Speeds up the query time using `Query Builder` instead of `Eloquent Builder`.
- [1.x] Adds the ability to order the results using the `addOrderBy` method.
- [1.x] Renames the methods to be more descriptive.

## [v1.0.0](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.0)

- [1.x] Releases the package. 🎉

## [v1.0.0 (alpha.3)](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.0-alpha.3)

- [1.x] Renames the method that adds all relationships checking to `addAllWheres` and `addAllOrWheres` methods.
- [1.x] Adds the ability to search within the models and their relationships using `addWheres` and `addOrWheres` methods.

## [v1.0.0 (alpha.2)](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.0-alpha.2)

- [1.x] Adds the ability to search within model relationships using `addWhereRelation` and `addOrWhereRelation` methods.

## [v1.0.0 (alpha.1)](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.0.0-alpha.1)

- [1.x] Initials pre-release.
