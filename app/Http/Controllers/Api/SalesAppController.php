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
        $uid = $request->input('uid');
        $user = auth()->user();

        $leads = Lead::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $leads
        ]);
    }

    public function getSaleDdlData(Request $request)
    {
        $fields = TenantFieldOption::all()->groupBy('field_name');

        // Helper to map dynamic db fields to the requested {"key": id, "value": text} format
        $formatOptions = function ($keyName, $startingKey = 1000) use ($fields) {
            $options = [];
            if (isset($fields[$keyName])) {
                foreach ($fields[$keyName]->pluck('option_value')->toArray() as $idx => $val) {
                    $options[] = ['key' => $startingKey + $idx, 'value' => $val];
                }
            }
            return $options;
        };

        return response()->json([
            'status' => 'Success',
            'response_code' => '000',
            'description' => 'Success',
            'sale_status' => [
                ['key' => 8001, 'value' => 'New Lead Potential', 'weight' => '10%'],
                ['key' => 8002, 'value' => 'Followup', 'weight' => '20%'],
                ['key' => 8003, 'value' => 'Active', 'weight' => '100%'],
                ['key' => 8004, 'value' => 'Pending', 'weight' => '50%'],
            ],
            'sale_source' => (!empty($formatOptions('source'))) ? $formatOptions('source', 4000) : [['key' => 4000, 'value' => 'Door to Door']],
            'sale_business_type' => (!empty($formatOptions('biz_type'))) ? $formatOptions('biz_type', 5000) : [['key' => 5000, 'value' => 'Condo']],
            'sale_sme' => [
                ['key' => 9000, 'value' => 'Automotive']
            ],
            'sale_designation' => [
                ['key' => 'D0001', 'value' => 'CEO']
            ],
            'division' => (!empty($formatOptions('division'))) ? $formatOptions('division', 5000) : [['key' => 5000, 'value' => 'Yangon']],
            'township' => (!empty($formatOptions('township'))) ? $formatOptions('township', 21000) : [['key' => 21000, 'value' => 'Dagon']],
            'followup_via' => [
                ['key' => 10, 'value' => 'In Person']
            ],
            'discount' => [
                ['key' => 0, 'value' => '0%']
            ],
            'plan' => [
                ['key' => 11000, 'value' => 'Premium Home Fiber']
            ],
            'package' => [
                ['key' => '300Mbps', 'value' => '300000', 'plan' => 'Mojoenet Elite']
            ],
            'customer_type' => [
                ['key' => 'consumer', 'value' => 'Consumer']
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function getActivityDetail(Request $request)
    {
        $leadId = $request->input('leadId');
        $lead = Lead::find($leadId);

        if (!$lead) {
            return response()->json(['status' => 'error', 'message' => 'Lead not found.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $lead
        ]);
    }

  
    public function postLeadForm(Request $request)
    {
        $data = [
            'biz_type'      => $request->input('business_type'),
            'source'        => $request->input('lead_source', $request->input('source')),
            'phone'         => $request->input('contact_number'),
            'contact_name'  => $request->input('name', $request->input('business_name')), // Ensures database requirement is met
            'first_name'    => $request->input('name'),
            'secondary_contact_number' => $request->input('secondary_contact_number'),
            'contact_email' => $request->input('email'),
            'business_name' => $request->input('business_name'),
            'division'      => $request->input('division'),
            'township'      => $request->input('township'),
            'address'       => $request->input('address'),
            'status'        => $request->input('status', 'New'),
            'product'       => $request->input('product'),
            'amount'        => $request->input('amount', 0),
            'plan'          => $request->input('plan'),
            'package'       => $request->input('package'),
            'discount'      => $request->input('discount'),
            'meeting_note'  => $request->input('meeting_notes'),
            'next_step'     => $request->input('next_step'),
        ];

        $data = array_filter($data, fn($v) => !is_null($v));

        $lid = $request->input('lid');
        if (!empty($lid)) {
            $lead = Lead::find($lid);
            if ($lead) {
                $lead->update($data);
                return response()->json(['status' => 'success', 'message' => 'Lead updated successfully.', 'data' => $lead]);
            }
        }

        $data['created_by'] = auth()->id();
        $lead = Lead::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Lead created successfully.',
            'data' => $lead
        ]);
    }

    public function getContractedLeadList(Request $request)
    {
        $user = auth()->user();
         $leads = Lead::whereNotNull('est_contract_date')->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $leads
        ]);
    }

    public function getContractedDetail(Request $request)
    {
        $leadId = $request->input('leadId');
                $lead = Lead::where('id', $leadId)->orWhere('uuid', $leadId)->first();

        if (!$lead) {
            return response()->json(['status' => 'error', 'message' => 'Contracted lead not found.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $lead
        ]);
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
