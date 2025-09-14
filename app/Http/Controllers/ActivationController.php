<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;


class ActivationController extends Controller
{
    public function sendActivationCode(Request $request)
    {
        $user = $request->user();

        if ($user->is_active) {
            return $this->errorResponse(
                400,
                'Bad Request',
                'Account is already activated.',
            );
        }

        if ($user->last_activation_sent_at && Carbon::parse($user->last_activation_sent_at)->diffInMinutes(now()) < 3) {
            $remain = 3 - Carbon::parse($user->last_activation_sent_at)->diffInMinutes(now());
            return $this->errorResponse(
                429,
                'Too Many Requests',
                "You can only resend the code after {$remain} minutes."
            );
        }

        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->activation_code = $code;
        $user->activation_expires_at = now()->addMinutes(15);
        $user->last_activation_sent_at = now();
        $user->save();

        Mail::to($user->email)->send(new OtpMail($code));

        return $this->successResponse(
            200,
            'Activation code has been sent to your email.',
            null,
        );
    }


    public function verifyActivationCode(Request $request)
    {
        $request->validate([
            'code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/'
            ],
        ], [
            'code.size' => 'The activation code must be exactly 6 characters.',
            'code.regex' => 'The activation code must consist of 6 digits only.',
        ]);

        $user = $request->user();

        if ($user->activation_code !== $request->code) {
            return $this->errorResponse(
                400,
                'Bad Request',
                'Incorrect activation code.'
            );
        }

        if ($user->activation_expires_at && now()->greaterThan($user->activation_expires_at)) {
            return $this->errorResponse(
                400,
                'Bad Request',
                'Activation code has expired.'
            );
        }

        $user->is_active = true;
        $user->activation_code = null;
        $user->activation_expires_at = null;
        $user->save();

        return $this->successResponse(
            200,
            'Activation successful',
            [
                'is_active' => $user->is_active,
            ]
        );
    }
}
