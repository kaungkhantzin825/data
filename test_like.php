<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force tenant connection
$user = App\Models\User::first();
if($user) {
    $tenant = App\Models\Tenant::find($user->tenant_id);
    if($tenant) {
        config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
        \DB::purge('tenant');
        \DB::reconnect('tenant');
    }
}

$leads = App\Models\Lead::where('business_name', 'like', '%ABC Company%')->get();
echo "Found " . count($leads) . " leads with ABC Company\n";
