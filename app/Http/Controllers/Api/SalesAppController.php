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
        $user = auth()->user();

        if ($uid != $user->id && !$user->is_admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access to UID.'], 403);
        }

        $totalLeads = Lead::count();
        $contracted = Lead::whereNotNull('est_contract_date')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_leads' => $totalLeads,
                'active' => $contracted,
                'pending' => Lead::where('status', 'followup')->count(),
            ]
        ]);
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
            return [
                'lid'               => (string) $lead->id,
                'business_name'     => $lead->business_name,
                'status'            => $lead->status,
                'firstname'         => $lead->first_name ?? $lead->contact_name,
                'followup_date'     => $lead->est_follow_up_date,
                'follow_up_date'    => $lead->est_follow_up_date,
                'contactno'         => $lead->phone,
                'package'           => $lead->package,
                'plan'              => $lead->amount, // Note: In their provided example, plan held '15000' amount
                'lead_assign'       => null,
                'created_by'        => (string) $lead->created_by,
                'est_contract_date' => $lead->est_contract_date,
            ];
        });

        // Exact Outer Envelope Structure
        return response()->json([
            'status'              => 'Success',
            'response_code'       => '000',
            'description'         => 'Success',
            'is_requiered_update' => false,
            'isforce_update'      => false,
            'details'             => $mappedDetails
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getSaleDdlData(Request $request)
    {
        $fields = TenantFieldOption::all()->groupBy('field_name');

        // Extract straight from the database: use real database ID as the "key"!
        $formatOptions = function ($keyName) use ($fields) {
            $options = [];
            if (isset($fields[$keyName])) {
                foreach ($fields[$keyName] as $field) {
                    $options[] = [
                        'key' => $field->id, 
                        'value' => $field->option_value
                    ];
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
        if ($lead->status === 'New') $statusKey = '8001';
        elseif ($lead->status === 'Followup') $statusKey = '8002';
        elseif ($lead->status === 'Active') $statusKey = '8003';
        elseif ($lead->status === 'Pending') $statusKey = '8004';
        
        $statusName = $lead->status;
        if ($lead->status === 'New') $statusName = 'New Lead Potential';

        return response()->json([
            'status'              => 'Success',
            'response_code'       => '000',
            'description'         => 'Success',
            'is_requiered_update' => false,
            'isforce_update'      => false,
            'details'             => [
                'lid'                 => (string) $lead->id,
                'uid'                 => $lead->uuid ?? (string) $lead->created_by,
                'profile_id'          => $lead->tenant_id ?? null,
                'customer_type'       => $lead->customer_type ?? null,
                'firstname'           => $lead->first_name ?? $lead->contact_name,
                'lastname'            => $lead->last_name,
                'email'               => $lead->contact_email,
                'address'             => $lead->address,
                'contact_information' => $lead->contact_information ?? null,
                'package'             => $lead->package,
                'plan'                => $lead->plan,
                'notes'               => $lead->note,
                'installation'        => $lead->installation_appointment ?? null,
                'lead_source'         => $lead->source,
                'business_type'       => $lead->biz_type,
                'business_category'   => $lead->business_category ?? null,
                'township'            => $lead->township,
                'division'            => $lead->division,
                'contactno'           => $lead->phone,
                'business_name'       => $lead->business_name,
                'current_isp'         => $lead->current_isp ?? null,
                'potential'           => (string) $lead->potential,
                'weighted'            => $lead->weighted,
                'followup_via'        => $lead->followup_via ?? $lead->channel,
                'followup_date'       => $lead->est_follow_up_date,
                'estimate_flightdate' => $lead->estimate_flightdate ?? null,
                'channel'             => (string) $lead->channel,
                'designation'         => $lead->designation ?? null,
                'compound'            => $lead->compound ?? null,
                'created_by'          => (string) $lead->created_by,
                'updated_by'          => (string) $lead->updated_by ?? (string) $lead->created_by,
                'creation_date'       => $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : null,
                'modified_date'       => $lead->updated_at ? $lead->updated_at->format('Y-m-d H:i:s') : null,
                'status_key'          => $statusKey,
                'status'              => $statusName,
                'package_total'       => (string) $lead->amount,
                'referrel_id'         => $lead->referral_id ?? null,
                'lead_assign'         => $lead->lead_assign ?? null,
                'isReferal'           => $lead->is_referral ? "1" : "0",
                'designation_other'   => $lead->designation_other ?? null,
                'business_type_other' => $lead->business_type_other ?? null,
                'secondary_contact_number' => $lead->secondary_contact_number,
                'discount'            => $lead->discount ? $lead->discount . '%' : "0%",
                'meeting_notes'       => $lead->meeting_note,
                'next_step'           => $lead->next_step,
                'est_contract_date'   => $lead->est_contract_date,
                'est_start_date'      => $lead->est_start_date,
                'follow_up_date'      => $lead->est_follow_up_date,
                'latitude'            => null,
                'longitude'           => null,
                'contract_date'       => $lead->contracted_date,
                'installation_appointment_date' => $lead->installation_appointment,
                'customer_note'       => $lead->customer_note
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

  
    public function postLeadForm(Request $request)
    {
        // Parse mobile app status mappings back to physical Database String
        $incomingStatus = $request->input('status', 'New');
        if ($incomingStatus === 'New Lead') {
            $incomingStatus = 'New';
        }

        $data = [
            'biz_type'           => $request->input('business_type'),
            'source'             => $request->input('source', $request->input('lead_source')),
            'phone'              => $request->input('contact_number'),
            // Map 'contact_person' as requested by their JSON
            'contact_name'       => $request->input('contact_person', $request->input('name')), 
            'first_name'         => $request->input('contact_person', $request->input('name')),
            'secondary_contact_number' => $request->input('secondary_contact_number'),
            // Handle the specific 'emiail' typo exactly as written in their JSON payload
            'contact_email'      => $request->input('emiail', $request->input('email')),
            'business_name'      => $request->input('business_name'),
            'division'           => $request->input('division'),
            'township'           => $request->input('township'),
            'address'            => $request->input('address'),
            'status'             => $incomingStatus,
            'business_category'  => $request->input('sme'),
            'designation'        => $request->input('designation'),
            'potential'          => $request->input('potential'),
            'product'            => $request->input('product'),
            'amount'             => $request->input('amount', 0),
            'plan'               => $request->input('plan'),
            'package'            => $request->input('package'),
            'discount'           => $request->input('discount'),
            'meeting_note'       => $request->input('meeting_notes'),
            'next_step'          => $request->input('next_step'),
        ];

        $data = array_filter($data, fn($v) => !is_null($v));

        // Use 'uid' securely from JSON if provided, otherwise fallback to Auth
        if (!isset($data['created_by']) && $request->filled('uid')) {
            $data['created_by'] = $request->input('uid');
        } elseif (!isset($data['created_by'])) {
            $data['created_by'] = auth()->id();
        }

        $lid = $request->input('lid');
        if (!empty($lid)) {
            $lead = Lead::find($lid);
            if ($lead) {
                $lead->update($data);
                return response()->json([
                    'status'              => 'Success',
                    'response_code'       => '000',
                    'description'         => 'Lead updated successfully.',
                    'is_requiered_update' => false,
                    'isforce_update'      => false,
                    'details'             => $lead // Or leave empty [] depending on App preference
                ], 200, [], JSON_UNESCAPED_SLASHES);
            }
        }

        $lead = Lead::create($data);

        return response()->json([
            'status'              => 'Success',
            'response_code'       => '000',
            'description'         => 'Lead created successfully.',
            'is_requiered_update' => false,
            'isforce_update'      => false,
            'details'             => $lead
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
            if ($statusName === 'New') $statusName = 'New Lead Potential';

            return [
                'profile_id'          => $lead->uuid ?? (string) $lead->id,
                'business_name'       => $lead->business_name,
                'status'              => $statusName,
                'contact_information' => $lead->phone ?? $lead->contact_information,
                'sign'                => '/esignature/' . ($lead->uuid ?? $lead->id) . '/app'
            ];
        });

        return response()->json([
            'status'              => 'Success',
            'response_code'       => '000',
            'description'         => 'Success',
            'is_requiered_update' => false,
            'isforce_update'      => false,
            'details'             => $mappedDetails
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getContractedDetail(Request $request)
    {
        $leadId = $request->input('leadId');
        $lead = Lead::where('id', $leadId)->orWhere('uuid', $leadId)->first();

        if (!$lead) {
            return response()->json(['status' => 'error', 'message' => 'Contracted lead not found.'], 404);
        }

        // Mock status mapping to fulfill Mobile App's expectation
        $statusKey = '8001';
        if ($lead->status === 'New') $statusKey = '8001';
        elseif ($lead->status === 'Followup') $statusKey = '8002';
        elseif ($lead->status === 'Active') $statusKey = '8003';
        elseif ($lead->status === 'Pending') $statusKey = '8004';
        
        $statusName = $lead->status;
        if ($lead->status === 'New') $statusName = 'New Lead Potential';

        return response()->json([
            'status'              => 'Success',
            'response_code'       => '000',
            'description'         => 'Success',
            'is_requiered_update' => false,
            'isforce_update'      => false,
            'details'             => [
                'lid'                 => (string) $lead->id,
                'uid'                 => $lead->uuid ?? (string) $lead->created_by,
                'profile_id'          => $lead->tenant_id ?? null,
                'customer_type'       => $lead->customer_type ?? null,
                'firstname'           => $lead->first_name ?? $lead->contact_name,
                'lastname'            => $lead->last_name,
                'email'               => $lead->contact_email,
                'address'             => $lead->address,
                'contact_information' => $lead->contact_information ?? null,
                'package'             => $lead->package,
                'plan'                => $lead->plan,
                'notes'               => $lead->note,
                'installation'        => $lead->installation_appointment ?? null,
                'lead_source'         => $lead->source,
                'business_type'       => $lead->biz_type,
                'business_category'   => $lead->business_category ?? null,
                'township'            => $lead->township,
                'division'            => $lead->division,
                'contactno'           => $lead->phone,
                'business_name'       => $lead->business_name,
                'current_isp'         => $lead->current_isp ?? null,
                'potential'           => (string) $lead->potential,
                'weighted'            => $lead->weighted,
                'followup_via'        => $lead->followup_via ?? $lead->channel,
                'followup_date'       => $lead->est_follow_up_date,
                'estimate_flightdate' => $lead->estimate_flightdate ?? null,
                'channel'             => (string) $lead->channel,
                'designation'         => $lead->designation ?? null,
                'compound'            => $lead->compound ?? null,
                'created_by'          => (string) $lead->created_by,
                'updated_by'          => (string) $lead->updated_by ?? (string) $lead->created_by,
                'creation_date'       => $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : null,
                'modified_date'       => $lead->updated_at ? $lead->updated_at->format('Y-m-d H:i:s') : null,
                'status_key'          => $statusKey,
                'status'              => $statusName,
                'package_total'       => (string) $lead->amount,
                'referrel_id'         => $lead->referral_id ?? null,
                'lead_assign'         => $lead->lead_assign ?? null,
                'isReferal'           => $lead->is_referral ? "1" : "0",
                'designation_other'   => $lead->designation_other ?? null,
                'business_type_other' => $lead->business_type_other ?? null,
                'secondary_contact_number' => $lead->secondary_contact_number,
                'discount'            => $lead->discount ? $lead->discount . '%' : "0%",
                'meeting_notes'       => $lead->meeting_note,
                'next_step'           => $lead->next_step,
                'est_contract_date'   => $lead->est_contract_date,
                'est_start_date'      => $lead->est_start_date,
                'follow_up_date'      => $lead->est_follow_up_date,
                'latitude'            => null,
                'longitude'           => null,
                'contract_date'       => $lead->contracted_date,
                'installation_appointment_date' => $lead->installation_appointment,
                'customer_note'       => $lead->customer_note
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function postContractedData(Request $request)
    {
        $leadId = $request->input('leadId') ?? $request->input('profile_id');

        if (!$leadId) {
            return response()->json(['status' => 'error', 'message' => 'Lead identifier required.'], 400);
        }

        $lead = Lead::where('id', $leadId)->orWhere('uuid', $leadId)->first();

        $data = [
            'phone'               => $request->input('contact_number', $lead->phone ?? null),
            'address'             => $request->input('address'),
            'package'             => $request->input('package'),
            'plan'                => $request->input('plan'),
            'first_name'          => $request->input('name'),
            'business_name'       => $request->input('business_name'),
            'secondary_contact_number' => $request->input('secondary_contact_number'),
            'contact_email'       => $request->input('email'),
            'division'            => $request->input('division'),
            'township'            => $request->input('township'),
            'est_contract_date'   => $request->input('contracted_date'),   
            'installation_appointment' => $request->input('installation_appointment_date'), 
            'note'                => $request->input('customer_note'),
            'amount'              => $request->input('amount'),
        ];

        
        $data = array_filter($data, fn($v) => !is_null($v));

        if (!$lead) {
            $data['created_by'] = auth()->id();
            $lead = Lead::create($data);
            return response()->json(['status' => 'success', 'message' => 'Lead contracted successfully.', 'data' => $lead]);
        }

        $lead->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Contract details updated successfully.',
            'data'    => $lead
        ]);
    }
}
