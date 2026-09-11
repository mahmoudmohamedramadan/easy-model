<?php

namespace Ramadan\EasyModel\Tests;

use Ramadan\EasyModel\EasyModel;
use Ramadan\EasyModel\Tests\Models\Post;
use Ramadan\EasyModel\Tests\Models\Role;
use Ramadan\EasyModel\Tests\Models\User;

class OrderableTest extends TestCase
{
    public function test_it_aliases_joins_when_two_relations_share_a_table(): void
    {
        $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $bob   = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);
        $carol = User::create(['name' => 'Carol', 'email' => 'carol@example.com']);

        Post::create(['user_id' => $alice->id, 'editor_id' => $carol->id, 'title' => 'First']);
        Post::create(['user_id' => $bob->id, 'editor_id' => $alice->id, 'title' => 'Second']);

        $sql = EasyModel::for(Post::class)
            ->addOrderBy([
                ['author.name' => 'asc'],
                ['editor.name' => 'desc'],
            ])
            ->toSql();

        $this->assertStringContainsString('users_2', $sql);

        $titles = EasyModel::for(Post::class)
            ->addOrderBy([
                ['author.name' => 'asc'],
                ['editor.name' => 'desc'],
            ])
            ->execute()
            ->pluck('title')
            ->all();

        $this->assertSame(['First', 'Second'], $titles);
    }

    public function test_it_orders_by_belongs_to_many_without_losing_rows(): void
    {
        $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $bob   = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

        $admin = Role::create(['name' => 'Admin']);
        $guest = Role::create(['name' => 'Guest']);

        $alice->roles()->attach($admin);
        $bob->roles()->attach($guest);

        $names = EasyModel::for(User::class)
            ->addOrderBy([['roles.name' => 'asc']])
            ->execute()
            ->pluck('name')
            ->all();

        $this->assertSame(['Alice', 'Bob'], $names);
    }

    public function test_it_orders_by_relationship_count(): void
    {
        $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $bob   = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

        Post::create(['user_id' => $alice->id, 'title' => 'One']);
        Post::create(['user_id' => $alice->id, 'title' => 'Two']);
        Post::create(['user_id' => $bob->id, 'title' => 'Three']);

        $names = EasyModel::for(User::class)
            ->addOrderByCount('posts', 'desc')
            ->execute()
            ->pluck('name')
            ->all();

        $this->assertSame(['Alice', 'Bob'], $names);
    }
}
