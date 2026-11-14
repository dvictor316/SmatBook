<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 30)->nullable();
            }
            if (! Schema::hasColumn('users', 'dob')) {
                $table->string('dob', 40)->nullable();
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (! Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['gender', 'dob', 'address', 'city', 'postal_code'],
            fn (string $column): bool => Schema::hasColumn('users', $column)
        ));

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
