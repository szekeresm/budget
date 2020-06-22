<?php

namespace App\Http\Controllers;

use App\Mail\ResetPassword;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller {
    private $userRepository;
    private $passwordResetRepository;

    public function __construct(UserRepository $userRepository, PasswordResetRepository $passwordResetRepository)
    {
        $this->userRepository = $userRepository;
        $this->passwordResetRepository = $passwordResetRepository;
    }

    public function get(Request $request) {
        return view('reset_password', [
            'token' => $request->get('token')
        ]);
    }

    public function post(Request $request) {
        $request->validate($this->userRepository->getValidationRulesForPasswordReset());

        if ($request->input('email') && !$request->has('token')) {
            $email = $request->input('email');

            $existingUser = $this->userRepository->getByEmail($email);

            if ($existingUser) {
                $shippingToken = null;

                $existingRecord = $this->passwordResetRepository->getByEmail($email);

                if (!$existingRecord) {
                    $shippingToken = Str::random(100);

                    $this->passwordResetRepository->create($email, $shippingToken);
                } else {
                    $shippingToken = $existingRecord->token;
                }

                Mail::to($email)->queue(new ResetPassword($shippingToken));
            }

            return redirect()
                ->route('login')
                ->with([
                    'alert_type' => 'success',
                    'alert_message' => 'If you registered with that address, we\'ve sent you an e-mail'
                ]);
        } else if ($request->has('token') && $request->has('password') && !$request->has('email')) {
            $token = $request->input('token');
            $password = $request->input('password');

            $record = $this->passwordResetRepository->getByToken($token);

            if ($record) {
                $user = $this->userRepository->getByEmail($record->email);

                $this->userRepository->update($user->id, ['password' => Hash::make($password)]);
            }

            $this->passwordResetRepository->delete($token);

            return redirect()
                ->route('login')
                ->with([
                    'alert_type' => 'success',
                    'alert_message' => 'You\'ve successfully changed your password'
                ]);
        }

        return redirect()->route('reset_password');
    }
}
