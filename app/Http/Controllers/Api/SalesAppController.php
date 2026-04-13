<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Lead;
use App\Models\TenantFieldOption;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesAppController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('sales-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'uid' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'token' => $token,
                'tenant_id' => $user->tenant_id,
            ]
        ]);
    }

    public function getActivityOverview(Request $request)
    {
        $uid = $request->input('uid');


        $baseQuery = Lead::orderBy('id', 'desc');
        if (!empty($uid)) {
            $baseQuery->where(function ($q) use ($uid) {
                $q->where('created_by', $uid)->orWhere('user_id', $uid);
            });
        }


        $mapLeads = function ($leads) {
            return $leads->map(function ($lead) {
                $statusName = $lead->status;
                if ($statusName === 'New' || $statusName === 'New Lead') {
                    $statusName = 'New Lead Potential';
                }
                return [
                    'lid' => (string) $lead->id,
                    'business_name' => $lead->business_name,
                    'status' => $statusName,
                    'firstname' => $lead->first_name ?? $lead->contact_name,
                    'followup_date' => $lead->est_follow_up_date,
                    'follow_up_date' => $lead->est_follow_up_date,
                    'contactno' => $lead->phone,
                    'package' => $lead->package,
                    'plan' => $lead->amount,
                    'lead_assign' => null,
                    'created_by' => (string) $lead->created_by,
                    'est_contract_date' => $lead->est_contract_date,
                ];
            })->values()->toArray();
        };

        $today = now()->toDateString();
        $startOfWeek = now()->startOfWeek()->toDateString();
        $endOfWeek = now()->endOfWeek()->toDateString();


        $dailyFollowUp = (clone $baseQuery)->whereDate('est_follow_up_date', $today)->get();

        $weeklyFollowUp = (clone $baseQuery)->whereBetween('est_follow_up_date', [$startOfWeek, $endOfWeek])->get();
        // 3. Daily Appointment Data
        $dailyAppointment = (clone $baseQuery)->whereDate('installation_appointment_date', $today)->get();
        // 4. Weekly Appointment Data
        $weeklyAppointment = (clone $baseQuery)->whereBetween('installation_appointment_date', [$startOfWeek, $endOfWeek])->get();

        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Success',
            'is_requiered_update' => false,
            'isforce_update' => false,
            'details' => [
                'daily_follow_up_data' => $mapLeads($dailyFollowUp),
                'weekly_follow_up_data' => $mapLeads($weeklyFollowUp),
                'daily_appointment_data' => $mapLeads($dailyAppointment),
                'weekly_appointment_data' => $mapLeads($weeklyAppointment),
                'lead_assingend_data' => [] // Safely pass empty array as requested instead of risking crashes on unknown 'lead_assign_column' logic
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }


    public function getLeadList(Request $request)
    {
        $uidParam = $request->input('uid');
        $statusParam = $request->input('status');

        $query = Lead::orderBy('id', 'desc');

        // Apply UID filter if provided
        if (!empty($uidParam)) {
            $query->where('created_by', $uidParam);
        }

        // Status filter heavily restricted/removed as per explicit request
        // The mobile app will handle filtering if necessary, or the query defaults to all.

        // Additional Query Filters requested by Mobile App
        if ($request->filled('est_contract_date')) {
            $query->whereDate('est_contract_date', $request->input('est_contract_date'));
        }

        if ($request->filled('contact_number')) {
            $query->where('phone', 'like', '%' . $request->input('contact_number') . '%');
        }

        if ($request->filled('business_name')) {
            $query->where('business_name', 'like', '%' . $request->input('business_name') . '%');
        }

        $leads = $query->get();

        // Strictly map the raw Database properties into the Mobile App's required custom Dictionary format
        $mappedDetails = $leads->map(function ($lead) {

            // Ensure status strings exactly match the Sales DDL Value properties even for legacy db entries
            $statusName = $lead->status;
            if ($statusName === 'New' || $statusName === 'New Lead') {
                $statusName = 'New Lead Potential';
            }

            return [
                'lid' => (string) $lead->id,
                'business_name' => $lead->business_name,
                'status' => $statusName,
                'firstname' => $lead->first_name ?? $lead->contact_name,
                'followup_date' => $lead->est_follow_up_date,
                'follow_up_date' => $lead->est_follow_up_date,
                'contactno' => $lead->phone,
                'package' => $lead->package,
                'plan' => $lead->amount, // Note: In their provided example, plan held '15000' amount
                'lead_assign' => null,
                'created_by' => (string) $lead->created_by,
                'est_contract_date' => $lead->est_contract_date,
            ];
        });

        // Exact Outer Envelope Structure
        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Success',
            'is_requiered_update' => false,
            'isforce_update' => false,
            'details' => $mappedDetails
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getSaleDdlData(Request $request)
    {
        $fields = TenantFieldOption::all()->groupBy('field_name');

        $formatOptions = function ($keyName) use ($fields) {
            $options = [];
            if (isset($fields[$keyName])) {
                foreach ($fields[$keyName] as $field) {
                    // Mobile app specifies they might map key to string for packages.
                    // If they want string for key, they can save it in option_value, 
                    // but we will safely attach the extra dynamically created columns here!
                    $item = [
                        'key' => ($keyName === 'package' && !is_numeric($field->id)) ? $field->option_value : $field->id,
                        'value' => $field->option_value
                    ];

                    // Attach extra db columns seamlessly if requested
                    if ($keyName === 'package') {
                        $item['plan'] = $field->plan ?? null;
                        // For cost/value overrides based on their snippet example
                        if (!empty($field->weight)) {
                            $item['value'] = $field->weight;
                            $item['key'] = $field->option_value;
                        }
                    }

                    if ($keyName === 'status') {
                        $item['weight'] = $field->weight ?? "10%"; // Fallback mimicking their strict snippet
                        if ($field->id == 8001)
                            $item['key'] = 8001; // Force 8001 mappings just like before
                    }

                    $options[] = $item;
                }
            }
            return $options;
        };

        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Success',
            // Retrieve strictly from dynamic Option tables
            'sale_status' => $formatOptions('status'),
            'sale_source' => $formatOptions('source'),
            'sale_business_type' => $formatOptions('biz_type'),
            'sale_sme' => $formatOptions('sme'),
            'sale_designation' => $formatOptions('designation'),
            'division' => $formatOptions('division'),
            'township' => $formatOptions('township'),
            'followup_via' => $formatOptions('followup_via'),
            'discount' => $formatOptions('discount'),
            'plan' => $formatOptions('plan'),
            'package' => $formatOptions('package'),
            'customer_type' => $formatOptions('customer_type'),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getActivityDetail(Request $request)
    {
        $leadId = $request->input('leadId');
        $lead = Lead::find($leadId);

        if (!$lead) {
            return response()->json(['status' => 'error', 'message' => 'Lead not found.'], 404);
        }

        // Mock status mapping to fulfill Mobile App's expectation
        $statusKey = '8001';
        if ($lead->status === 'New')
            $statusKey = '8001';
        elseif ($lead->status === 'Followup')
            $statusKey = '8002';
        elseif ($lead->status === 'Active')
            $statusKey = '8003';
        elseif ($lead->status === 'Pending')
            $statusKey = '8004';

        $statusName = $lead->status;
        if ($lead->status === 'New')
            $statusName = 'New Lead Potential';

        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Success',
            'is_requiered_update' => false,
            'isforce_update' => false,
            'details' => [
                'lid' => (string) $lead->id,
                'uid' => $lead->uuid ?? (string) $lead->created_by,
                'profile_id' => $lead->tenant_id ?? null,
                'customer_type' => $lead->customer_type ?? null,
                'firstname' => $lead->first_name ?? $lead->contact_name,
                'lastname' => $lead->last_name,
                'email' => $lead->contact_email,
                'address' => $lead->address,
                'contact_information' => $lead->contact_information ?? null,
                'package' => $lead->package,
                'plan' => $lead->plan,
                'notes' => $lead->note,
                'installation' => $lead->installation_appointment ?? null,
                'lead_source' => $lead->source,
                'business_type' => $lead->biz_type,
                'business_category' => $lead->business_category ?? null,
                'township' => $lead->township,
                'division' => $lead->division,
                'contactno' => $lead->phone,
                'business_name' => $lead->business_name,
                'current_isp' => $lead->current_isp ?? null,
                'potential' => (string) $lead->potential,
                'weighted' => $lead->weighted,
                'followup_via' => $lead->followup_via ?? $lead->channel,
                'followup_date' => $lead->est_follow_up_date,
                'estimate_flightdate' => $lead->estimate_flightdate ?? null,
                'channel' => (string) $lead->channel,
                'designation' => $lead->designation ?? null,
                'compound' => $lead->compound ?? null,
                'created_by' => (string) $lead->created_by,
                'updated_by' => (string) $lead->updated_by ?? (string) $lead->created_by,
                'creation_date' => $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : null,
                'modified_date' => $lead->updated_at ? $lead->updated_at->format('Y-m-d H:i:s') : null,
                'status_key' => $statusKey,
                'status' => $statusName,
                'package_total' => (string) $lead->amount,
                'referrel_id' => $lead->referral_id ?? null,
                'lead_assign' => $lead->lead_assign ?? null,
                'isReferal' => $lead->is_referral ? "1" : "0",
                'designation_other' => $lead->designation_other ?? null,
                'business_type_other' => $lead->business_type_other ?? null,
                'secondary_contact_number' => $lead->secondary_contact_number,
                'discount' => $lead->discount ? $lead->discount . '%' : "0%",
                'meeting_notes' => $lead->meeting_note,
                'next_step' => $lead->next_step,
                'est_contract_date' => $lead->est_contract_date,
                'est_start_date' => $lead->est_start_date,
                'follow_up_date' => $lead->est_follow_up_date,
                'latitude' => null,
                'longitude' => null,
                'contract_date' => $lead->contracted_date,
                'installation_appointment_date' => $lead->installation_appointment,
                'customer_note' => $lead->customer_note
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }


    public function postLeadForm(Request $request)
    {
        $data = [
            'biz_type' => $request->input('business_type'),
            'source' => $request->input('source'),
            'phone' => $request->input('contact_number'),
            'contact_name' => $request->input('contact_person'),
            'first_name' => $request->input('contact_person'),
            'secondary_contact_number' => $request->input('secondary_contact_number'),
            'contact_email' => $request->input('email'),
            'business_name' => $request->input('business_name'),
            'division' => $request->input('division'),
            'township' => $request->input('township'),
            'address' => $request->input('address'),
            'status' => $request->input('status', 'New'),
            'designation' => $request->input('designation'),
            'potential' => $request->input('potential'),
            'est_follow_up_date' => $request->input('follow_up_date', $request->input('followup_date')),
            'est_contract_date' => $request->input('est_contract_date'),
            'est_start_date' => $request->input('est_start_date'),
            'is_referral' => $request->input('isReferral') == "1",
            'note' => $request->input('reason'),
            'contracted_date' => $request->input('contracted_date'),
            'installation_appointment_date' => $request->input('installation_appointment_date'),
            'amount' => $request->input('amount', 0),
            'plan' => $request->input('plan'),
            'package' => $request->input('package'),
            'discount' => $request->input('discount'),
            'meeting_note' => $request->input('meeting_notes'),
            'next_step' => $request->input('next_step'),
            // "lat", "long", "customer_type", "isNotified" ignored to prevent column crashes
        ];

        $data = array_filter($data, fn($v) => !is_null($v));

        $lid = $request->input('lid');
        if (!empty($lid)) {
            $lead = Lead::find($lid);
            if ($lead) {
                $lead->update($data);
                return response()->json([
                    'status' => 'Success',
                    'response_code' => '000',
                    'description' => 'Lead updated successfully.',
                    'is_requiered_update' => false,
                    'isforce_update' => false,
                    'details' => $lead
                ], 200, [], JSON_UNESCAPED_SLASHES);
            }
        }

        $data['created_by'] = $request->input('uid', auth()->id());
        $lead = Lead::create($data);

        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Lead created successfully.',
            'is_requiered_update' => false,
            'isforce_update' => false,
            'details' => $lead
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getContractedLeadList(Request $request)
    {
        $uidParam = $request->input('uid');

        // Query contracted leads
        $query = Lead::whereNotNull('est_contract_date')->orderBy('id', 'desc');

        if (!empty($uidParam)) {
            $query->where('created_by', $uidParam);
        }

        if ($request->filled('business_name')) {
            $query->where('business_name', 'like', '%' . $request->input('business_name') . '%');
        }

        $leads = $query->get();

        $mappedDetails = $leads->map(function ($lead) {
            $statusName = $lead->status;
            if ($statusName === 'New')
                $statusName = 'New Lead Potential';

            return [
                'profile_id' => $lead->uuid ?? (string) $lead->id,
                'business_name' => $lead->business_name,
                'status' => $statusName,
                'contact_information' => $lead->phone ?? $lead->contact_information,
                'sign' => '/esignature/' . ($lead->uuid ?? $lead->id) . '/app'
            ];
        });

        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Success',
            'is_requiered_update' => false,
            'isforce_update' => false,
            'details' => $mappedDetails
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getContractedDetail(Request $request)
    {
        // Enforce the new requirement to lookup by profile_id instead of lid
        $profileId = $request->input('profile_id', $request->input('lid', $request->input('leadId')));
        $lead = Lead::where('id', $profileId)->orWhere('uuid', $profileId)->first();

        if (!$lead) {
            return response()->json(['status' => 'error', 'message' => 'Contracted lead not found.'], 404);
        }

        // Mock status mapping to fulfill Mobile App's expectation
        $statusKey = '8001';
        if ($lead->status === 'New')
            $statusKey = '8001';
        elseif ($lead->status === 'Followup')
            $statusKey = '8002';
        elseif ($lead->status === 'Active')
            $statusKey = '8003';
        elseif ($lead->status === 'Pending')
            $statusKey = '8004';

        $statusName = $lead->status;
        if ($lead->status === 'New' || $lead->status === 'New Lead') {
            $statusName = 'New Lead Potential';
        }

        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Success',
            'is_requiered_update' => false,
            'isforce_update' => false,
            'details' => [
                'profile_id' => $lead->uuid ?? (string) $lead->id,
                'division' => $lead->division,
                'township' => $lead->township,
                'plan' => $lead->plan,
                'package' => $lead->package,
                'package_total' => (string) ($lead->amount ?? 0),
                'address' => $lead->address,
                'email' => $lead->contact_email,
                'latitude' => null,
                'longitude' => null,
                'notes' => $lead->note,
                'installation' => $lead->installation_appointment ?? "",
                'contract_date' => $lead->contracted_date ?? "",
                'business_name' => $lead->business_name,
                'customer_type' => $lead->customer_type ?? "",
                'firstname' => $lead->first_name ?? $lead->contact_name,
                'phone_1' => $lead->phone,
                'phone_2' => $lead->secondary_contact_number
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function postContractedData(Request $request)
    {
        $profileId = $request->input('profile_id', $request->input('leadId', $request->input('lid')));
        $uid = $request->input('uid');

        if (!$profileId) {
            return response()->json(['status' => 'error', 'message' => 'Profile ID identifier required.'], 400);
        }

        $lead = Lead::where('id', $profileId)->orWhere('uuid', $profileId)->first();

        // Exact mapping from the mobile app's JSON newly standardized requirement
        $data = [
            'first_name' => $request->input('name'),
            'contact_name' => $request->input('name', $lead->contact_name ?? null),
            'business_name' => $request->input('business_name'),
            'phone' => $request->input('contact_number', $request->input('contact_no')),
            'secondary_contact_number' => $request->input('secondary_contact_number'),
            'contact_email' => $request->input('email'),
            'division' => $request->input('division'),
            'township' => $request->input('township'),
            'address' => $request->input('address'),
            'package' => $request->input('package'),
            'plan' => $request->input('plan'),
            'amount' => $request->input('amount'),
            'contracted_date' => $request->input('contracted_date'),
            'installation_appointment_date' => $request->input('installation_appointment_date'),
            'customer_note' => $request->input('customer_note'),
            // "lat", "long", and "customer_type" are ignored currently to prevent SQL ColumnNotFound crashes.
        ];

        $data = array_filter($data, fn($v) => !is_null($v));

        if (!$lead) {
            $data['created_by'] = $uid ?? auth()->id();
            $lead = Lead::create($data);
            return response()->json([
                'status' => 'Success',
                'response_code' => '000',
                'description' => 'Lead contracted successfully.',
                'is_requiered_update' => false,
                'isforce_update' => false,
                'details' => $lead
            ], 200, [], JSON_UNESCAPED_SLASHES);
        }

        $lead->update($data);

        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Lead updated successfully.',
            'is_requiered_update' => false,
            'isforce_update' => false,
            'details' => $lead
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
