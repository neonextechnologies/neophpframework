<?php

declare(strict_types=1);

namespace App\Controllers;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\TwoFactor\TwoFactorAuthProvider;
use Cycle\ORM\EntityManagerInterface;

/**
 * Two-Factor Authentication Controller
 */
class TwoFactorAuthController
{
    protected TwoFactorAuthProvider $twoFactor;
    protected EntityManagerInterface $entityManager;

    public function __construct(
        TwoFactorAuthProvider $twoFactor,
        EntityManagerInterface $entityManager
    ) {
        $this->twoFactor = $twoFactor;
        $this->entityManager = $entityManager;
    }

    /**
     * Enable 2FA for the user
     */
    public function enable(Request $request): Response
    {
        $user = $request->user();

        if ($user->two_factor_secret) {
            return redirect()->back()->with('error', '2FA is already enabled.');
        }

        // Generate secret
        $secret = $this->twoFactor->generateSecretKey();
        $qrCodeUrl = $this->twoFactor->getQrCodeUrl(
            config('app.name', 'NeoCore'),
            $user->email,
            $secret
        );

        // Store secret temporarily in session
        $request->session()->put('2fa_secret_pending', $secret);

        return view('auth/two-factor/enable', [
            'qr_code_url' => $qrCodeUrl,
            'secret' => $secret
        ]);
    }

    /**
     * Confirm 2FA setup
     */
    public function confirm(Request $request): Response
    {
        $user = $request->user();
        $code = $request->input('code');
        $secret = $request->session()->get('2fa_secret_pending');

        if (!$secret) {
            return redirect()->back()->withErrors(['code' => 'No pending 2FA setup found.']);
        }

        if (!$this->twoFactor->verify($secret, $code)) {
            return redirect()->back()->withErrors(['code' => 'Invalid verification code.']);
        }

        // Enable 2FA
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_recovery_codes = encrypt(json_encode(
            $this->twoFactor->generateRecoveryCodes()
        ));

        $this->entityManager->persist($user);
        $this->entityManager->run();

        $request->session()->forget('2fa_secret_pending');

        return redirect('/dashboard')->with('success', '2FA enabled successfully!');
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request): Response
    {
        $user = $request->user();

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;

        $this->entityManager->persist($user);
        $this->entityManager->run();

        return redirect()->back()->with('success', '2FA disabled successfully!');
    }

    /**
     * Show 2FA challenge
     */
    public function challenge(Request $request): Response
    {
        return view('auth/two-factor/challenge');
    }

    /**
     * Verify 2FA code
     */
    public function verify(Request $request): Response
    {
        $user = $request->session()->get('2fa_user');
        $code = $request->input('code');
        $useRecoveryCode = $request->has('recovery_code');

        if (!$user) {
            return redirect('/login')->withErrors(['code' => 'Session expired.']);
        }

        if ($useRecoveryCode) {
            return $this->verifyRecoveryCode($request, $user, $code);
        }

        $secret = decrypt($user->two_factor_secret);

        if (!$this->twoFactor->verify($secret, $code)) {
            return redirect()->back()->withErrors(['code' => 'Invalid verification code.']);
        }

        // Complete login
        auth()->login($user);
        $request->session()->forget('2fa_user');

        return redirect()->intended('/dashboard');
    }

    /**
     * Verify recovery code
     */
    protected function verifyRecoveryCode(Request $request, $user, string $code): Response
    {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (!in_array($code, $recoveryCodes)) {
            return redirect()->back()->withErrors(['code' => 'Invalid recovery code.']);
        }

        // Remove used recovery code
        $recoveryCodes = array_diff($recoveryCodes, [$code]);
        $user->two_factor_recovery_codes = encrypt(json_encode(array_values($recoveryCodes)));

        $this->entityManager->persist($user);
        $this->entityManager->run();

        // Complete login
        auth()->login($user);
        $request->session()->forget('2fa_user');

        return redirect()->intended('/dashboard')
            ->with('warning', 'You used a recovery code. Please regenerate them.');
    }
}
