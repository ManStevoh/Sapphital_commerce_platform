'use client';

import { FormEvent, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Alert, Button, Card, Input, TurnstileWidget, turnstileSiteKey } from '@sapphital/scp-ui';
import {
  platformLogin,
  platformMfaConfirm,
  platformMfaSetup,
  platformMfaVerify,
  storeToken,
} from '@/lib/api';

const TURNSTILE_SITE_KEY = turnstileSiteKey();

type Step = 'credentials' | 'mfa_challenge' | 'mfa_enroll' | 'mfa_confirm';

export default function LoginPage() {
  const router = useRouter();
  const [step, setStep] = useState<Step>('credentials');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [pendingToken, setPendingToken] = useState<string | null>(null);
  const [mfaSecret, setMfaSecret] = useState('');
  const [mfaCode, setMfaCode] = useState('');
  const [backupCodes, setBackupCodes] = useState<string[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);

  async function handleCredentialsSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (TURNSTILE_SITE_KEY && !turnstileToken) {
      setError('Complete the security check before continuing.');
      return;
    }

    setSubmitting(true);

    try {
      const result = await platformLogin(email, password, turnstileToken ?? undefined);

      if (result.mfa_enrollment_required) {
        const setup = await platformMfaSetup(result.token);
        setPendingToken(result.token);
        setMfaSecret(setup.data.secret);
        setStep('mfa_enroll');
        return;
      }

      if (result.mfa_required) {
        setPendingToken(result.token);
        setStep('mfa_challenge');
        return;
      }

      storeToken(result.token);
      router.push('/tenants');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login failed.');
    } finally {
      setSubmitting(false);
    }
  }

  async function handleMfaChallengeSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (!pendingToken) {
      return;
    }

    setSubmitting(true);

    try {
      const result = await platformMfaVerify(pendingToken, mfaCode.trim());
      storeToken(result.token);
      router.push('/tenants');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'MFA verification failed.');
    } finally {
      setSubmitting(false);
    }
  }

  async function handleMfaEnrollSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (!pendingToken || !mfaSecret) {
      return;
    }

    setSubmitting(true);

    try {
      const result = await platformMfaConfirm(pendingToken, mfaSecret, mfaCode.trim());
      setBackupCodes(result.backup_codes);
      storeToken(result.token);
      setStep('mfa_confirm');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'MFA enrollment failed.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main style={{ maxWidth: 420, margin: '4rem auto', padding: '0 1.5rem' }}>
      <Card title="Platform Operations">
        <p style={{ marginTop: 0, color: 'var(--color-text-secondary)' }}>
          Sign in to manage tenants and platform health.
        </p>

        {step === 'credentials' && (
          <form onSubmit={handleCredentialsSubmit}>
            <Input
              label="Email"
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              autoComplete="email"
            />
            <Input
              label="Password"
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              autoComplete="current-password"
            />

            {TURNSTILE_SITE_KEY && (
              <div style={{ marginBottom: 16 }}>
                <TurnstileWidget siteKey={TURNSTILE_SITE_KEY} onToken={setTurnstileToken} />
              </div>
            )}

            {error && <Alert>{error}</Alert>}

            <Button type="submit" disabled={submitting} style={{ width: '100%' }}>
              {submitting ? 'Signing in…' : 'Sign in'}
            </Button>
          </form>
        )}

        {step === 'mfa_challenge' && (
          <form onSubmit={handleMfaChallengeSubmit}>
            <p>Enter the 6-digit code from your authenticator app.</p>
            <Input
              label="Authenticator code"
              type="text"
              inputMode="numeric"
              required
              value={mfaCode}
              onChange={(e) => setMfaCode(e.target.value)}
              autoComplete="one-time-code"
            />
            {error && <Alert>{error}</Alert>}
            <Button type="submit" disabled={submitting} style={{ width: '100%' }}>
              {submitting ? 'Verifying…' : 'Verify'}
            </Button>
          </form>
        )}

        {step === 'mfa_enroll' && (
          <form onSubmit={handleMfaEnrollSubmit}>
            <Alert variant="success">
              MFA is required. Add this secret to your authenticator app, then enter the current
              code.
            </Alert>
            <p style={{ wordBreak: 'break-all', fontFamily: 'monospace' }}>{mfaSecret}</p>
            <Input
              label="Authenticator code"
              type="text"
              inputMode="numeric"
              required
              value={mfaCode}
              onChange={(e) => setMfaCode(e.target.value)}
              autoComplete="one-time-code"
            />
            {error && <Alert>{error}</Alert>}
            <Button type="submit" disabled={submitting} style={{ width: '100%' }}>
              {submitting ? 'Enrolling…' : 'Complete enrollment'}
            </Button>
          </form>
        )}

        {step === 'mfa_confirm' && (
          <div>
            <Alert variant="success">MFA enrolled. Save these backup codes in a secure place.</Alert>
            <ul>
              {backupCodes.map((code) => (
                <li key={code} style={{ fontFamily: 'monospace' }}>
                  {code}
                </li>
              ))}
            </ul>
            <Button type="button" style={{ width: '100%' }} onClick={() => router.push('/tenants')}>
              Continue to tenants
            </Button>
          </div>
        )}
      </Card>
    </main>
  );
}
