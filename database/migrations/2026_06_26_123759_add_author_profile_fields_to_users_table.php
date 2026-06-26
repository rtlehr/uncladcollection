<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthorProfileFieldsToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('author_title')->nullable()->after('username');
            $table->text('author_bio')->nullable()->after('author_title');
            $table->string('author_website_url')->nullable()->after('author_bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'author_title',
                'author_bio',
                'author_website_url',
            ]);
        });
    }
}