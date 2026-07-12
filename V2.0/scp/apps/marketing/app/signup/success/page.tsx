'use client';

import { Suspense, useEffect, useState } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { pollProvisioningStatus, type ProvisioningStatus } from '@/lib/api';

const ADMIN_URL = process.env.NEXT_PUBLIC_ADMIN_URL ?? 'http://localhost:3001';

function SuccessContent() {
  const searchParams = useSearchParams();
  const tenantId = searchParams.get('tenant_id');
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
      <h2>Your store is ready!</h2>
      <p>Provisioning completed successfully.</p>
      <p>
        <a href={ADMIN_URL}>Go to merchant admin →</a>
      </p>
      <p>
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
