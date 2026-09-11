<?php

namespace Ramadan\EasyModel\Tests;

use Ramadan\EasyModel\EasyModel;
use Ramadan\EasyModel\Tests\Models\Comment;
use Ramadan\EasyModel\Tests\Models\Post;
use Ramadan\EasyModel\Tests\Models\User;

class SearchableTest extends TestCase
{
    public function test_it_filters_with_when_and_unless(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

        $names = EasyModel::for(User::class)
            ->when(true, fn($query) => $query->addWheres([['name', 'Alice']]))
            ->unless(true, fn($query) => $query->addWheres([['name', 'Bob']]))
            ->execute()
            ->pluck('name')
            ->all();

        $this->assertSame(['Alice'], $names);
    }

    public function test_it_searches_keywords_on_related_columns(): void
    {
        $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $bob   = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

        Post::create(['user_id' => $alice->id, 'title' => 'Learning Laravel']);
        Post::create(['user_id' => $bob->id, 'title' => 'Shipping Rust']);

        $names = EasyModel::for(User::class)
            ->addKeywordSearch('Laravel', ['name', 'posts.title'])
            ->execute()
            ->pluck('name')
            ->all();

        $this->assertSame(['Alice'], $names);
    }

    public function test_it_filters_by_date_and_period(): void
    {
        $today    = User::create(['name' => 'Today', 'email' => 'today@example.com']);
        $lastWeek = User::create(['name' => 'Old', 'email' => 'old@example.com']);

        $today->forceFill(['created_at' => now()])->save();
        $lastWeek->forceFill(['created_at' => now()->subWeek()])->save();

        $todayNames = EasyModel::for(User::class)
            ->addWhereDate([['created_at', now()->toDateString()]])
            ->execute()
            ->pluck('name')
            ->all();

        $periodNames = EasyModel::for(User::class)
            ->addWherePeriod('created_at', now()->subDay(), now()->addDay())
            ->execute()
            ->pluck('name')
            ->all();

        $this->assertSame(['Today'], $todayNames);
        $this->assertSame(['Today'], $periodNames);
    }

    public function test_it_filters_json_contains(): void
    {
        Post::create([
            'user_id' => User::create(['name' => 'Alice', 'email' => 'alice@example.com'])->id,
            'title'   => 'Tagged',
            'meta'    => ['tags' => ['laravel', 'php']],
        ]);
        Post::create([
            'user_id' => User::create(['name' => 'Bob', 'email' => 'bob@example.com'])->id,
            'title'   => 'Other',
            'meta'    => ['tags' => ['rust']],
        ]);

        $titles = EasyModel::for(Post::class)
            ->addWhereJsonContains([['meta->tags' => 'laravel']])
            ->execute()
            ->pluck('title')
            ->all();

        $this->assertSame(['Tagged'], $titles);
    }

    public function test_it_eager_loads_and_paginates_without_execute(): void
    {
        $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        Post::create(['user_id' => $user->id, 'title' => 'First']);
        Post::create(['user_id' => $user->id, 'title' => 'Second']);

        $page = EasyModel::for(User::class)
            ->addWith(['posts'])
            ->addWithCount(['posts'])
            ->paginate(15);

        $this->assertSame(1, $page->total());
        $this->assertTrue($page->first()->relationLoaded('posts'));
        $this->assertSame(2, $page->first()->posts_count);
    }

    public function test_it_restricts_results_to_only_trashed_models(): void
    {
        $alive = User::create(['name' => 'Alive', 'email' => 'alive@example.com']);
        $gone  = User::create(['name' => 'Gone', 'email' => 'gone@example.com']);
        $gone->delete();

        $trashed = EasyModel::for(User::class)
            ->onlyTrashed()
            ->execute()
            ->pluck('name')
            ->all();

        $this->assertSame(['Gone'], $trashed);
        $this->assertNotContains($alive->name, $trashed);
    }

    public function test_to_sql_returns_the_compiled_query(): void
    {
        $sql = EasyModel::for(User::class)
            ->addWheres([['name', 'Alice']])
            ->toSql();

        $this->assertStringContainsString('select', strtolower($sql));
        $this->assertStringContainsString('name', $sql);
    }

    public function test_it_orders_by_nested_relationship_columns(): void
    {
        $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $bob   = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

        $first  = Post::create(['user_id' => $alice->id, 'title' => 'A']);
        $second = Post::create(['user_id' => $bob->id, 'title' => 'B']);

        Comment::create(['post_id' => $first->id, 'body' => 'older']);
        $latest = Comment::create(['post_id' => $second->id, 'body' => 'newer']);
        $latest->forceFill(['created_at' => now()->addMinute()])->save();

        $names = EasyModel::for(User::class)
            ->addOrderBy([['posts.comments.created_at' => 'desc']])
            ->execute()
            ->pluck('name')
            ->all();

        $this->assertSame(['Bob', 'Alice'], $names);
    }
}
