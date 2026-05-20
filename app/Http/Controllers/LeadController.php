<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\TenantFieldOption;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function dashboard(Request $request)
    {
        $user     = auth()->user();
        $reqMonth = (int) $request->input('month', now()->month);
        $reqYear  = (int) $request->input('year',  now()->year);

        $query = Lead::query()->with('creator:id,name');

        // ── Visibility scope ────────────────────────────────────────────
        if (!$user->is_admin) {
            if ($user->hasRole('Company Super Admin')) {
                // Scoped by manager if selected
                if ($request->manager_id) {
                    $mStaffIds   = User::where('manager_id', $request->manager_id)->pluck('id')->toArray();
                    $mCreators   = array_merge([$request->manager_id], $mStaffIds);
                    $mOrgIds     = User::find($request->manager_id)?->organizations()->pluck('organizations.id')->toArray() ?? [];
                    $query->where(function($q) use ($mCreators, $mOrgIds) {
                        $q->whereIn('created_by', $mCreators)->orWhereIn('organization_id', $mOrgIds);
                    });
                }
            } elseif ($user->hasRole('Manager')) {
                $orgIds          = $user->organizations()->pluck('organizations.id')->toArray();
                $staffIds        = User::where('manager_id', $user->id)->pluck('id')->toArray();
                $visibleCreators = array_merge([$user->id], $staffIds);
                $query->where(function($q) use ($orgIds, $visibleCreators) {
                    $q->whereIn('created_by', $visibleCreators)->orWhereIn('organization_id', $orgIds);
                });
            } else {
                $query->where('created_by', $user->id);
            }
        }

        // ── Additional filters ───────────────────────────────────────────
        if ($request->plan)     { $query->where('plan', $request->plan); }
        if ($request->biz_type) { $query->where('biz_type', $request->biz_type); }
        if ($request->status)   { $query->where('status', $request->status); }

        if ($request->org_id) {
            $staffInOrg = User::whereHas('organizations', fn($q) => $q->where('organizations.id', $request->org_id))->pluck('id')->toArray();
            $query->where(function($q) use ($request, $staffInOrg) {
                $q->where('organization_id', $request->org_id)->orWhereIn('created_by', $staffInOrg);
            });
        }

        if ($request->staff_id) {
            $query->where('created_by', $request->staff_id);
        }

        $allLeads = $query->get();

        // ── Metrics builder ──────────────────────────────────────────────
        $buildMetrics = function ($leadsDataset) {
            return [
                'total_customers' => $leadsDataset->count(),
                'total_amount'    => $leadsDataset->sum(fn($l) => floatval($l->amount ?: $l->package_total ?: 0)),
                'recent_leads'    => $leadsDataset->sortByDesc('id')->take(5)->map(fn($l) => [
                    'id'           => $l->id,
                    'business_name'=> $l->business_name ?: $l->contact_name ?: 'Unknown',
                    'biz_type'     => $l->biz_type     ?? 'N/A',
                    'package'      => $l->package      ?? 'N/A',
                    'plan'         => $l->plan         ?? 'N/A',
                    'amount'       => floatval($l->amount ?: $l->package_total ?: 0),
                    'creator_name' => $l->creator?->name ?? 'Unknown',
                ])->values(),
                'sales_persons'   => $leadsDataset->groupBy('created_by')->map(function($group) {
                    $creator = $group->first()->creator;
                    return [
                        'name'   => $creator?->name ?? 'Unknown',
                        'links'  => $group->count(),
                        'amount' => $group->sum(fn($l) => floatval($l->amount ?: $l->package_total ?: 0)),
                    ];
                })->values()->sortByDesc('amount')->take(5)->values(),
            ];
        };

        // ── Monthly & plan reports ────────────────────────────────────────
        $monthLeads = $allLeads->filter(function($l) use ($reqMonth, $reqYear) {
            if (!$l->created_at) return false;
            $ca = \Carbon\Carbon::parse($l->created_at);
            return $ca->month == $reqMonth && $ca->year == $reqYear;
        });

        $summaryPlans   = ['Enterprise', 'Business DIA', 'MojoeElite', 'Premium Home Fiber', 'Staff Plan'];
        $summaryReports = [];
        foreach ($summaryPlans as $plan) {
            $planLeads = $allLeads->filter(function($l) use ($plan) {
                $p   = strtolower($l->plan    ?? '');
                $pkg = strtolower($l->package ?? '');
                $s   = strtolower($plan);
                return str_contains($p, $s) || str_contains($pkg, $s);
            });
            $filteredLeads          = $planLeads->filter(fn($l) => $l->created_at && \Carbon\Carbon::parse($l->created_at)->month == $reqMonth && \Carbon\Carbon::parse($l->created_at)->year == $reqYear);
            $summaryReports[$plan]  = [
                'Monthly'   => $buildMetrics($filteredLeads),
                'Quarterly' => $buildMetrics($planLeads->filter(fn($l) => $l->created_at && \Carbon\Carbon::parse($l->created_at)->diffInDays(now()) <= 90)),
                'Yearly'    => $buildMetrics($planLeads->filter(fn($l) => $l->created_at && \Carbon\Carbon::parse($l->created_at)->diffInDays(now()) <= 365)),
            ];
        }

        $diaPlans   = ['Enterprise DIA', 'Business DIA', 'MojoeElite'];
        $diaReports = [];
        foreach ($diaPlans as $plan) {
            $planLeads = $allLeads->filter(function($l) use ($plan, $reqMonth, $reqYear) {
                $p = strtolower($l->plan ?? ''); $pkg = strtolower($l->package ?? ''); $s = strtolower($plan);
                $match = str_contains($s, 'dia') ? (str_contains($p, 'dia') || str_contains($pkg, 'dia')) : (str_contains($p, $s) || str_contains($pkg, $s));
                if (!$match) return false;
                $ca = \Carbon\Carbon::parse($l->created_at);
                return $ca->month == $reqMonth && $ca->year == $reqYear;
            });
            $diaReports[$plan] = $buildMetrics($planLeads);
        }

        $allLeadsReport  = $buildMetrics($allLeads);
        $monthlyOverview = $buildMetrics($monthLeads);

        // ── Cascading filter data ─────────────────────────────────────────
        $staffList      = [];
        $managerOrgs    = [];
        $superAdminData = [];
        $availablePlans    = Lead::whereNotNull('plan')->where('plan', '!=', '')->distinct()->pluck('plan');
        $availableBizTypes = Lead::whereNotNull('biz_type')->where('biz_type', '!=', '')->distinct()->pluck('biz_type');

        if (!$user->is_admin && $user->hasRole('Manager')) {
            $orgs   = $user->organizations()->get(['organizations.id', 'organizations.name']);
            $orgIds = $orgs->pluck('id')->toArray();
            $myStaff = User::where('manager_id', $user->id)->select('id', 'name')
                ->with(['organizations' => fn($q) => $q->whereIn('organizations.id', $orgIds)->select('organizations.id')])->get();
            $staffList = $myStaff->map(fn($s) => [
                'id' => $s->id, 'name' => $s->name,
                'org_ids' => $s->organizations->pluck('id')->toArray(),
            ])->values()->toArray();
            $managerOrgs = $orgs->map(fn($org) => ['id' => $org->id, 'name' => $org->name])->values()->toArray();

        } elseif (!$user->is_admin && $user->hasRole('Company Super Admin')) {
            $allManagers = User::where('tenant_id', $user->tenant_id)
                ->whereHas('roles', fn($q) => $q->where('name', 'Manager'))->select('id', 'name')->get();
            $superAdminData['managers'] = $allManagers->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->values()->toArray();
            $superAdminData['orgs']  = [];
            $superAdminData['staff'] = [];
            foreach ($allManagers as $manager) {
                $mOrgs   = $manager->organizations()->get(['organizations.id', 'organizations.name']);
                $mOrgIds = $mOrgs->pluck('id')->toArray();
                foreach ($mOrgs as $org) {
                    $superAdminData['orgs'][] = ['id' => $org->id, 'name' => $org->name, 'manager_id' => $manager->id];
                }
                $mStaff = User::where('manager_id', $manager->id)->select('id', 'name')
                    ->with(['organizations' => fn($q) => $q->whereIn('organizations.id', $mOrgIds)->select('organizations.id')])->get();
                foreach ($mStaff as $s) {
                    $superAdminData['staff'][] = ['id' => $s->id, 'name' => $s->name, 'manager_id' => $manager->id, 'org_ids' => $s->organizations->pluck('id')->toArray()];
                }
            }
        }

        return Inertia::render('Dashboard', [
            'activeTab'        => 'dashboard',
            'summaryReports'   => $summaryReports,
            'diaReports'       => $diaReports,
            'totalLeads'       => $allLeads->count(),
            'allLeadsReport'   => $allLeadsReport,
            'monthlyOverview'  => $monthlyOverview,
            'reqMonth'         => $reqMonth,
            'reqYear'          => $reqYear,
            'availablePlans'   => $availablePlans,
            'availableBizTypes'=> $availableBizTypes,
            'staffList'        => $staffList,
            'managerOrgs'      => $managerOrgs,
            'superAdminData'   => $superAdminData,
            'filters'          => $request->only(['plan', 'biz_type', 'status', 'manager_id', 'org_id', 'staff_id', 'month', 'year']),
            'fieldOptions'     => $this->getTenantFieldOptions(),
        ]);

    }

    public function index(Request $request)
    {
        $query = Lead::query();
        
        $user = auth()->user();
        if (!$user->is_admin) {
            if ($user->hasRole('Company Super Admin')) {
                // Super Admin: if manager_id filter applied, scope to that manager's data
                if ($request->manager_id) {
                    $managerStaffIds = User::where('manager_id', $request->manager_id)->pluck('id')->toArray();
                    $visibleCreators = array_merge([$request->manager_id], $managerStaffIds);
                    $managerOrgIds   = User::find($request->manager_id)?->organizations()->pluck('organizations.id')->toArray() ?? [];
                    $query->where(function($q) use ($visibleCreators, $managerOrgIds) {
                        $q->whereIn('created_by', $visibleCreators)
                          ->orWhereIn('organization_id', $managerOrgIds);
                    });
                }
            } elseif ($user->hasRole('Manager')) {
                // Manager sees leads uploaded by their direct staff AND leads in their orgs
                $orgIds = $user->organizations()->pluck('organizations.id')->toArray();
                $staffIds = User::where('manager_id', $user->id)->pluck('id')->toArray();
                $visibleCreators = array_merge([$user->id], $staffIds);
                $query->where(function($q) use ($orgIds, $visibleCreators) {
                    $q->whereIn('created_by', $visibleCreators)
                      ->orWhereIn('organization_id', $orgIds);
                });
            } else {
                $query->where('created_by', $user->id);
            }
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

        // Status filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Org filter — show leads tagged to that org OR uploaded by any staff in that org
        if ($request->org_id) {
            $staffInOrg = User::whereHas('organizations', function($q) use ($request) {
                $q->where('organizations.id', $request->org_id);
            })->pluck('id')->toArray();

            $query->where(function($q) use ($request, $staffInOrg) {
                $q->where('organization_id', $request->org_id)
                  ->orWhereIn('created_by', $staffInOrg);
            });
        }

        // Staff filter — filter leads by specific staff member
        if ($request->staff_id) {
            $query->where('created_by', $request->staff_id);
        }

        \Log::info('Lead Query:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings(), 'user' => auth()->id()]);

        $leads = $query->orderByDesc('id')->paginate(10)->withQueryString();

        $availablePlans    = Lead::whereNotNull('plan')->where('plan', '!=', '')->distinct()->pluck('plan');
        $availableBizTypes = Lead::whereNotNull('biz_type')->where('biz_type', '!=', '')->distinct()->pluck('biz_type');

        // Build Staff List + Org List for Manager (cascading filter)
        $staffList      = [];
        $managerOrgs    = [];
        $superAdminData = [];

        if (!$user->is_admin && $user->hasRole('Manager')) {
            // Manager's organizations
            $orgs   = $user->organizations()->get(['organizations.id', 'organizations.name']);
            $orgIds = $orgs->pluck('id')->toArray();

            $myStaff = User::where('manager_id', $user->id)
                ->select('id', 'name', 'email')
                ->with(['organizations' => function($q) use ($orgIds) {
                    $q->whereIn('organizations.id', $orgIds)->select('organizations.id', 'organizations.name');
                }])
                ->get();

            $allLeadsByCreator = Lead::query()
                ->whereIn('created_by', $myStaff->pluck('id')->toArray())
                ->selectRaw('created_by, count(*) as total')
                ->groupBy('created_by')
                ->pluck('total', 'created_by');

            $staffList = $myStaff->map(function($s) use ($allLeadsByCreator) {
                return [
                    'id'         => $s->id,
                    'name'       => $s->name,
                    'email'      => $s->email,
                    'lead_count' => $allLeadsByCreator[$s->id] ?? 0,
                    'org_ids'    => $s->organizations->pluck('id')->toArray(),
                ];
            })->values()->toArray();

            $managerOrgs = $orgs->map(function($org) use ($staffList) {
                $staffCount = collect($staffList)->filter(fn($s) => in_array($org->id, $s['org_ids']))->count();
                return ['id' => $org->id, 'name' => $org->name, 'staff_count' => $staffCount];
            })->values()->toArray();

        } elseif (!$user->is_admin && $user->hasRole('Company Super Admin')) {
            // Super Admin cascading data: all managers → their orgs → their staff
            $allManagers = User::where('tenant_id', $user->tenant_id)
                ->whereHas('roles', fn($q) => $q->where('name', 'Manager'))
                ->select('id', 'name')
                ->get();

            $superAdminData['managers'] = $allManagers->map(fn($m) => [
                'id'   => $m->id,
                'name' => $m->name,
            ])->values()->toArray();

            // For each manager: their orgs
            $superAdminData['orgs'] = [];
            $superAdminData['staff'] = [];

            foreach ($allManagers as $manager) {
                $mOrgs  = $manager->organizations()->get(['organizations.id', 'organizations.name']);
                $mOrgIds = $mOrgs->pluck('id')->toArray();

                foreach ($mOrgs as $org) {
                    $superAdminData['orgs'][] = [
                        'id'         => $org->id,
                        'name'       => $org->name,
                        'manager_id' => $manager->id,
                    ];
                }

                $mStaff = User::where('manager_id', $manager->id)
                    ->select('id', 'name')
                    ->with(['organizations' => fn($q) => $q->whereIn('organizations.id', $mOrgIds)->select('organizations.id')])
                    ->get();

                $staffLeadCounts = Lead::query()
                    ->whereIn('created_by', $mStaff->pluck('id')->toArray())
                    ->selectRaw('created_by, count(*) as total')
                    ->groupBy('created_by')
                    ->pluck('total', 'created_by');

                foreach ($mStaff as $s) {
                    $superAdminData['staff'][] = [
                        'id'         => $s->id,
                        'name'       => $s->name,
                        'manager_id' => $manager->id,
                        'org_ids'    => $s->organizations->pluck('id')->toArray(),
                        'lead_count' => $staffLeadCounts[$s->id] ?? 0,
                    ];
                }
            }
        }

        return Inertia::render('Dashboard', [
            'leads'            => $leads,
            'filters'          => $request->only(['search', 'plan', 'biz_type', 'status', 'org_id', 'staff_id', 'manager_id']),
            'activeTab'        => 'lists',
            'availablePlans'   => $availablePlans,
            'availableBizTypes'=> $availableBizTypes,
            'staffList'        => $staffList,
            'managerOrgs'      => $managerOrgs,
            'superAdminData'   => $superAdminData,
            'fieldOptions'     => $this->getTenantFieldOptions(),
        ]);
    }

    private function getTenantFieldOptions()
    {
        $fields = TenantFieldOption::all()->groupBy('field_name');

        $getOptions = function ($key) use ($fields) {
            return isset($fields[$key]) ? $fields[$key]->pluck('option_value')->toArray() : [];
        };

        return [
            'biz_type'  => $getOptions('biz_type'),
            'source'    => $getOptions('source'),
            'division'  => $getOptions('division'),
            'township'  => $getOptions('township'),
            'product'   => $getOptions('product'),
            'channel'   => $getOptions('channel'),
            'package'   => $getOptions('package'),
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
            'business_name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'secondary_contact_number' => 'nullable|string|max:255',

            'biz_type' => 'nullable|string',
            'source' => 'nullable|string',
            'division' => 'nullable|string',
            'township' => 'nullable|string',
            'address' => 'nullable|string',

            'product' => 'nullable|string',
            'package' => 'nullable|string',
            'package_total' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',

            'status' => 'nullable|string',
            'channel' => 'nullable|string',
            'installation_appointment' => 'nullable|date',
            'est_contract_date' => 'nullable|date',
            'est_start_date' => 'nullable|date',
            'est_follow_up_date' => 'nullable|date',
            'is_referral' => 'nullable|boolean',
            'meeting_note' => 'nullable|string',
            'next_step' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        
        $user = auth()->user();
        if ($user->organizations()->exists()) {
            $validated['organization_id'] = $user->organizations()->first()->id;
        }
        
        // Build contact_name from first_name and last_name (only if they have values)
        $firstName = $validated['first_name'] ?? '';
        $lastName = $validated['last_name'] ?? '';
        $contactName = trim($firstName . ' ' . $lastName);
        $validated['contact_name'] = $contactName ?: '-';
        
        // Set default "-" for empty business_name
        if (empty($validated['business_name']) || trim($validated['business_name']) === '') {
            $validated['business_name'] = '-';
        }
        
        $validated['plan'] = $validated['package'] ?? null;
        $validated['amount'] = $validated['package_total'] ?? 0;

        Lead::create($validated);

        return redirect()->route('leads.index');
    }

    public function show($id)
    {
        $lead = Lead::where(function ($query) use ($id) {
            $query->where('uuid', $id)->orWhere('id', $id);
        })->with('creator:id,name')->firstOrFail();

        return Inertia::render('LeadDetail', [
            'lead' => $lead
        ]);
    }

    public function update(Request $request, $id)
    {
        $q = Lead::where(function ($query) use ($id) {
            $query->where('uuid', $id)->orWhere('id', $id);
        });

        $lead = $q->firstOrFail();

        $validated = $request->validate([
            'business_name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'secondary_contact_number' => 'nullable|string|max:255',

            'biz_type' => 'nullable|string',
            'source' => 'nullable|string',
            'division' => 'nullable|string',
            'township' => 'nullable|string',
            'address' => 'nullable|string',

            'product' => 'nullable|string',
            'package' => 'nullable|string',
            'package_total' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',

            'status' => 'nullable|string',
            'channel' => 'nullable|string',
            'installation_appointment' => 'nullable|date',
            'est_contract_date' => 'nullable|date',
            'est_start_date' => 'nullable|date',
            'est_follow_up_date' => 'nullable|date',
            'is_referral' => 'nullable|boolean',
            'meeting_note' => 'nullable|string',
            'next_step' => 'nullable|string',
        ]);

        // Build contact_name from first_name and last_name (only if they have values)
        $firstName = $validated['first_name'] ?? '';
        $lastName = $validated['last_name'] ?? '';
        $contactName = trim($firstName . ' ' . $lastName);
        $validated['contact_name'] = $contactName ?: '-';
        
        // Set default "-" for empty business_name
        if (empty($validated['business_name']) || trim($validated['business_name']) === '') {
            $validated['business_name'] = '-';
        }
        
        $validated['plan'] = $validated['package'] ?? null;
        $validated['amount'] = $validated['package_total'] ?? 0;

        $lead->update($validated);

        return redirect()->route('leads.index');
    }

    public function export(Request $request)
    {
        $query = Lead::query();

        if (!auth()->user()->is_admin) {
           
            if (auth()->user()->hasRole('Staff')) {
                $query->where('created_by', auth()->id());
            }
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('business_name', 'like', "%{$request->search}%")
                    ->orWhere('contact_name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        if ($request->plan)     { $query->where('plan', $request->plan); }
        if ($request->biz_type) { $query->where('biz_type', $request->biz_type); }
        if ($request->status)   { $query->where('status', $request->status); }

        $leads = $query->orderByDesc('id')->get();

        $filename = 'Pipeline' . date('dmY') . '.csv';
        
        $httpHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $csvColumns = [
            'business_name', 'first_name', 'last_name',
            'contact_email', 'phone', 'secondary_contact_number',
            'biz_type', 'source', 'division', 'township', 'address',
            'product', 'package', 'package_total', 'discount', 'note',
            'status', 'channel',
            'installation_appointment', 'est_contract_date',
            'est_start_date', 'est_follow_up_date',
            'is_referral', 'meeting_note', 'next_step',
        ];

        $callback = function () use ($leads, $csvColumns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvColumns);

            foreach ($leads as $lead) {
                $nameParts = explode(' ', $lead->contact_name ?? '', 2);
                fputcsv($file, [
                    $lead->business_name,
                    $nameParts[0] ?? '',
                    $nameParts[1] ?? '',
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
                    $lead->note,
                    $lead->status,
                    $lead->channel,
                    $lead->installation_appointment,
                    $lead->est_contract_date,
                    $lead->est_start_date,
                    $lead->est_follow_up_date,
                    $lead->is_referral ? 'Yes' : 'No',
                    $lead->meeting_note,
                    $lead->next_step,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $httpHeaders);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'            => 'required|file|mimes:csv,txt,xlsx,xls|max:2048',
            'update_existing' => 'nullable|string',
        ]);

        $updateExisting = $request->update_existing === 'true';

        $file = $request->file('file');
        
        
        $originalName = $file->getClientOriginalName();
        $fileName = date('Y-m-d_H-i-s') . '_' . $originalName;
        $filePath = $file->storeAs('uploads/leads', $fileName, 'public');
        
      
        $storedFilePath = storage_path('app/public/' . $filePath);
        $handle = fopen($storedFilePath, 'r');

        $rawHeaders = fgetcsv($handle, 5000, ',');
        if (!$rawHeaders) {
            fclose($handle);
            return redirect()->back()->withErrors(['file' => 'Invalid CSV format.']);
        }
        $rawHeaders[0] = ltrim($rawHeaders[0], "\xEF\xBB\xBF\xFF\xFE");
        $headers = array_map(fn($h) => trim(strtolower(trim($h))), $rawHeaders);

        $created        = 0;
        $updated        = 0;
        $duplicateSkip  = 0;  
        $nullSkip       = 0;  
        $errors         = 0;
        $errorDetails   = [];

        $user = auth()->user();
        $tenantId = $user->tenant_id ? Tenant::find($user->tenant_id)->user_id : $user->id;

        $parseDate = function (?string $value, bool $withTime = false): ?string {
            if (!$value || trim($value) === '') return null;
            try {
                $dt = \Carbon\Carbon::parse(trim($value));
                return $withTime ? $dt->format('Y-m-d H:i:s') : $dt->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        while (($data = fgetcsv($handle, 5000, ',')) !== false) {
            if (count($headers) !== count($data)) continue;
            $row = array_combine($headers, $data);

            $get = function (array $keys) use ($row): ?string {
                foreach ($keys as $k) {
                    $v = $row[$k] ?? null;
                    if ($v !== null && trim((string) $v) !== '') return trim((string) $v);
                }
                return null;
            };

            $phone = $get(['phone', 'phone_number', 'ph', 'contact_information',
                           'mobile', 'mobile_number', 'contact_no', 'phonenumber']);

            $firstName = $get(['first_name', 'firstname', 'first name', 'fname']);
            $lastName  = $get(['last_name',  'lastname',  'last name',  'lname']);

            if (!$firstName && !$lastName) {
                $cn = $get(['contact_name', 'contact name', 'name', 'full_name']);
                if ($cn) {
                    $parts     = explode(' ', $cn, 2);
                    $firstName = $parts[0] ?? '';
                    $lastName  = $parts[1] ?? '';
                }
            }

            if (!$phone && !$firstName && !$lastName) {
                $nullSkip++;
                continue;
            }

            $existing = null;
            $businessName = $get(['business_name', 'business name', 'company', 'company_name']);
            
            if ($phone && $updateExisting) {
                $existing = Lead::where('phone', $phone)->first();
            }

            if ($existing && $updateExisting) {
            }

            $refRaw     = strtolower($get(['is_referral', 'referral', 'ref']) ?? 'no');
            $isReferral = in_array($refRaw, ['yes', '1', 'true']) ? 1 : 0;

            $mappedData = [
                'business_name'            => $businessName,
                'contact_name'             => trim("{$firstName} {$lastName}") ?: null,
                'first_name'               => $firstName ?: null,
                'last_name'                => $lastName  ?: null,
                'contact_email'            => $get(['contact_email', 'email', 'e-mail', 'email_address']),
                'phone'                    => $phone,
                'secondary_contact_number' => $get(['secondary_contact_number', 'secondary phone',
                                                    'secondary_phone', 'phone2', 'alt_phone']),
                'biz_type'                 => $get(['biz_type', 'biz type', 'business_type', 'business type']),
                'source'                   => $get(['source', 'lead_source']),
                'division'                 => $get(['division', 'region']),
                'township'                 => $get(['township', 'town', 'city']),
                'address'                  => $get(['address', 'full_address']),
                'product'                  => $get(['product', 'product_name']),
                'package'                  => $get(['package', 'plan_name']),
                'plan'                     => $get(['plan']) ?? $get(['package', 'plan_name']),
                'package_total'            => is_numeric($t = $get(['package_total', 'package total', 'total'])) ? (float)$t : null,
                'discount'                 => is_numeric($d = $get(['discount', 'disc']))                          ? (float)$d : null,
                'note'                     => $get(['note', 'notes', 'product_note']),
                'amount'                   => is_numeric($a = $get(['package_total', 'package total', 'amount', 'total'])) ? (float)$a : 0,
                'status'                   => $get(['status']),
                'channel'                  => $get(['channel', 'sale_channel']),
                'meeting_note'             => $get(['meeting_note', 'meeting note', 'meeting_notes']),
                'next_step'                => $get(['next_step', 'next step', 'action']),
                'installation_appointment' => $parseDate($get(['installation_appointment', 'installation appointment']), true),
                'est_contract_date'        => $parseDate($get(['est_contract_date', 'est. contract date', 'est contract date', 'contract_date'])),
                'est_start_date'           => $parseDate($get(['est_start_date', 'est. start date', 'est start date', 'start_date'])),
                'est_follow_up_date'       => $parseDate($get(['est_follow_up_date', 'est. follow up date', 'est follow up date', 'follow_up_date'])),
                'is_referral'              => $isReferral,
            ];

            $mappedData = array_filter($mappedData, fn($v) => $v !== null && $v !== '');
            $mappedData['is_referral'] = $isReferral; 

            $dropdownFields = [
                'biz_type'  => $mappedData['biz_type'] ?? null,
                'source'    => $mappedData['source'] ?? null,
                'division'  => $mappedData['division'] ?? null,
                'township'  => $mappedData['township'] ?? null,
                'product'   => $mappedData['product'] ?? null,
                'channel'   => $mappedData['channel'] ?? null,
                'package'   => $mappedData['package'] ?? null,
                'status'    => $mappedData['status'] ?? null,
            ];

            foreach ($dropdownFields as $fieldName => $optionValue) {
                if ($optionValue && trim($optionValue) !== '') {
                    $exists = TenantFieldOption::where('tenant_id', $tenantId)
                        ->where('field_name', $fieldName)
                        ->where('option_value', trim($optionValue))
                        ->exists();
                    
                    if (!$exists) {
                        TenantFieldOption::create([
                            'tenant_id'    => $tenantId,
                            'field_name'   => $fieldName,
                            'option_value' => trim($optionValue),
                        ]);
                    }
                }
            }

            if (empty($mappedData['business_name'])) {
                $errors++;
                $errorDetails[] = "[phone:{$phone}] business_name is required — column not found in CSV.";
                continue;
            }
            if (empty($mappedData['contact_name'])) {
                $mappedData['contact_name'] = $mappedData['business_name'];
            }

            try {
                if ($existing && $updateExisting) {
                    $existing->update($mappedData);
                    $updated++;
                } else {
                    $mappedData['created_by'] = auth()->id();
                    Lead::create($mappedData);
                    $created++;
                }
            } catch (\Exception $e) {
                $errors++;
                $biz = $mappedData['business_name'] ?? "phone:{$phone}";
                $errorDetails[] = "[{$biz}]: " . $e->getMessage();
                \Log::error('CSV Import row error: ' . $e->getMessage(), [
                    'business' => $biz,
                    'phone'    => $phone,
                    'keys'     => array_keys($mappedData),
                ]);
            }
        }

        fclose($handle);
        $toUtf8 = fn($str) => mb_convert_encoding((string)$str, 'UTF-8', 'UTF-8');
        $importResult = [
            'created'          => $created,
            'updated'          => $updated,
            'duplicate_skip'   => $duplicateSkip,
            'null_skip'        => $nullSkip,
            'errors'           => $errors,
            'error_details'    => array_map($toUtf8, array_slice($errorDetails, 0, 10)),
            'detected_headers' => array_map($toUtf8, $headers),
        ];

        $base = redirect()->route('leads.index')->with('importResult', $importResult);

        if ($created > 0 || $updated > 0) {
            $msg = "Created: {$created}, Updated: {$updated}.";
            if ($nullSkip  > 0)     $msg .= " Skipped (blank rows): {$nullSkip}.";
            if ($errors    > 0)     $msg .= " Errors: {$errors}.";
            return $base->with('success', $msg);
        }

        if ($errors > 0) {
            return $base->with('error', "Import failed — {$errors} row(s) could not be saved.");
        }

        if ($nullSkip > 0) {
            return $base->with('error',
                "No leads imported — {$nullSkip} row(s) had no phone number AND no name. " .
                "Check that your CSV column names match exactly: phone, first_name, last_name.");
        }

        return $base->with('error', "No data was imported. Please check your CSV column names match the sample file.");
    }
}
