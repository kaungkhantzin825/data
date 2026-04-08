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
        $statusParam = $request->input('status');

        $query = Lead::orderBy('id', 'desc');

        // If the mobile app passes a status filter (e.g., 8001)
        if ($statusParam) {
            if (is_numeric($statusParam)) {
                $option = TenantFieldOption::find($statusParam);
                if ($option) {
                    $query->where('status', $option->option_value);
                } else {
                    $query->where('status', $statusParam);
                }
            } else {
                $query->where('status', $statusParam);
            }
        }

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
