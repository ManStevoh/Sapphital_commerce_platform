'use client';

import { FormEvent, useState, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { Alert, Button, Card, Input } from '@sapphital/scp-ui';
import { signup, type PlanSlug } from '@/lib/api';

const PLANS: { slug: PlanSlug; label: string }[] = [
  { slug: 'starter', label: 'Starter' },
  { slug: 'growth', label: 'Growth' },
  { slug: 'pro', label: 'Pro' },
];

function SignupForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const initialPlan = searchParams.get('plan');
  const validInitialPlan = PLANS.some((p) => p.slug === initialPlan)
    ? (initialPlan as PlanSlug)
    : 'starter';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [storeName, setStoreName] = useState('');
  const [planSlug, setPlanSlug] = useState<PlanSlug>(validInitialPlan);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    try {
      const result = await signup({
        email,
        password,
        store_name: storeName,
        plan_slug: planSlug,
      });

      router.push(`/signup/success?tenant_id=${encodeURIComponent(result.tenant_id)}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Signup failed.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Card>
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
          minLength={8}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          hint="Minimum 8 characters"
          autoComplete="new-password"
        />
        <Input
          label="Store name"
          type="text"
          required
          value={storeName}
          onChange={(e) => setStoreName(e.target.value)}
        />
        <fieldset style={{ border: 'none', padding: 0, margin: '0 0 16px' }}>
          <legend style={{ fontWeight: 500, marginBottom: 8 }}>Plan</legend>
          {PLANS.map((plan) => (
            <label key={plan.slug} style={{ display: 'block', marginBottom: 4 }}>
              <input
                type="radio"
                name="plan"
                value={plan.slug}
                checked={planSlug === plan.slug}
                onChange={() => setPlanSlug(plan.slug)}
              />{' '}
              {plan.label}
            </label>
          ))}
        </fieldset>

        {error && <Alert>{error}</Alert>}

        <Button type="submit" disabled={submitting} style={{ width: '100%' }}>
          {submitting ? 'Creating account…' : 'Create account'}
        </Button>
      </form>
    </Card>
  );
}

export default function SignupPage() {
  return (
    <main style={{ maxWidth: 480, margin: '3rem auto', padding: '0 1.5rem' }}>
      <h1 style={{ marginTop: 0 }}>Start your store</h1>
      <p style={{ color: 'var(--color-text-secondary)' }}>
        Create your SAPPHITAL merchant account in minutes.
      </p>

      <Suspense fallback={<p>Loading…</p>}>
        <SignupForm />
      </Suspense>

      <p style={{ marginTop: '1.5rem' }}>
        <Link href="/">← Back to home</Link>
      </p>
    </main>
  );
}
