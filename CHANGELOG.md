# Release Notes for 1.x

## [Unreleased](https://github.com/mahmoudmohamedramadan/easy-model/compare/v1.2.0...1.x)

## [v1.2.0](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.2.0)

- [1.x] Adds an `EasyModelException` marker interface implemented by every exception thrown by the package, so consumers can `catch (EasyModelException $e)` to handle any package error in one block.
- [1.x] Adds named constructors to all three exceptions (`InvalidModel::notAModelClass()`, `InvalidModel::softDeletesNotUsed()`, `InvalidArrayStructure::invalidWhereTuple()`, `InvalidOrderableRelationship::morphToCannotBeJoined()`, etc.) for centralized, consistent error messages.
- [1.x] `InvalidModel` and `InvalidOrderableRelationship` now extend `\LogicException`; `InvalidArrayStructure` now extends `\InvalidArgumentException`. All three remain catchable by their original FQCN — this is a non-breaking enrichment.
- [1.x] Adds `EasyModelServiceProvider` with Laravel auto-discovery, a publishable `config/easy-model.php`, and an `EasyModel` Facade so the package can be consumed without dragging the trait into a controller (`EasyModel::for(User::class)->...`).
- [1.x] Adds `addOrderByCount()` and `addOrderByAggregate()` helpers (count / sum / avg / min / max) for ordering by a relationship aggregate.
- [1.x] Adds `addKeywordSearch($keyword, $columns, $strict)` for grouped multi-column `LIKE` / `=` searches.
- [1.x] Adds `addWhereIn`, `addWhereNotIn`, `addWhereNull`, `addWhereNotNull`, and `addWhereBetween` array-driven helpers.
- [1.x] Adds first-class join support in `addOrderBy` for `MorphOne`, `MorphMany`, `MorphToMany`, `HasOneThrough`, and `HasManyThrough` relationships.
- [1.x] Adds `flushSearchable()` / `flushUpdatable()` to reset internal trait state for safe reuse within the same request.
- [1.x] Adds an Eloquent / Query Builder `keywordSearch()` macro and `orderByCountRelation()` / `orderByAggregateRelation()` macros.
- [1.x] Fixes a `reset()` reference bug in `prepareWhereConditions` that warned (and silently kept only the first array value) when an array was passed as a `where` value. The method now defers to the public `Builder::where()` API which natively handles arrays, nulls, expressions, and sub-selects.
- [1.x] Fixes wrong foreign-key resolution for `BelongsTo` ordering (now uses `getForeignKeyName()` / `getOwnerKeyName()` instead of the related model's conventional default).
- [1.x] Fixes `BelongsToMany` ordering, which previously generated invalid SQL by skipping the pivot table; the package now emits the correct `parent → pivot → related` joins, including the morph type filter for `MorphToMany`.
- [1.x] Fixes silent row-loss when ordering by a relationship: joins now default to `LEFT JOIN`, the base table's columns are explicitly selected (`SELECT {base}.*`) so child columns do not overwrite parent ones, and identical relationship paths are deduplicated within the same chain.
- [1.x] Fixes `match` without a `default` arm in `buildQueryUsingWheres`; an `InvalidArrayStructure` exception is now thrown with a helpful message.
- [1.x] Fixes `setSearchableQuery(EloquentBuilder)` / `setUpdatableQuery(EloquentBuilder)` silently downgrading to a `QueryBuilder` and losing observers, global scopes, casts, and timestamps.
- [1.x] Fixes `includeSoftDeleted()` silently no-oping when the model doesn't use the `SoftDeletes` trait — it now throws `InvalidModel` with a clear message.

## [v1.1.9](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.9)

- [1.x] Adds compatibility to support **Laravel v13** and introduces a refreshed package logo.

## [v1.1.8](https://github.com/mahmoudmohamedramadan/easy-model/releases/tag/v1.1.8)

- [1.x] Updates the internal method names.
- [1.x] Removes all additional parameter types.
- [1.x] Adds the needed parameter types.

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
