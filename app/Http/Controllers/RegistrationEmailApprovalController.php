<?php

namespace App\Http\Controllers;

use App\Services\RegistrationEmailApproval;
use App\Models\DeploymentManager;
use Illuminate\Http\Request;

class RegistrationEmailApprovalController extends Controller
{
    public function send(Request $request, RegistrationEmailApproval $approval)
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
            'purpose' => 'required|in:self,assisted',
        ]);
        if ($data['purpose'] === 'self') {
            abort_if($request->user(), 403);
        } else {
            $user = $request->user();
            abort_unless($user && (
                $user->isSuperAdmin()
                || $user->hasRole('agent')
                || $user->hasRole('state_manager')
                || DeploymentManager::where('user_id', $user->id)->whereRaw('LOWER(status) = ?', ['active'])->exists()
            ), 403);
        }

        $approval->send($request, $data['email'], $data['purpose']);
        return response()->json(['message' => 'The code was sent. The owner must open the email and enter the code on their own device.']);
    }

    public function status(Request $request, RegistrationEmailApproval $approval)
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
            'purpose' => 'required|in:self,assisted',
        ]);
        return response()->json(['approved' => $approval->isApproved($request, $data['email'], $data['purpose'])])
            ->header('Cache-Control', 'no-store');
    }

    public function show(string $token)
    {
        return response()->view('Pages.Authentication.registration-email-approval', ['token' => $token])
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function verify(Request $request, string $token, RegistrationEmailApproval $approval)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $status = $approval->verify($token, $data['code']);
        if ($status === 'approved') {
            return response()->view('Pages.Authentication.registration-email-approved')
                ->header('Cache-Control', 'no-store')
                ->header('Referrer-Policy', 'no-referrer');
        }
        $message = match ($status) {
            'locked' => 'Too many incorrect attempts. Ask for a new code after one minute.',
            'invalid' => 'The code is incorrect. Please try again.',
            default => 'This code has expired or is no longer valid. Ask for a new code.',
        };
        return redirect()->route('registration-verification.show', $token)->withErrors(['code' => $message]);
    }
}
