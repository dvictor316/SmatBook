<?php
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
$projectRoot = dirname(__DIR__, 2);
$output = getenv('RESPONSIVE_FIXTURES') ?: sys_get_temp_dir().'/smartprobook-responsive';
if (!is_dir($output)) { mkdir($output, 0700, true); }
require $projectRoot.'/vendor/autoload.php';
$app = require $projectRoot.'/bootstrap/app.php';
if ($app->configurationIsCached()) {
    throw new RuntimeException('Run browser fixtures in a local checkout without cached production configuration.');
}
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'session.driver' => 'array', 'mail.default' => 'array', 'queue.default' => 'sync', 'app.url' => 'http://localhost', 'app.domain' => 'localhost', 'app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
foreach (['home' => '/', 'about' => '/about-us', 'pricing' => '/membership-plans'] as $file => $path) {
    $request = Illuminate\Http\Request::create('http://localhost'.$path);
    $response = $kernel->handle($request);
    file_put_contents($output.'/'.$file.'.html', $response->getContent());
    echo $file.': '.$response->getStatusCode().PHP_EOL;
    $kernel->terminate($request, $response);
}
// Render an authenticated agent dashboard through the same Blade layouts without touching a live database.
$company = App\Models\Company::create(['name' => 'Responsive Test', 'plan' => 'Enterprise']);
$user = App\Models\User::factory()->create(['role' => 'agent', 'company_id' => $company->id]);
Illuminate\Support\Facades\Auth::login($user);
session(['current_tenant_id' => $company->id]);
$request = Illuminate\Http\Request::create('http://localhost/agent/dashboard');
$request->setLaravelSession(app('session.store'));
$request->setUserResolver(fn () => $user);
$route = app('router')->getRoutes()->getByName('agent.dashboard');
$route->bind($request);
$request->setRouteResolver(fn () => $route);
$app->instance('request', $request);
app('url')->setRequest($request);
view()->share('errors', new Illuminate\Support\ViewErrorBag);
$html = app(App\Http\Controllers\AgentPortalController::class)->dashboard()->render();
file_put_contents($output.'/dashboard.html', $html);
echo 'dashboard: rendered'.PHP_EOL;
$user->update(['role' => 'state_manager']);
App\Models\DeploymentManager::create(['user_id' => $user->id, 'status' => 'active']);
Illuminate\Support\Facades\Cache::flush();
file_put_contents($output.'/manager.html', app(App\Http\Controllers\AgentPortalController::class)->dashboard()->render());
echo 'manager: rendered'.PHP_EOL;
