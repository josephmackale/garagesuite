<?php

namespace App\Http\Controllers;

use App\Models\SmsCampaign;
use App\Models\Customer;
use App\Models\SmsMessage;
use App\Services\SmsSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsCampaignController extends Controller
{
    /**
     * List all SMS campaigns for the current garage.
     */
    public function index()
    {
        $user = Auth::user();

        $garageId = $user->garage_id ?? ($user->garage->id ?? null);

        if (! $garageId) {
            abort(403, 'No garage associated with this user.');
        }

        $campaigns = SmsCampaign::where('garage_id', $garageId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('sms_campaigns.index', compact('campaigns'));
    }

    /**
     * Show form to create a new campaign.
     */
    public function create()
    {
        return view('sms_campaigns.create');
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $garageId = $user->garage_id ?? ($user->garage->id ?? null);

        if (! $garageId) {
            abort(403, 'No garage associated with this user.');
        }

        $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:480'], // ~3 segments
        ]);

        $campaign = SmsCampaign::create([
            'garage_id'        => $garageId,
            'name'             => $request->name,
            'message'          => $request->message,
            'filters_json'     => null,
            'total_recipients' => 0,
            'sent_count'       => 0,
            'status'           => 'draft',
        ]);

        return redirect()
            ->route('sms-campaigns.show', $campaign)
            ->with('success', 'SMS campaign created. Next: send the campaign.');
    }

    /**
     * Display a single campaign + its delivery log.
     */
    public function show(SmsCampaign $campaign)
    {
        $user = Auth::user();
        $garageId = $user->garage_id ?? ($user->garage->id ?? null);

        if (! $garageId || $campaign->garage_id !== $garageId) {
            abort(403);
        }

        // Load related messages + customer for the delivery log
        $campaign->load('messages.customer');

        $messages = $campaign->messages()
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->take(50)   // you can change to paginate later
            ->get();

        return view('sms_campaigns.show', compact('campaign', 'messages'));
    }

    /**
     * Simple send: loop over all customers with phones and send immediately.
     */
    public function send(SmsCampaign $campaign, SmsSender $smsSender)
    {
        $user = Auth::user();
        $garageId = $user->garage_id ?? ($user->garage->id ?? null);

        if (! $garageId || $campaign->garage_id !== $garageId) {
            abort(403);
        }

        // Get all customers in this garage with a phone number
        $customers = Customer::where('garage_id', $garageId)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        if ($customers->isEmpty()) {
            return back()->with('success', 'No customers with phone numbers to send to.');
        }

        $sentCount = 0;

        foreach ($customers as $customer) {
            $phone = $customer->phone;

            $ok = $smsSender->send($phone, $campaign->message, [
                'campaign_id' => $campaign->id,
                'customer_id' => $customer->id,
                'garage_id'   => $garageId,
            ]);

            // log to sms_messages
            SmsMessage::create([
                'garage_id'            => $garageId,
                'sms_campaign_id'      => $campaign->id,
                'customer_id'          => $customer->id,
                'phone'                => $phone,
                'message'              => $campaign->message,
                'status'               => $ok ? 'sent' : 'failed',
                'provider'             => config('sms.driver', 'fake'),
                'provider_message_id'  => null,
                'error_message'        => $ok ? null : 'Failed to send (fake driver).',
                'sent_at'              => now(),
            ]);

            if ($ok) {
                $sentCount++;
            }
        }

        // Update statistics on the campaign
        $campaign->update([
            'status'          => 'sent',
            'sent_count'      => $sentCount,
            'total_recipients'=> $customers->count(),
        ]);

        return back()->with('success', "SMS campaign sent (fake driver) to {$sentCount} customers.");
    }
}
