<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotApiToken;
use App\Models\BotLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/** Admin → Bot API & leads: tokens for the social automation app and the leads it sends. */
class BotApiController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $status = array_key_exists((string) $status, BotLead::STATUSES) ? $status : null;

        $tokens = BotApiToken::orderByRaw('revoked_at IS NULL DESC')->latest('id')->get();
        $leads = BotLead::with('product:id,name_bn,name_en,slug')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(30)->withQueryString();
        $counts = BotLead::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.bot-api', compact('tokens', 'leads', 'counts', 'status'));
    }

    public function storeToken(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(array_keys(BotApiToken::ABILITIES))],
        ], [
            'name.required' => 'টোকেনের একটা নাম দিন (যেমন: Facebook Bot)।',
            'abilities.required' => 'অন্তত একটি অনুমতি বেছে নিন।',
        ]);

        [$token, $plain] = BotApiToken::issue($data['name'], $data['abilities'], $request->user()?->id);
        Log::info('Bot API token created', ['user_id' => $request->user()?->id, 'token_id' => $token->id]);

        // Shown exactly once on the next page.
        return redirect()->route('admin.bot-api.index')
            ->with('bot_plain_token', $plain)
            ->with('success', 'টোকেন তৈরি হয়েছে। এখনই কপি করে নিন — পরে আর দেখা যাবে না।');
    }

    public function revokeToken(Request $request, BotApiToken $token)
    {
        if (! $token->isRevoked()) {
            $token->forceFill(['revoked_at' => now()])->save();
            Log::info('Bot API token revoked', ['user_id' => $request->user()?->id, 'token_id' => $token->id]);
        }

        return back()->with('success', 'টোকেন বন্ধ করা হয়েছে। এই টোকেন দিয়ে আর কোনো রিকোয়েস্ট চলবে না।');
    }

    public function updateLead(Request $request, BotLead $lead)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(BotLead::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $lead->update($data);

        return back()->with('success', 'লিড আপডেট হয়েছে।');
    }
}
