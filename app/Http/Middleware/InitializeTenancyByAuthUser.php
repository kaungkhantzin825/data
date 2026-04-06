<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InitializeTenancyByAuthUser
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

           
            if ($user->is_admin) {
                return $next($request);
            }

            if ($user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);

                if ($tenant && $tenant->tenancy_db_name) {
                    config([
                        'database.connections.tenant.database' => $tenant->tenancy_db_name,
                    ]);

                    DB::purge('tenant');
                    DB::reconnect('tenant');
                }
            }
        }

        return $next($request);
    }
}
