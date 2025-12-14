<?php

declare(strict_types=1);

namespace NeoCore\Auth\TwoFactor;

/**
 * Two-Factor Authentication Provider
 * 
 * Generates and validates 2FA codes using TOTP
 */
class TwoFactorAuthProvider
{
    protected int $window = 1; // Time window for code validation
    protected int $period = 30; // Time period in seconds

    /**
     * Generate a secret key for 2FA
     */
    public function generateSecretKey(): string
    {
        $bytes = random_bytes(20);
        return $this->base32Encode($bytes);
    }

    /**
     * Get the current TOTP code for a secret
     */
    public function getCurrentCode(string $secret): string
    {
        $timestamp = floor(time() / $this->period);
        return $this->generateCode($secret, $timestamp);
    }

    /**
     * Verify a TOTP code
     */
    public function verify(string $secret, string $code): bool
    {
        $timestamp = floor(time() / $this->period);

        // Check current and adjacent time windows
        for ($i = -$this->window; $i <= $this->window; $i++) {
            $validCode = $this->generateCode($secret, $timestamp + $i);
            if (hash_equals($validCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a provisioning URI for QR code
     */
    public function getQrCodeUrl(string $companyName, string $accountName, string $secret): string
    {
        $encodedCompanyName = rawurlencode($companyName);
        $encodedAccountName = rawurlencode($accountName);

        return "otpauth://totp/{$encodedCompanyName}:{$encodedAccountName}?secret={$secret}&issuer={$encodedCompanyName}";
    }

    /**
     * Generate a TOTP code for a given timestamp
     */
    protected function generateCode(string $secret, int $timestamp): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timestamp);

        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;

        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 encode
     */
    protected function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v <<= 8;
            $v += ord($data[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 31];
            }
        }

        if ($vbits > 0) {
            $v <<= (5 - $vbits);
            $output .= $alphabet[$v & 31];
        }

        return $output;
    }

    /**
     * Base32 decode
     */
    protected function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v <<= 5;
            $v += stripos($alphabet, $data[$i]);
            $vbits += 5;

            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }

        return $output;
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes(4)); // 8 character codes
        }
        return $codes;
    }
}
