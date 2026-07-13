'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Alert, Button, Card, Input } from '@sapphital/scp-ui';
import { customerLogin } from '@/lib/api';
import { getCustomerToken, setCustomerSession } from '@/lib/customer-auth';
import { resolveClientTenantId } from '@/lib/tenant-client';

export default function CustomerLoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);

  useEffect(() => {
    if (getCustomerToken()) {
      router.replace('/account');
    }
  }, [router]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setWorking(true);
    setError(null);

    try {
      const tenantId = await resolveClientTenantId();
      const result = await customerLogin(tenantId, email.trim(), password);
      setCustomerSession(result.token, result.data.email);
      router.push('/account');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login failed.');
    } finally {
      setWorking(false);
    }
  }

  return (
    <main style={{ maxWidth: 420, margin: '3rem auto', padding: '0 1rem' }}>
      <p>
        <Link href="/">&larr; Shop</Link>
      </p>
      <Card title="Sign in">
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
          {error && <Alert>{error}</Alert>}
          <Button type="submit" disabled={working}>
            {working ? 'Signing in…' : 'Sign in'}
          </Button>
        </form>
        <p style={{ marginTop: 16 }}>
          New here? <Link href="/account/register">Create an account</Link>
        </p>
      </Card>
    </main>
  );
}
