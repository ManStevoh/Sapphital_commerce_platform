'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Alert, Button, Card, Input } from '@sapphital/scp-ui';
import { customerRegister } from '@/lib/api';
import { getCustomerToken, setCustomerSession } from '@/lib/customer-auth';
import { resolveClientTenantId } from '@/lib/tenant-client';

export default function CustomerRegisterPage() {
  const router = useRouter();
  const [name, setName] = useState('');
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
      const result = await customerRegister(tenantId, {
        email: email.trim(),
        password,
        name: name.trim() || undefined,
      });
      setCustomerSession(result.token, result.data.email);
      router.push('/account');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Registration failed.');
    } finally {
      setWorking(false);
    }
  }

  return (
    <main style={{ maxWidth: 420, margin: '3rem auto', padding: '0 1rem' }}>
      <p>
        <Link href="/">&larr; Shop</Link>
      </p>
      <Card title="Create account">
        <form onSubmit={handleSubmit}>
          <Input label="Name" value={name} onChange={(e) => setName(e.target.value)} />
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
            minLength={8}
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete="new-password"
          />
          {error && <Alert>{error}</Alert>}
          <Button type="submit" disabled={working}>
            {working ? 'Creating…' : 'Create account'}
          </Button>
        </form>
        <p style={{ marginTop: 16 }}>
          Already registered? <Link href="/account/login">Sign in</Link>
        </p>
      </Card>
    </main>
  );
}
