'use client';

import { FormEvent, Suspense, useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Alert, Button, Card, Input, TurnstileWidget, turnstileSiteKey } from '@sapphital/scp-ui';
import {
  exchangeMerchantHandoff,
  fetchMe,
  merchantLogin,
  merchantMfaConfirm,
  merchantMfaSetup,
  merchantMfaVerify,
  storeAuth,
} from '@/lib/api';

const TURNSTILE_SITE_KEY = turnstileSiteKey();

type Step = 'credentials' | 'mfa_challenge' | 'mfa_enroll' | 'mfa_confirm';

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const handoffToken = searchParams.get('handoff');
  const nextPath = searchParams.get('next') ?? '/products';
  const prefilledEmail = searchParams.get('email') ?? '';

  const [step, setStep] = useState<Step>('credentials');
  const [email, setEmail] = useState(prefilledEmail);
  const [password, setPassword] = useState('');
  const [pendingToken, setPendingToken] = useState<string | null>(null);
  const [mfaSecret, setMfaSecret] = useState('');
  const [mfaCode, setMfaCode] = useState('');
  const [backupCodes, setBackupCodes] = useState<string[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [handoffLoading, setHandoffLoading] = useState(Boolean(handoffToken));
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);

  async function finishWithToken(token: string, tenantId?: string) {
    let resolvedTenantId = tenantId;

    if (!resolvedTenantId) {
      const me = await fetchMe(token);
      resolvedTenantId = me.tenant_id;
    }

    storeAuth(token, resolvedTenantId);
    router.push(nextPath.startsWith('/') ? nextPath : '/products');
  }

  useEffect(() => {
    if (!handoffToken) {
      return;
    }

    let cancelled = false;

    exchangeMerchantHandoff(handoffToken)
      .then(async (result) => {
        if (cancelled) {
          return;
        }

        if (result.mfa_enrollment_required) {
          const setup = await merchantMfaSetup(result.token);
          setPendingToken(result.token);
          setMfaSecret(setup.data.secret);
          setHandoffLoading(false);
          setStep('mfa_enroll');
          return;
        }

        if (result.mfa_required) {
          setPendingToken(result.token);
          setHandoffLoading(false);
          setStep('mfa_challenge');
          return;
        }

        storeAuth(result.token, result.tenant_id);
        const destination = nextPath.startsWith('/') ? nextPath : '/products';
        const welcome = searchParams.get('welcome');
        const query = welcome === '1' ? '?welcome=1' : '';
        const emailQuery = prefilledEmail
          ? `${query ? '&' : '?'}email=${encodeURIComponent(prefilledEmail)}`
          : '';
        router.replace(`${destination}${query}${emailQuery}`);
      })
      .catch((err) => {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Automatic sign-in failed.');
          setHandoffLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [handoffToken, nextPath, prefilledEmail, router, searchParams]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (TURNSTILE_SITE_KEY && !turnstileToken) {
      setError('Complete the security check before continuing.');
      return;
    }

    setSubmitting(true);

    try {
      const login = await merchantLogin(email, password, turnstileToken ?? undefined);

      if (login.mfa_enrollment_required) {
        const setup = await merchantMfaSetup(login.token);
        setPendingToken(login.token);
        setMfaSecret(setup.data.secret);
        setStep('mfa_enroll');
        return;
      }

      if (login.mfa_required) {
        setPendingToken(login.token);
        setStep('mfa_challenge');
        return;
      }

      await finishWithToken(login.token, login.tenant_id);
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
      const result = await merchantMfaVerify(pendingToken, mfaCode.trim());
      await finishWithToken(result.token, result.tenant_id);
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
      const result = await merchantMfaConfirm(pendingToken, mfaSecret, mfaCode.trim());
      setBackupCodes(result.backup_codes);
      storeAuth(result.token, result.tenant_id);
      setStep('mfa_confirm');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'MFA enrollment failed.');
    } finally {
      setSubmitting(false);
    }
  }

  if (handoffLoading) {
    return (
      <Card title="Merchant Admin">
        <p style={{ marginTop: 0, color: 'var(--color-text-secondary)' }}>
          Signing you in after signup…
        </p>
      </Card>
    );
  }

  return (
    <Card title="Merchant Admin">
      <p style={{ marginTop: 0, color: 'var(--color-text-secondary)' }}>
        Sign in to manage your catalog, orders, and shipments.
      </p>

      {step === 'credentials' && (
        <form onSubmit={handleSubmit}>
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
            MFA is required for store owners. Add this secret to your authenticator app, then enter
            the current code.
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
          <Button
            type="button"
            style={{ width: '100%' }}
            onClick={() => router.push(nextPath.startsWith('/') ? nextPath : '/products')}
          >
            Continue
          </Button>
        </div>
      )}
    </Card>
  );
}

export default function LoginPage() {
  return (
    <main style={{ maxWidth: 420, margin: '4rem auto', padding: '0 1.5rem' }}>
      <Suspense fallback={<p>Loading…</p>}>
        <LoginForm />
      </Suspense>
    </main>
  );
}
