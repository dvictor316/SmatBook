<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('name', 120);
                $table->string('symbol', 30);
                $table->string('status', 20)->default('active')->index();
                $table->timestamps();

                $table->unique(['company_id', 'symbol']);
            });
        }

        $now = now();
        foreach ($this->defaultUnits() as [$name, $symbol]) {
            DB::table('units')->updateOrInsert(
                ['company_id' => null, 'symbol' => $symbol],
                ['name' => $name, 'status' => 'active', 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }

    private function defaultUnits(): array
    {
        return [
            ['Piece', 'pcs'],
            ['Kilogram', 'kg'],
            ['Gram', 'g'],
            ['Litre', 'L'],
            ['Millilitre', 'ml'],
            ['Metre', 'm'],
            ['Carton', 'ctn'],
            ['Pack', 'pack'],
            ['Dozen', 'doz'],
            ['Bag', 'bag'],
            ['Bottle', 'bottle'],
        ];
    }
};
