<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantFieldOption;
use Illuminate\Http\Request;

class TenantFieldController extends Controller
{
   
    private function getOwnerIntId(): int
    {
        $user = auth()->user();

        if ($user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
            if ($tenant) {
                return (int) $tenant->user_id;
            }
        }

        
        return (int) $user->id;
    }

    public function index()
    {
        $ownerIntId = $this->getOwnerIntId();
        $fields = TenantFieldOption::where('tenant_id', $ownerIntId)->orderBy('created_at')->get();

        return \Inertia\Inertia::render('DropdownSettings', [
            'options' => $fields
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'field_name'   => 'required|string|in:biz_type,source,division,township,product,channel,package,status',
            'option_value' => 'required|string',
            'plan'         => 'nullable|string',
            'weight'       => 'nullable|string'
        ]);

        $ownerIntId = $this->getOwnerIntId();

        TenantFieldOption::create([
            'tenant_id'    => $ownerIntId,
            'field_name'   => $request->field_name,
            'option_value' => trim($request->option_value),
            'plan'         => $request->plan ? trim($request->plan) : null,
            'weight'       => $request->weight ? trim($request->weight) : null,
        ]);

        return redirect()->back();
    }

    public function updateOption(Request $request, $id)
    {
        $request->validate([
            'option_value' => 'required|string',
            'plan'         => 'nullable|string',
            'weight'       => 'nullable|string'
        ]);

        $ownerIntId = $this->getOwnerIntId();
        TenantFieldOption::where('id', $id)->where('tenant_id', $ownerIntId)->update([
            'option_value' => trim($request->option_value),
            'plan'         => $request->plan ? trim($request->plan) : null,
            'weight'       => $request->weight ? trim($request->weight) : null,
        ]);

        return redirect()->back();
    }

    public function destroy($id)
    {
        $ownerIntId = $this->getOwnerIntId();
        TenantFieldOption::where('id', $id)->where('tenant_id', $ownerIntId)->delete();

        return redirect()->back();
    }
}
