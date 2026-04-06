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
                if (!$l->created_at) return false;
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

                if (!$match) return false;

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

       
        $monthLeads = $allLeads->filter(function ($l) use ($reqMonth, $reqYear) {
            if (!$l->created_at) return false;
            $ca = \Carbon\Carbon::parse($l->created_at);
            return $ca->month == $reqMonth && $ca->year == $reqYear;
        });

        $allLeadsReport = $buildMetrics($allLeads);
        $monthlyOverview = $buildMetrics($monthLeads);

        return Inertia::render('Dashboard', [
            'activeTab'      => 'dashboard',
            'summaryReports' => $summaryReports,
            'diaReports'     => $diaReports,
            'totalLeads'     => $allLeads->count(),
            'allLeadsReport' => $allLeadsReport,
            'monthlyOverview'=> $monthlyOverview,
            'expiringPlans'  => $expiringPlans,
            'reqMonth'       => $reqMonth,
            'reqYear'        => $reqYear,
            'fieldOptions'   => $this->getTenantFieldOptions(),
        ]);
    }

    public function index(Request $request)
    {
        $query = Lead::query();
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

        \Log::info('Lead Query:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings(), 'user' => auth()->id()]);

        $leads = $query->orderByDesc('id')->paginate(10)->withQueryString();

        $availablePlans = Lead::whereNotNull('plan')->where('plan', '!=', '')->distinct()->pluck('plan');
        $availableBizTypes = Lead::whereNotNull('biz_type')->where('biz_type', '!=', '')->distinct()->pluck('biz_type');

        return Inertia::render('Dashboard', [
            'leads' => $leads,
            'filters' => $request->only(['search', 'plan', 'biz_type', 'status']),
            'activeTab' => 'lists',
            'availablePlans' => $availablePlans,
            'availableBizTypes' => $availableBizTypes,
            'fieldOptions' => $this->getTenantFieldOptions(),
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

        $httpHeaders = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=leads_export_' . date('Y-m-d_H-i-s') . '.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $csvColumns = [
            'id', 'uuid', 'business_name', 'first_name', 'last_name',
            'contact_email', 'phone', 'secondary_contact_number',
            'biz_type', 'source', 'division', 'township', 'address',
            'product', 'package', 'package_total', 'discount',
            'status', 'channel',
            'installation_appointment', 'est_contract_date',
            'est_start_date', 'est_follow_up_date',
            'is_referral', 'meeting_note', 'next_step', 'created_at',
        ];

        $callback = function () use ($leads, $csvColumns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvColumns);

            foreach ($leads as $lead) {
                
                $nameParts = explode(' ', $lead->contact_name ?? '', 2);
                fputcsv($file, [
                    $lead->id,
                    $lead->uuid,
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

        return response()->stream($callback, 200, $httpHeaders);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'            => 'required|file',
            'update_existing' => 'nullable|string',
        ]);

        $updateExisting = $request->update_existing === 'true';

        $file   = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $rawHeaders = fgetcsv($handle, 5000, ',');
        if (!$rawHeaders) {
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
            if ($phone) {
                $q = Lead::where('phone', $phone);
                $existing = $q->first();
            }

            if ($existing) {
                if ($updateExisting) {
                } else {
                    $existing = null;
                }
            }

            $refRaw     = strtolower($get(['is_referral', 'referral', 'ref']) ?? 'no');
            $isReferral = in_array($refRaw, ['yes', '1', 'true']) ? 1 : 0;

            $mappedData = [
                'business_name'            => $get(['business_name', 'business name', 'company', 'company_name']),
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
                'amount'                   => is_numeric($a = $get(['package_total', 'package total', 'amount', 'total'])) ? (float)$a : 0,
                'status'                   => $get(['status']),
                'channel'                  => $get(['channel', 'sale_channel']),
                'meeting_note'             => $get(['meeting_note', 'meeting note', 'notes', 'note']),
                'next_step'                => $get(['next_step', 'next step', 'action']),
                'installation_appointment' => $parseDate($get(['installation_appointment', 'installation appointment']), true),
                'est_contract_date'        => $parseDate($get(['est_contract_date', 'est. contract date', 'est contract date', 'contract_date'])),
                'est_start_date'           => $parseDate($get(['est_start_date', 'est. start date', 'est start date', 'start_date'])),
                'est_follow_up_date'       => $parseDate($get(['est_follow_up_date', 'est. follow up date', 'est follow up date', 'follow_up_date'])),
                'is_referral'              => $isReferral,
            ];

            $mappedData = array_filter($mappedData, fn($v) => $v !== null && $v !== '');
            $mappedData['is_referral'] = $isReferral; // always set

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
            if ($duplicateSkip > 0) $msg .= " Skipped (duplicate): {$duplicateSkip}.";
            if ($nullSkip  > 0)     $msg .= " Skipped (blank rows): {$nullSkip}.";
            if ($errors    > 0)     $msg .= " Errors: {$errors}.";
            return $base->with('success', $msg);
        }

        if ($errors > 0) {
            return $base->with('error', "Import failed — {$errors} row(s) could not be saved.");
        }

        if ($duplicateSkip > 0) {
            return $base->with('error',
                "No new leads created — {$duplicateSkip} row(s) skipped (phone already exists). " .
                "Tick 'Update existing' to overwrite them.");
        }

        if ($nullSkip > 0) {
            return $base->with('error',
                "No leads imported — {$nullSkip} row(s) had no phone number AND no name. " .
                "Check that your CSV column names match exactly: phone, first_name, last_name.");
        }

        return $base->with('error', "No data was imported. Please check your CSV column names match the sample file.");
    }
}
