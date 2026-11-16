<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProductBulkImportTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): Company
    {
        $company = Company::create(['name' => 'Bulk Import Business']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'super_admin']);
        $this->actingAs($user)->withoutMiddleware()->withSession([
            'current_tenant_id' => $company->id,
            'active_branch_id' => 'hq',
            'active_branch_name' => 'Headquarters',
        ]);

        return $company;
    }

    private function workbook(array $rows): UploadedFile
    {
        $response = $this->get(route('inventory.Products.import.template'))->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $file = UploadedFile::fake()->createWithContent('products.xlsx', $response->streamedContent());
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Products');
        $this->assertSame('Packaging / measurements', $sheet->getCell('C1')->getValue());
        $this->assertSame('Opening stock quantity', $sheet->getCell('G1')->getValue());
        $this->assertSame('Product Name', $sheet->getCell('A2')->getValue());
        $this->assertSame('C3:C10002', $sheet->getCell('C3')->getDataValidation()->getSqref());
        $this->assertNotNull($spreadsheet->getSheetByName('How to use'));
        $this->assertNull($sheet->getCell('A3')->getValue());
        $sheet->fromArray($rows, null, 'A3');
        $spreadsheet->setActiveSheetIndex(1);
        (new Xlsx($spreadsheet))->save($file->getRealPath());
        $spreadsheet->disconnectWorksheets();

        return $file;
    }

    public function test_simple_workbook_imports_cartons_rolls_and_measured_units(): void
    {
        $company = $this->workspace();
        $this->get(route('product-list'))->assertOk()
            ->assertSee('Download Simple Excel Template')
            ->assertSee('10 cartons')
            ->assertSee('405 pcs');
        $file = $this->workbook([
            ['Sachets', 'Food', 'pcs', 10, 12, null, 2, 3, 4, 250, 180],
            ['Noodles', 'Food', 'pcs', null, null, 40, 10, null, 5, 250, 180, null, null, 'NOODLES-1', '001234567890'],
            ['Rice', 'Food', 'kg', null, null, 25.5, 2, null, 1.5, 1500, 1200],
            ['Tissue', 'Household', 'roll', 12, null, null, 5, 2, null, 500, 400],
            ['Sealed boxes', 'Food', 'carton', null, null, null, 10, null, null, 5000, 4000],
            ['Extra rolls', 'Food', 'pcs', null, 12, null, null, 3, 4, 250, 180],
            ['Measured rolls', 'Food', 'kg', 3, 1.5, null, 2, 1, 0.5, 1500, 1200],
        ]);

        $this->post(route('inventory.Products.import'), ['import_file' => $file])
            ->assertRedirect(route('product-list'))->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 7'));

        foreach ([
            ['Sachets', 'pcs', 280, 120],
            ['Noodles', 'pcs', 405, 40],
            ['Rice', 'kg', 52.5, 25.5],
            ['Tissue', 'roll', 62, 12],
            ['Sealed boxes', 'ctn', 10, 0],
            ['Extra rolls', 'pcs', 40, 0],
            ['Measured rolls', 'kg', 11, 4.5],
        ] as [$name, $unit, $stock, $cartonSize]) {
            $product = Product::withoutGlobalScopes()->where('company_id', $company->id)->where('name', $name)->firstOrFail();
            $this->assertSame($unit, $product->stockUnitSymbol());
            $this->assertEquals($stock, $product->stock);
            $this->assertEquals($cartonSize, $product->units_per_carton);
            $this->assertDatabaseHas('product_branch_stocks', [
                'product_id' => $product->id, 'company_id' => $company->id, 'branch_id' => 'hq', 'quantity' => $stock,
            ]);
        }
        $this->assertDatabaseHas('products', [
            'company_id' => $company->id, 'name' => 'Noodles', 'price' => 250,
            'purchase_price' => 180, 'sku' => 'NOODLES-1', 'barcode' => '001234567890',
        ]);
        $noodles = Product::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Noodles')->firstOrFail();
        $this->assertDatabaseHas('product_units', [
            'product_id' => $noodles->id, 'unit_name' => 'carton',
            'conversion_factor' => 40, 'selling_price' => 10000,
        ]);
        $sachets = Product::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Sachets')->firstOrFail();
        $this->assertEquals(12, $sachets->units_per_roll);
        $this->assertDatabaseHas('product_units', ['product_id' => $sachets->id, 'unit_name' => 'roll', 'conversion_factor' => 12, 'selling_price' => 3000]);
        $this->assertDatabaseHas('product_units', ['product_id' => $sachets->id, 'unit_name' => 'carton', 'conversion_factor' => 120, 'selling_price' => 30000]);
        $this->post(route('inventory.Products.import'), ['import_file' => $file])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 0') && str_contains($message, 'Duplicates skipped: 7'));
        $this->assertDatabaseHas('products', ['id' => $noodles->id, 'stock' => 405]);
        $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $noodles->id, 'branch_id' => 'hq', 'quantity' => 405]);
    }

    public function test_simple_workbook_rejects_bad_quantities_and_existing_updates(): void
    {
        $this->workspace();
        $file = $this->workbook([
            ['Missing size', '', 'pcs', '', '', '', 3, '', 0, 10, 5],
            ['Bad quantity', '', 'kg', '', '', '', '', '', 'oops', 10, 5],
            ['Counted cartons', '', 'carton', '', '', 12, 2, '', 0, 10, 5],
            ['Small carton', '', 'kg', '', '', 0.5, 2, '', 0, 10, 5],
            ['Missing roll size', '', 'pcs', 10, '', '', 2, 3, 4, 10, 5],
            ['Conflicting sizes', '', 'pcs', 10, 12, 40, 2, 0, 0, 10, 5],
            ['Fractional rolls', '', 'pcs', 10, 12, '', 2, 1.5, 0, 10, 5],
            ['Duplicated rolls', '', 'roll', 10, '', '', 2, 2, 1, 10, 5],
            ['Valid', '', 'litre', '', '', '', '', '', 1.5, 10, 5],
        ]);

        $this->post(route('inventory.Products.import'), ['import_file' => $file])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 1'))
            ->assertSessionHas('warning', fn ($message) => str_contains($message, 'Row 3:')
                && str_contains($message, 'Row 4:') && str_contains($message, 'Row 5:')
                && str_contains($message, 'Row 6:') && str_contains($message, 'Row 7:')
                && str_contains($message, 'Row 8:') && str_contains($message, 'Row 9:')
                && str_contains($message, 'Row 10:'));
        $this->assertDatabaseMissing('products', ['name' => 'Missing size']);
        $this->assertDatabaseMissing('products', ['name' => 'Bad quantity']);
        $this->assertDatabaseMissing('products', ['name' => 'Counted cartons']);
        $this->assertDatabaseMissing('products', ['name' => 'Small carton']);
        $this->assertDatabaseMissing('products', ['name' => 'Missing roll size']);
        $this->assertDatabaseMissing('products', ['name' => 'Conflicting sizes']);
        $this->assertDatabaseMissing('products', ['name' => 'Fractional rolls']);
        $this->assertDatabaseMissing('products', ['name' => 'Duplicated rolls']);
        $this->assertDatabaseHas('products', ['name' => 'Valid', 'stock' => 1.5]);

        $this->post(route('inventory.Products.import'), ['import_file' => $file, 'update_existing' => 1])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'new products only'));
        $this->assertDatabaseHas('products', ['name' => 'Valid', 'stock' => 1.5]);

        $incomplete = $this->workbook([['Incomplete', '', 'kg', 2, 10, 12]]);
        $spreadsheet = IOFactory::load($incomplete->getRealPath());
        $spreadsheet->getSheetByName('Products')->setCellValue('E2', '');
        (new Xlsx($spreadsheet))->save($incomplete->getRealPath());
        $spreadsheet->disconnectWorksheets();
        $this->post(route('inventory.Products.import'), ['import_file' => $incomplete])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'missing or repeated columns'));
        $this->assertDatabaseMissing('products', ['name' => 'Incomplete']);
    }

    public function test_c_in_simple_templates_counts_whole_cartons(): void
    {
        $company = $this->workspace();
        $file = $this->workbook([
            ['Lowercase carton', '', 'c', '', '', '', 10, '', '', 5000, 4000],
            ['Uppercase carton', '', ' C ', '', '', '', 3, '', '', 5000, 4000],
            ['Invalid carton', '', 'c', '', '', 12, 2, '', '', 5000, 4000],
        ]);

        $this->post(route('inventory.Products.import'), ['import_file' => $file])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 2'))
            ->assertSessionHas('warning', fn ($message) => str_contains($message, 'Row 5:'));
        foreach (['Lowercase carton' => 10, 'Uppercase carton' => 3] as $name => $stock) {
            $product = Product::withoutGlobalScopes()->where('company_id', $company->id)->where('name', $name)->firstOrFail();
            $this->assertSame('ctn', $product->stockUnitSymbol());
            $this->assertEquals($stock, $product->stock);
            $this->assertDatabaseHas('product_branch_stocks', [
                'company_id' => $company->id, 'product_id' => $product->id, 'branch_id' => 'hq', 'quantity' => $stock,
            ]);
        }
        $this->assertDatabaseMissing('products', ['name' => 'Invalid carton']);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Product Name', 'Category', 'Stock Unit', 'Units per Carton', 'Cartons in Stock', 'Loose Stock'],
            ['Previous template carton', '', 'c', '', '', 4],
        ]);
        $previousFile = UploadedFile::fake()->create('previous-products.xlsx');
        (new Xlsx($spreadsheet))->save($previousFile->getRealPath());
        $spreadsheet->disconnectWorksheets();
        $this->post(route('inventory.Products.import'), ['import_file' => $previousFile])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 1'));
        $previous = Product::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Previous template carton')->firstOrFail();
        $this->assertSame('ctn', $previous->stockUnitSymbol());
        $this->assertEquals(4, $previous->stock);
    }

    public function test_imported_packaging_matches_a_product_added_in_the_app(): void
    {
        $company = $this->workspace();
        $file = $this->workbook([
            ['Imported sachets', 'Food', 'pcs', 10, 12, null, 2, 3, 4, 250, 180],
        ]);
        $this->post(route('inventory.Products.import'), ['import_file' => $file, 'branch_id' => 'hq'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 1'));
        $imported = Product::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Imported sachets')->firstOrFail();

        $this->post(route('inventory.Products.store'), [
            'name' => 'Manual sachets',
            'category_id' => $imported->category_id,
            'measurement_mode' => 'pieces_carton',
            'base_unit_name' => 'pcs',
            'unit_id' => $imported->unit_id,
            'base_unit_id' => $imported->base_unit_id,
            'unit_type' => 'carton',
            'units_per_carton' => 120,
            'units_per_roll' => 12,
            'stock_cartons' => 2,
            'stock_rolls' => 3,
            'stock_units' => 4,
            'price' => 250,
            'purchase_price' => 180,
            'branch_id' => 'hq',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $manual = Product::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Manual sachets')->firstOrFail();

        foreach (['stock', 'units_per_carton', 'units_per_roll', 'price', 'purchase_price'] as $field) {
            $this->assertEquals($manual->$field, $imported->$field, $field);
        }
        $this->assertSame($manual->stockUnitSymbol(), $imported->stockUnitSymbol());
        $this->assertEqualsCanonicalizing($manual->salesUnitConfiguration()['options'], $imported->salesUnitConfiguration()['options']);
        foreach ([$manual, $imported] as $product) {
            $this->assertDatabaseHas('product_branch_stocks', ['product_id' => $product->id, 'company_id' => $company->id, 'branch_id' => 'hq', 'quantity' => 280]);
        }
        $this->get(route('product-list'))->assertOk()->assertSee('Imported sachets');
    }

    public function test_previous_simple_workbook_keeps_its_original_stock_calculation(): void
    {
        $this->workspace();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Product Name', 'Category', 'Stock Unit', 'Units per Carton', 'Cartons in Stock', 'Loose Stock', 'Selling Price', 'Cost Price'],
            ['Old noodles', 'Food', 'pcs', 40, 10, 5, 250, 180],
            ['Old cartons', 'Food', 'carton', null, null, 10, 5000, 4000],
        ]);
        $file = UploadedFile::fake()->create('old-products.xlsx');
        (new Xlsx($spreadsheet))->save($file->getRealPath());
        $spreadsheet->disconnectWorksheets();

        $this->post(route('inventory.Products.import'), ['import_file' => $file])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 2'));
        $this->assertDatabaseHas('products', ['name' => 'Old noodles', 'stock' => 405]);
        $this->assertDatabaseHas('products', ['name' => 'Old cartons', 'stock' => 10]);
    }

    public function test_previous_roll_workbook_remains_compatible(): void
    {
        $this->workspace();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Product Name', 'Category', 'Stock Unit', 'Number of Cartons', 'Rolls per Carton', 'Pcs / Units per Roll', 'Units per Carton (no rolls)', 'Extra Rolls', 'Extra Pieces / Stock Units', 'Selling Price', 'Cost Price'],
            ['Earlier sachets', 'Food', 'pcs', 2, 10, 12, null, 3, 4, 250, 180],
        ]);
        $file = UploadedFile::fake()->create('earlier-products.xlsx');
        (new Xlsx($spreadsheet))->save($file->getRealPath());
        $spreadsheet->disconnectWorksheets();

        $this->post(route('inventory.Products.import'), ['import_file' => $file])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 1'));
        $this->assertDatabaseHas('products', ['name' => 'Earlier sachets', 'stock' => 280, 'units_per_carton' => 120, 'units_per_roll' => 12]);
    }

    public function test_legacy_csv_still_imports_without_changing_its_stock_rules(): void
    {
        $this->workspace();
        $csv = "name,unit,unit_type,units_per_carton,stock_cartons,stock_units,stock,retail_price\n"
            . "Legacy noodles,pcs,carton,40,10,5,405,250\n"
            . "Legacy rice,kg,unit,0,0,1.5,999,1500\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->post(route('inventory.Products.import'), ['import_file' => $file])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 2'));
        $this->assertDatabaseHas('products', ['name' => 'Legacy noodles', 'stock' => 405]);
        $this->assertDatabaseHas('products', ['name' => 'Legacy rice', 'stock' => 1.5]);
    }

    public function test_failed_simple_row_does_not_leave_product_or_stock_behind(): void
    {
        $this->workspace();
        $file = $this->workbook([
            ['Failed item', 'Rollback category', 'pcs', '', '', 40, 2, '', 1, 10, 5],
            ['Good item', 'Food', 'pcs', '', '', '', '', '', 3, 10, 5],
        ]);
        DB::statement("CREATE TRIGGER fail_carton_unit BEFORE INSERT ON product_units
            WHEN NEW.unit_name = 'carton' BEGIN SELECT RAISE(ABORT, 'test failure'); END");

        $this->post(route('inventory.Products.import'), ['import_file' => $file])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Created: 1'))
            ->assertSessionHas('warning', fn ($message) => str_contains($message, 'Row 3:'));
        $this->assertDatabaseMissing('products', ['name' => 'Failed item']);
        $this->assertDatabaseMissing('categories', ['name' => 'Rollback category']);
        $this->assertDatabaseHas('products', ['name' => 'Good item', 'stock' => 3]);
    }
}
