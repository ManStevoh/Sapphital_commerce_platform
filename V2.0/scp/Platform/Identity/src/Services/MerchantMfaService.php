<?php

declare(strict_types=1);

namespace Platform\Identity\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;

final class MerchantMfaService
{
    /** @var list<string> */
    private const BACKUP_CODE_ALPHABET = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M',
        'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        '2', '3', '4', '5', '6', '7', '8', '9',
    ];

    public function __construct(
        private readonly TotpService $totp,
    ) {}

    public function isEnforced(): bool
    {
        return (bool) config('identity.merchant_mfa_enforced', true);
    }

    public function isRequiredFor(MerchantUser $user): bool
    {
        return $this->isEnforced() && $user->role === MerchantUserRole::Owner;
    }

    public function isEnrolled(MerchantUser $user): bool
    {
        return is_string($user->mfa_secret)
            && $user->mfa_secret !== ''
            && $user->mfa_confirmed_at !== null;
    }

    /**
     * @return array{secret: string, otpauth_uri: string}
     */
    public function beginEnrollment(MerchantUser $user): array
    {
        $secret = $this->totp->generateSecret();

        return [
            'secret' => $secret,
            'otpauth_uri' => $this->totp->provisioningUri(
                $user->email,
                $secret,
                (string) config('identity.merchant_mfa_issuer', 'SAPPHITAL Merchant'),
            ),
        ];
    }

    /**
     * @return array{backup_codes: list<string>, token: string, token_type: string, tenant_id: string}
     */
    public function confirmEnrollment(
        MerchantUser $user,
        string $secret,
        string $code,
        ?PersonalAccessToken $setupToken,
    ): array {
        if (! $this->totp->verify($secret, $code)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authenticator code.'],
            ]);
        }

        $backupCodes = $this->generateBackupCodes();
        $hashedCodes = array_map(
            static fn (string $plain): string => Hash::make($plain),
            $backupCodes,
        );

        $user->forceFill([
            'mfa_secret' => $secret,
            'mfa_confirmed_at' => now(),
            'mfa_backup_codes' => $hashedCodes,
        ])->save();

        if ($setupToken !== null) {
            $setupToken->delete();
        }

        $accessToken = $this->issueFullAccessToken($user);

        return [
            'backup_codes' => $backupCodes,
            'token' => $accessToken->plainTextToken,
            'token_type' => 'Bearer',
            'tenant_id' => $user->tenant_id,
        ];
    }

    /**
     * @return array{token: string, token_type: string, tenant_id: string}
     */
    public function verifyChallenge(
        MerchantUser $user,
        string $code,
        PersonalAccessToken $challengeToken,
    ): array {
        $normalized = preg_replace('/\s+/', '', $code) ?? '';

        $verified = is_string($user->mfa_secret)
            && $user->mfa_secret !== ''
            && $this->totp->verify($user->mfa_secret, $normalized);

        if (! $verified) {
            $verified = $this->consumeBackupCode($user, $normalized);
        }

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authenticator or backup code.'],
            ]);
        }

        $challengeToken->delete();

        $accessToken = $this->issueFullAccessToken($user);

        return [
            'token' => $accessToken->plainTextToken,
            'token_type' => 'Bearer',
            'tenant_id' => $user->tenant_id,
        ];
    }

    public function issueSetupToken(MerchantUser $user): NewAccessToken
    {
        return $user->createToken('merchant-mfa-setup', ['mfa:setup']);
    }

    public function issueChallengeToken(MerchantUser $user): NewAccessToken
    {
        return $user->createToken('merchant-mfa-challenge', ['mfa:challenge']);
    }

    public function issueFullAccessToken(MerchantUser $user, string $name = 'merchant-api'): NewAccessToken
    {
        return $user->createToken($name, ['merchant:access']);
    }

    /**
     * @return list<string>
     */
    private function generateBackupCodes(int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->randomBackupCode();
        }

        return $codes;
    }

    private function randomBackupCode(): string
    {
        $segments = [];

        for ($segment = 0; $segment < 2; $segment++) {
            $part = '';

            for ($i = 0; $i < 4; $i++) {
                $part .= self::BACKUP_CODE_ALPHABET[random_int(0, count(self::BACKUP_CODE_ALPHABET) - 1)];
            }

            $segments[] = $part;
        }

        return implode('-', $segments);
    }

    private function consumeBackupCode(MerchantUser $user, string $code): bool
    {
        $normalized = strtoupper(str_replace(' ', '', $code));

        if (! preg_match('/^[A-Z0-9]{4}-?[A-Z0-9]{4}$/', $normalized)) {
            return false;
        }

        if (! str_contains($normalized, '-')) {
            $normalized = substr($normalized, 0, 4).'-'.substr($normalized, 4);
        }

        $stored = $user->mfa_backup_codes;

        if (! is_array($stored) || $stored === []) {
            return false;
        }

        $remaining = [];
        $matched = false;

        foreach ($stored as $hashed) {
            if (! is_string($hashed)) {
                continue;
            }

            if (! $matched && Hash::check($normalized, $hashed)) {
                $matched = true;

                continue;
            }

            $remaining[] = $hashed;
        }

        if (! $matched) {
            return false;
        }

        $user->forceFill(['mfa_backup_codes' => $remaining])->save();

        return true;
    }
}
