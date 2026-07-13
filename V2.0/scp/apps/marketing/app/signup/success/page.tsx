'use client';

import { Suspense, useEffect, useState } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { Button, Card } from '@sapphital/scp-ui';
import { pollProvisioningStatus, type ProvisioningStatus } from '@/lib/api';

const ADMIN_URL = process.env.NEXT_PUBLIC_ADMIN_URL ?? 'http://localhost:3001';

function SuccessContent() {
  const searchParams = useSearchParams();
  const tenantId = searchParams.get('tenant_id');
  const handoff = searchParams.get('handoff');
  const email = searchParams.get('email');
  const [status, setStatus] = useState<ProvisioningStatus | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!tenantId) {
      setError('Missing tenant ID.');
      return;
    }

    let cancelled = false;

    pollProvisioningStatus(tenantId)
      .then((result) => {
        if (!cancelled) {
          setStatus(result);
        }
      })
      .catch((err) => {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Provisioning failed.');
        }
      });

    return () => {
      cancelled = true;
    };
  }, [tenantId]);

  const billingUrl = (() => {
    const params = new URLSearchParams({ next: '/billing', welcome: '1' });
    if (handoff) {
      params.set('handoff', handoff);
    }
    if (email) {
      params.set('email', email);
    }
    return `${ADMIN_URL}/login?${params.toString()}`;
  })();

  if (!tenantId) {
    return <p style={{ color: 'crimson' }}>Invalid signup session.</p>;
  }

  if (error) {
    return <p style={{ color: 'crimson' }}>{error}</p>;
  }

  if (status === null) {
    return (
      <div>
        <p>Setting up your store…</p>
        <p>This usually takes a few seconds.</p>
      </div>
    );
  }

  if (status.status === 'failed') {
    return (
      <div>
        <p style={{ color: 'crimson' }}>
          Provisioning failed: {status.error ?? 'Unknown error'}
        </p>
        <p>
          <Link href="/signup">Try again</Link>
        </p>
      </div>
    );
  }

  return (
    <div>
      <Card>
        <h2 style={{ marginTop: 0 }}>Your store is ready!</h2>
        <p>
          You are on a <strong>14-day free trial</strong>. Activate billing now to keep your store
          live after the trial, or explore the admin first.
        </p>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', marginTop: '1rem' }}>
          <Button type="button" onClick={() => { window.location.href = billingUrl; }}>
            Activate plan &amp; pay →
          </Button>
          <a href={`${ADMIN_URL}/login${handoff ? `?handoff=${encodeURIComponent(handoff)}&next=/products` : ''}`}>
            Go to merchant admin
          </a>
        </div>
      </Card>
      <p style={{ marginTop: '1rem' }}>
        <Link href="/">Back to home</Link>
      </p>
    </div>
  );
}

export default function SignupSuccessPage() {
  return (
    <main style={{ maxWidth: 480, margin: '2rem auto', padding: '0 1rem' }}>
      <h1>Account created</h1>
      <Suspense fallback={<p>Loading…</p>}>
        <SuccessContent />
      </Suspense>
    </main>
  );
}
