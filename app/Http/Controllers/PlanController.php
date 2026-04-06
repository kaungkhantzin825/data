<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        return \Inertia\Inertia::render('PlanManagement', [
            'plans' => Plan::all()
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'staff_limit' => 'required|integer|min:1',
            'duration_in_days' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);

        Plan::create($validated);
        return redirect()->back();
    }

    public function update(Request $request, Plan $plan)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'staff_limit' => 'required|integer|min:1',
            'duration_in_days' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);

        $plan->update($validated);
        return redirect()->back();
    }

    public function destroy(Plan $plan)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $plan->delete();
        return redirect()->back();
    }
}
