<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->defaults() as [$name, $symbol]) {
            Unit::query()->updateOrCreate(
                ['company_id' => null, 'symbol' => $symbol],
                ['name' => $name, 'status' => 'active']
            );
        }
    }

    private function defaults(): array
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
}
