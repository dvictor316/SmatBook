<?php

namespace Tests\Feature;

use App\Models\Company;
use Tests\TestCase;

class DesktopAppDownloadTest extends TestCase
{
    public function test_business_dashboard_shows_install_and_open_actions(): void
    {
        $html = view('SuperAdmin.partials._desktop_app_download', [
            'company' => new Company(),
        ])->render();

        $this->assertStringContainsString('Install offline app', $html);
        $this->assertStringContainsString('Open offline app', $html);
        $this->assertStringContainsString(
            'https://github.com/dvictor316/SmatBook/releases/latest/download/SmartProbook-Offline-Installer.msi',
            $html
        );
        $this->assertStringContainsString('smartprobook://open/pos', $html);
        $this->assertStringContainsString('flex-sm-row', $html);
    }
}
