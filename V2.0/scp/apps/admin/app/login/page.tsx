'use client';

import { FormEvent, Suspense, useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Alert, Button, Card, Input, TurnstileWidget, turnstileSiteKey } from '@sapphital/scp-ui';
import { exchangeMerchantHandoff, fetchMe, merchantLogin, storeAuth } from '@/lib/api';

const TURNSTILE_SITE_KEY = turnstileSiteKey();

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const handoffToken = searchParams.get('handoff');
  const nextPath = searchParams.get('next') ?? '/products';
  const prefilledEmail = searchParams.get('email') ?? '';

  const [email, setEmail] = useState(prefilledEmail);
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [handoffLoading, setHandoffLoading] = useState(Boolean(handoffToken));
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);

  useEffect(() => {
    if (!handoffToken) {
      return;
    }

    let cancelled = false;

    exchangeMerchantHandoff(handoffToken)
      .then((result) => {
        if (cancelled) {
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
      const me = await fetchMe(login.token);
      storeAuth(login.token, me.tenant_id);
      router.push(nextPath.startsWith('/') ? nextPath : '/products');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login failed.');
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
