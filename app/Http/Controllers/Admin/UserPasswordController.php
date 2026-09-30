<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordChangedMail;
use App\Models\Patients;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class UserPasswordController extends Controller
{
    public function resetDoctor(Request $request, int $id): JsonResponse
    {
        return $this->resetPassword(
            $request,
            User::findOrFail($id),
            'doctor',
            fn ($user) => trim($user->name.' '.($user->surname ?? ''))
        );
    }

    public function resetPatient(Request $request, int $id): JsonResponse
    {
        return $this->resetPassword(
            $request,
            Patients::findOrFail($id),
            'patient',
            fn ($user) => trim($user->name.' '.($user->lastname ?? ''))
        );
    }

    private function resetPassword(Request $request, $user, string $accountType, callable $displayName): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $plainPassword = $validated['password'];

        $user->password = Hash::make($plainPassword);
        $user->save();

        $emailSent = $this->notifyPasswordChanged($user, $accountType, $displayName($user), $plainPassword);

        $message = $emailSent
            ? 'Password updated successfully. A notification email was sent to the user.'
            : 'Password updated successfully.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'email_sent' => $emailSent,
        ]);
    }

    private function notifyPasswordChanged($user, string $accountType, string $recipientName, string $newPassword): bool
    {
        $email = trim((string) ($user->email ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $name = $recipientName !== '' ? $recipientName : 'there';

        try {
            Mail::to($email)->send(new AdminPasswordChangedMail($name, $accountType, $newPassword));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }
}
