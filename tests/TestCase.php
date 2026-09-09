<?php

namespace Ramadan\EasyModel\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Ramadan\EasyModel\EasyModelServiceProvider;
use Ramadan\EasyModel\Tests\Models\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        User::$updatedCount = 0;
    }

    protected function getPackageProviders($app): array
    {
        return [
            EasyModelServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->unsignedInteger('country_id')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->boolean('is_admin')->default(false);
            $table->boolean('published')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('editor_id')->nullable()->constrained('users');
            $table->string('title');
            $table->unsignedInteger('share_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained();
            $table->string('body');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->primary(['role_id', 'user_id']);
        });
    }
}
