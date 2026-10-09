<?php

namespace Tests\Unit;

use App\Support\ModuleCatalog;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ModuleCatalogTest extends TestCase
{
    public function test_specialist_registration_choices_resolve_only_their_dedicated_plans(): void
    {
        $this->assertSame('hotel', ModuleCatalog::dedicatedPlanForOperation('hotel'));
        $this->assertSame('livestock', ModuleCatalog::dedicatedPlanForOperation('livestock'));
        $this->assertNull(ModuleCatalog::dedicatedPlanForOperation('general'));
        $this->assertNull(ModuleCatalog::dedicatedPlanForOperation('manufacturing'));
    }

    public function test_catalogued_module_routes_exist_and_projects_are_professional_only(): void
    {
        foreach (ModuleCatalog::modules() as $module) {
            if ($module['route']) {
                $this->assertTrue(Route::has($module['route']), "Missing route for {$module['title']}");
            }
        }

        $projectMiddleware = Route::getRoutes()->getByName('projects.index')?->gatherMiddleware() ?? [];
        $this->assertContains('plan.access:pro,enterprise', $projectMiddleware);
    }

    public function test_specialist_workspaces_keep_both_plan_and_tenant_guards(): void
    {
        $hotelMiddleware = Route::getRoutes()->getByName('hotel.dashboard')?->gatherMiddleware() ?? [];
        $livestockMiddleware = Route::getRoutes()->getByName('livestock.dashboard')?->gatherMiddleware() ?? [];

        $this->assertContains('plan.access:hotel', $hotelMiddleware);
        $this->assertContains('hotel.tenant', $hotelMiddleware);
        $this->assertContains('plan.access:enterprise', $livestockMiddleware);
        $this->assertContains('livestock.tenant', $livestockMiddleware);
    }
}
