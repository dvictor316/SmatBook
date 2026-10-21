<?php

namespace Tests\Feature;

use App\Support\PageSize;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ListPaginationTest extends TestCase
{
    public function test_web_page_sizes_default_to_500_and_reject_unbounded_or_malformed_inputs(): void
    {
        foreach ([[null, 500], ['25', 25], ['250', 250], ['10000', 500], ['0', 500], ['-1', 500], ['all', 500], [['500'], 500]] as [$input, $expected]) {
            $this->app->instance('request', Request::create('/list', 'GET', $input === null ? [] : ['per_page' => $input]));
            $this->assertSame($expected, PageSize::resolve());
        }
    }

    public function test_shared_pagination_shows_size_controls_even_on_one_page_and_preserves_filters(): void
    {
        $this->app->instance('request', Request::create('/list', 'GET', ['search' => 'Rice', 'branch_id' => 'hq', 'per_page' => 25]));
        $paginator = new LengthAwarePaginator(range(1, 25), 70, 25, 1, ['path' => '/list']);
        $html = $paginator->links('pagination::bootstrap-5')->toHtml();
        $this->assertStringContainsString('Rows per page', $html);
        $this->assertStringContainsString('value="500"', $html);
        $this->assertStringContainsString('search=Rice', $html);
        $this->assertStringContainsString('branch_id=hq', $html);
        $this->assertStringContainsString('per_page=25', $html);
        $single = new LengthAwarePaginator([1], 1, 500, 1, ['path' => '/list']);
        $this->assertStringContainsString('Rows per page', $single->links()->toHtml());
        $this->assertStringContainsString('of 1 results', $single->links()->toHtml());
    }
}
