<?php

namespace Ramadan\EasyModel\Tests;

use Ramadan\EasyModel\EasyModel;
use Ramadan\EasyModel\Tests\Models\Post;
use Ramadan\EasyModel\Tests\Models\User;

class RequestFiltersTest extends TestCase
{
    public function test_it_applies_allowlisted_request_filters_sorts_and_search(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'country_id' => 1]);
        User::create(['name' => 'Bob', 'email' => 'bob@example.com', 'country_id' => 2]);
        User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'country_id' => 1]);

        $names = EasyModel::for(User::class)
            ->fromRequest([
                'filter' => [
                    'country_id' => [1],
                    'email'      => '%@example.com',
                    'is_admin'   => '1',
                ],
                'sort'   => '-name',
                'search' => 'li',
            ])
            ->allowedFilters(['country_id', 'email', 'name'])
            ->allowedSorts(['name'])
            ->allowedSearch(['name', 'email'])
            ->execute()
            ->pluck('name')
            ->all();

        $this->assertSame(['Alice'], $names);
    }

    public function test_it_ignores_filters_and_sorts_that_are_not_allowlisted(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'country_id' => 1]);
        User::create(['name' => 'Bob', 'email' => 'bob@example.com', 'country_id' => 2]);

        $names = EasyModel::for(User::class)
            ->fromRequest([
                'filter' => ['country_id' => 1],
                'sort'   => '-name',
            ])
            ->allowedFilters(['name'])
            ->allowedSorts(['email'])
            ->execute()
            ->orderBy('id')
            ->pluck('name')
            ->all();

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function test_it_filters_related_columns_and_eager_loads_includes(): void
    {
        $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $bob   = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

        Post::create(['user_id' => $alice->id, 'title' => 'Easy Model']);
        Post::create(['user_id' => $bob->id, 'title' => 'Something Else']);

        $users = EasyModel::for(User::class)
            ->fromRequest([
                'filter'  => ['posts.title' => '%Easy%'],
                'include' => 'posts,roles',
            ])
            ->allowedFilters(['posts.title'])
            ->allowedIncludes(['posts'])
            ->execute()
            ->get();

        $this->assertCount(1, $users);
        $this->assertSame('Alice', $users->first()->name);
        $this->assertTrue($users->first()->relationLoaded('posts'));
        $this->assertFalse($users->first()->relationLoaded('roles'));
    }
}
