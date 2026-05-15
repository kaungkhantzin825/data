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

$uid = 8856;
if ($uid) {
    echo "Filtering by UID...\n";
    // wait im simulating what exists right now
}

$statusParam = '8001';

if ($statusParam) {
    if (is_numeric($statusParam)) {
        $option = App\Models\TenantFieldOption::find($statusParam);
        if ($option) {
            echo "Found option: " . $option->option_value . "\n";
            $query->where('status', $option->option_value);
        } else {
            echo "Option not found. Searching strict 8001\n";
            $query->where('status', $statusParam);
        }
    }
}

$leads = $query->get();
echo "Leads count with status 8001: " . $leads->count() . "\n";
