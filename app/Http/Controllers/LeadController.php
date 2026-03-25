<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\TenantFieldOption;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function dashboard(Request $request)
    {
        $query = Lead::query()->with('creator:id,name');
        if (!auth()->user()->is_admin) {
            $query->where('created_by', auth()->id());
        }

        $allLeads = $query->get();
        $now = \Carbon\Carbon::now();

        $buildMetrics = function ($leadsDataset) {
            return [
            'total_customers' => $leadsDataset->count(),
            'total_amount' => $leadsDataset->sum(function ($l) {
                    return floatval($l->amount ?: $l->package_total ?: 0);
                }
                ),
                'recent_leads' => $leadsDataset->sortByDesc('id')->take(5)->map(function ($l) {
                    return [
                    'id' => $l->id,
                    'business_name' => $l->business_name ?: $l->contact_name ?: 'Unknown',
                    'biz_type' => $l->biz_type ?? 'N/A',
                    'package' => $l->package ?? 'N/A',
                    'weight' => $l->potential ?? '0%',
                    'plan' => $l->plan ?? 'N/A',
                    'amount' => floatval($l->amount ?: $l->package_total ?: 0),
                    'creator_name' => $l->creator ? $l->creator->name : 'Unknown'
                    ];
                }
                )->values(),
                'sales_persons' => $leadsDataset->groupBy('created_by')->map(function ($group) {
                    $creator = $group->first()->creator;
                    return [
                    'name' => $creator ? $creator->name : 'Unknown',
                    'links' => $group->count(),
                    'amount' => $group->sum(function ($l) {
                            return floatval($l->amount ?: $l->package_total ?: 0);
                        }
                        )
                        ];
                    }
                    )->values()->sortByDesc('amount')->take(5)->values()
                    ];
                };

        $summaryPlans = ['Enterprise', 'Business DIA', 'MojoeElite', 'Premium Home Fiber', 'Staff Plan'];
        $summaryReports = [];
        $reqMonth = $request->input('month', now()->month);
        $reqYear = $request->input('year', now()->year);

        foreach ($summaryPlans as $plan) {
            $planLeads = $allLeads->filter(function ($l) use ($plan) {
                $p = strtolower($l->plan ?? '');
                $pkg = strtolower($l->package ?? '');
                $search = strtolower($plan);
                return str_contains($p, $search) || str_contains($pkg, $search);
            });

            $filteredLeads = $planLeads->filter(function ($l) use ($reqMonth, $reqYear) {
                if (!$l->created_at)
                    return false;
                $ca = \Carbon\Carbon::parse($l->created_at);
                return $ca->month == $reqMonth && $ca->year == $reqYear;
            });

            $summaryReports[$plan] = [
                'Monthly' => $buildMetrics($filteredLeads),
                'Quarterly' => $buildMetrics($planLeads->filter(fn($l) => $l->created_at && \Carbon\Carbon::parse($l->created_at)->diffInDays(now()) <= 90)),
                'Yearly' => $buildMetrics($planLeads->filter(fn($l) => $l->created_at && \Carbon\Carbon::parse($l->created_at)->diffInDays(now()) <= 365)),
            ];
        }

        $diaPlans = ['Enterprise DIA', 'Business DIA', 'MojoeElite'];
        $diaReports = [];
        foreach ($diaPlans as $plan) {
            $planLeads = $allLeads->filter(function ($l) use ($plan, $reqMonth, $reqYear) {
                $p = strtolower($l->plan ?? '');
                $pkg = strtolower($l->package ?? '');
                $search = strtolower($plan);

                $match = false;
                if (str_contains($search, 'dia')) {
                    $match = (str_contains($p, 'dia') || str_contains($pkg, 'dia'));
                }
                else {
                    $match = (str_contains($p, $search) || str_contains($pkg, $search));
                }

                if (!$match)
                    return false;

                $ca = \Carbon\Carbon::parse($l->created_at);
                return $ca->month == $reqMonth && $ca->year == $reqYear;
            });

            $diaReports[$plan] = $buildMetrics($planLeads);
        }

        $expiringPlans = [];
        if (auth()->user()->is_admin) {
            $expiringPlans = \App\Models\User::whereNotNull('plan_expired_at')
                ->where('plan_expired_at', '<=', \Carbon\Carbon::now()->addDays(7))
                ->get(['id', 'name', 'email', 'plan_expired_at']);
        }

        return Inertia::render('Dashboard', [
            'activeTab' => 'dashboard',
            'summaryReports' => $summaryReports,
            'diaReports' => $diaReports,
            'totalLeads' => $allLeads->count(),
            'expiringPlans' => $expiringPlans
        ]);
    }

    public function index(Request $request)
    {
        $query = Lead::query();

        if (!auth()->user()->is_admin) {
            $query->where('created_by', auth()->id());
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('business_name', 'like', "%{$request->search}%")
                    ->orWhere('contact_name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->plan) {
            $query->where('plan', $request->plan);
        }

        if ($request->biz_type) {
            $query->where('biz_type', $request->biz_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $leads = $query->orderByDesc('id')->paginate(10)->withQueryString();

        $availablePlans = Lead::whereNotNull('plan')->where('plan', '!=', '')->distinct()->pluck('plan');
        $availableBizTypes = Lead::whereNotNull('biz_type')->where('biz_type', '!=', '')->distinct()->pluck('biz_type');

        return Inertia::render('Dashboard', [
            'leads' => $leads,
            'filters' => $request->only(['search', 'plan', 'biz_type', 'status']),
            'activeTab' => 'lists',
            'availablePlans' => $availablePlans,
            'availableBizTypes' => $availableBizTypes,
        ]);
    }

    private function getTenantFieldOptions()
    {
        $tenantId = auth()->user()->tenant_id ?: auth()->id();
        $fields = TenantFieldOption::where('tenant_id', $tenantId)->get()->groupBy('field_name');

        $getDefault = function ($key, $defaults) use ($fields) {
            return isset($fields[$key]) ? $fields[$key]->pluck('option_value')->toArray() : $defaults;
        };

        return [
            'biz_type' => $getDefault('biz_type', ['Residential', 'Commercial']),
            'source' => $getDefault('source', ['Own Lead', 'Social Media']),
            'division' => $getDefault('division', ['Yangon', 'Mandalay']),
            'township' => $getDefault('township', ['Bahan', 'Sanchaung']),
            'product' => $getDefault('product', ['Internet']),
            'channel' => $getDefault('channel', ['Direct', 'Partner']),
            'package' => $getDefault('package', ['Home Plus', 'Business DIA']),
        ];
    }

    public function create()
    {
        return Inertia::render('Dashboard', [
            'activeTab' => 'create',
            'fieldOptions' => $this->getTenantFieldOptions(),
        ]);
    }

    public function upload()
    {
        return Inertia::render('Dashboard', [
            'activeTab' => 'upload',
            'fieldOptions' => $this->getTenantFieldOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'secondary_contact_number' => 'required|string|max:255',

            'biz_type' => 'required|string',
            'source' => 'required|string',
            'division' => 'required|string',
            'township' => 'required|string',
            'address' => 'required|string',

            'product' => 'required|string',
            'package' => 'required|string',
            'package_total' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'note' => 'nullable|string',

            'status' => 'required|string',
            'channel' => 'required|string',
            'installation_appointment' => 'nullable|date',
            'est_contract_date' => 'required|date',
            'est_start_date' => 'required|date',
            'est_follow_up_date' => 'required|date',
            'is_referral' => 'required|boolean',
            'meeting_note' => 'nullable|string',
            'next_step' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['contact_name'] = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        $validated['plan'] = $validated['package'] ?? null;
        $validated['amount'] = $validated['package_total'] ?? 0;

        Lead::create($validated);

        return redirect()->route('leads.index');
    }

    public function update(Request $request, $id)
    {
        $q = Lead::where(function ($query) use ($id) {
            $query->where('uuid', $id)->orWhere('id', $id);
        });

        if (!auth()->user()->is_admin) {
            $q->where('created_by', auth()->id());
        }

        $lead = $q->firstOrFail();

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'secondary_contact_number' => 'required|string|max:255',

            'biz_type' => 'required|string',
            'source' => 'required|string',
            'division' => 'required|string',
            'township' => 'required|string',
            'address' => 'required|string',

            'product' => 'required|string',
            'package' => 'required|string',
            'package_total' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'note' => 'nullable|string',

            'status' => 'required|string',
            'channel' => 'required|string',
            'installation_appointment' => 'nullable|date',
            'est_contract_date' => 'required|date',
            'est_start_date' => 'required|date',
            'est_follow_up_date' => 'required|date',
            'is_referral' => 'required|boolean',
            'meeting_note' => 'nullable|string',
            'next_step' => 'nullable|string',
        ]);

        $validated['contact_name'] = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        $validated['plan'] = $validated['package'] ?? null;
        $validated['amount'] = $validated['package_total'] ?? 0;

        $lead->update($validated);

        return redirect()->route('leads.index');
    }

    public function export(Request $request)
    {
        $query = Lead::query();

        if (!auth()->user()->is_admin) {
            $query->where('created_by', auth()->id());
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('business_name', 'like', "%{$request->search}%")
                    ->orWhere('contact_name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        if ($request->plan) {
            $query->where('plan', $request->plan);
        }
        if ($request->biz_type) {
            $query->where('biz_type', $request->biz_type);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $leads = $query->orderByDesc('id')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=leads_export_' . date('Y-m-d_H-i-s') . '.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['ID', 'UUID', 'Business Name', 'Contact Name', 'Email', 'Phone', 'Secondary Phone', 'Biz Type', 'Source', 'Division', 'Township', 'Address', 'Product', 'Package', 'Package Total', 'Discount', 'Status', 'Channel', 'Installation Appointment', 'Est. Contract Date', 'Est. Start Date', 'Est. Follow Up Date', 'Referral', 'Meeting Note', 'Next Step', 'Created At'];

        $callback = function () use ($leads, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->uuid,
                    $lead->business_name,
                    $lead->contact_name,
                    $lead->contact_email,
                    $lead->phone,
                    $lead->secondary_contact_number,
                    $lead->biz_type,
                    $lead->source,
                    $lead->division,
                    $lead->township,
                    $lead->address,
                    $lead->product,
                    $lead->package,
                    $lead->package_total,
                    $lead->discount,
                    $lead->status,
                    $lead->channel,
                    $lead->installation_appointment,
                    $lead->est_contract_date,
                    $lead->est_start_date,
                    $lead->est_follow_up_date,
                    $lead->is_referral ? 'Yes' : 'No',
                    $lead->meeting_note,
                    $lead->next_step,
                    $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'update_existing' => 'nullable|string'
        ]);

        $updateExisting = $request->update_existing === 'true';

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");

        $headers = fgetcsv($handle, 5000, ",");
        if (!$headers) {
            return redirect()->back()->withErrors(['file' => 'Invalid CSV format.']);
        }

        $headers = array_map('strtolower', $headers);
        $headers = array_map('trim', $headers);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
            if (count($headers) !== count($data))
                continue;
            $row = array_combine($headers, $data);

            $phone = $row['phone'] ?? $row['contact_information'] ?? null;
            $firstName = $row['first_name'] ?? $row['firstname'] ?? '';
            $lastName = $row['last_name'] ?? $row['lastname'] ?? '';

            if (!$phone && !$firstName) {
                $skipped++;
                continue;
            }

            $existing = null;
            if ($phone) {
                $q = Lead::where('phone', $phone);
                if (!auth()->user()->is_admin) {
                    $q->where('created_by', auth()->id());
                }
                $existing = $q->first();
            }

            $mappedData = [
                'business_name' => $row['business_name'] ?? null,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'contact_email' => $row['contact_email'] ?? $row['email'] ?? null,
                'phone' => $phone,
                'township' => $row['township'] ?? null,
                'address' => $row['address'] ?? null,
                'plan' => $row['plan'] ?? $row['package'] ?? null,
                'package' => $row['package'] ?? $row['plan'] ?? null,
                'biz_type' => $row['biz_type'] ?? null,
                'source' => $row['source'] ?? null,
                'contact_name' => trim(($firstName) . ' ' . ($lastName))
            ];

            $mappedData = array_filter($mappedData, function ($value) {
                return !is_null($value) && $value !== '';
            });

            if ($existing) {
                if ($updateExisting) {
                    $existing->update($mappedData);
                    $updated++;
                }
                else {
                    $skipped++;
                }
            }
            else {
                $mappedData['created_by'] = auth()->id();
                Lead::create($mappedData);
                $created++;
            }
        }

        fclose($handle);

        return redirect()->route('leads.index')->with('success', "Import complete! Created: {$created}, Updated: {$updated}, Skipped (Duplicate): {$skipped}.");
    }
}
