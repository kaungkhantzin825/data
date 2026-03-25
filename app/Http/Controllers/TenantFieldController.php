<?php

namespace App\Http\Controllers;

use App\Models\TenantFieldOption;
use Illuminate\Http\Request;

class TenantFieldController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id ?: auth()->id();
        $fields = TenantFieldOption::where('tenant_id', $tenantId)->orderBy('created_at')->get();

        return \Inertia\Inertia::render('DropdownSettings', [
            'options' => $fields
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'field_name' => 'required|string|in:biz_type,source,division,township,product,channel,package',
            'option_value' => 'required|string',
        ]);
        $tenantId = auth()->user()->tenant_id ?: auth()->id();

        TenantFieldOption::create([
            'tenant_id' => $tenantId,
            'field_name' => $request->field_name,
            'option_value' => trim($request->option_value),
        ]);
        return redirect()->back();
    }

    public function updateOption(Request $request, $id) 
    {
        $request->validate([
            'option_value' => 'required|string',
        ]);

        $tenantId = auth()->user()->tenant_id ?: auth()->id();
        TenantFieldOption::where('id', $id)->where('tenant_id', $tenantId)->update([
            'option_value' => trim($request->option_value)
        ]);
        return redirect()->back();
    }

    public function destroy($id)
    {
        $tenantId = auth()->user()->tenant_id ?: auth()->id();
        TenantFieldOption::where('id', $id)->where('tenant_id', $tenantId)->delete();
        return redirect()->back();
    }
}
