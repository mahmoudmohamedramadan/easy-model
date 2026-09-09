<?php

namespace Ramadan\EasyModel\Tests;

use Ramadan\EasyModel\EasyModel;
use Ramadan\EasyModel\Tests\Models\User;

class UpdatableTest extends TestCase
{
    public function test_it_creates_and_upserts_records(): void
    {
        $created = EasyModel::for(User::class)->performInsert([
            'name'  => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertSame('Alice', $created->name);
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);

        EasyModel::for(User::class)->performUpsert(
            ['name' => 'Alicia', 'email' => 'alice@example.com'],
            ['email']
        );

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com', 'name' => 'Alicia']);
        $this->assertSame(1, User::count());
    }

    public function test_using_model_events_fires_observers_on_mass_updates(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'views' => 1]);
        User::create(['name' => 'Bob', 'email' => 'bob@example.com', 'views' => 1]);

        EasyModel::for(User::class)
            ->usingModelEvents()
            ->incrementEach(['views' => 4]);

        $this->assertSame(2, User::$updatedCount);
        $this->assertSame([5, 5], User::orderBy('id')->pluck('views')->all());
    }

    public function test_it_restores_and_force_deletes_soft_deleted_models(): void
    {
        $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $user->delete();

        $restored = EasyModel::for(User::class)
            ->onlyTrashed()
            ->restore();

        $this->assertSame(1, $restored);
        $this->assertNotSoftDeleted('users', ['email' => 'alice@example.com']);

        EasyModel::for(User::class)
            ->addWheres([['email', 'alice@example.com']])
            ->forceDelete();

        $this->assertDatabaseMissing('users', ['email' => 'alice@example.com']);
    }

    public function test_toggle_and_zero_out_columns(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'is_admin' => false, 'views' => 9]);

        EasyModel::for(User::class)
            ->addWheres([['email', 'alice@example.com']])
            ->toggleColumns(['is_admin'])
            ->zeroOutColumns(['views']);

        $user = User::first();

        $this->assertTrue($user->is_admin);
        $this->assertSame(0, $user->views);
    }
}
