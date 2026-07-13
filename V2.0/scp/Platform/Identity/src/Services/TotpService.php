<?php

declare(strict_types=1);

namespace Platform\Identity\Services;

final class TotpService
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    public function generateSecret(int $length = 16): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    public function provisioningUri(string $email, string $secret, string $issuer): string
    {
        $label = rawurlencode($issuer.':'.$email);
        $issuerParam = rawurlencode($issuer);
        $secretParam = rawurlencode($secret);

        return "otpauth://totp/{$label}?secret={$secretParam}&issuer={$issuerParam}&algorithm=SHA1&digits=6&period=30";
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $normalized = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $normalized)) {
            return false;
        }

        $timeSlice = (int) floor(time() / self::PERIOD);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->codeForSlice($secret, $timeSlice + $offset), $normalized)) {
                return true;
            }
        }

        return false;
    }

    public function currentCode(string $secret): string
    {
        $timeSlice = (int) floor(time() / self::PERIOD);

        return $this->codeForSlice($secret, $timeSlice);
    }

    private function codeForSlice(string $secret, int $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );
        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $buffer = 0;
        $bitsLeft = 0;
        $decoded = '';

        foreach (str_split($secret) as $char) {
            $value = strpos($alphabet, $char);

            if ($value === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $decoded .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $decoded;
    }
}
