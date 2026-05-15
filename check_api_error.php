<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'staff3@gmail.com')->first();
$tenant = App\Models\Tenant::find($user->tenant_id);

config(['database.connections.tenant.database' => $tenant->tenancy_db_name]);
\DB::purge('tenant');
\DB::reconnect('tenant');

$query = App\Models\Lead::orderBy('id', 'desc');
$query->whereDate('est_contract_date', '2026-04-08');
$query->where('phone', 'like', '%098900999%');
$query->where('business_name', 'like', '%K%');

try {
    $leads = $query->get();
    echo "Success! Leads found: " . $leads->count() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
