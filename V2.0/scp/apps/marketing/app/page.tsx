import Link from 'next/link';

function formatNgn(kobo: number): string {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 0,
  }).format(kobo / 100);
}

const PLANS = [
  {
    slug: 'starter',
    name: 'Starter',
    priceKobo: 1_500_000,
    description: 'Up to 100 products, 2 staff seats',
  },
  {
    slug: 'growth',
    name: 'Growth',
    priceKobo: 4_500_000,
    description: 'Up to 1,000 products, 10 staff, custom domain',
  },
  {
    slug: 'pro',
    name: 'Pro',
    priceKobo: 12_000_000,
    description: 'Up to 10,000 products, 50 staff, custom domain',
  },
] as const;

export default function HomePage() {
  return (
    <main style={{ maxWidth: 960, margin: '0 auto', padding: '2rem 1rem' }}>
      <header style={{ marginBottom: '3rem', textAlign: 'center' }}>
        <h1>SAPPHITAL</h1>
        <p style={{ fontSize: '1.25rem', marginBottom: '1.5rem' }}>
          Launch and grow your online store in Nigeria — payments, catalog, and
          fulfillment in one platform.
        </p>
        <Link
          href="/signup"
          style={{
            display: 'inline-block',
            padding: '0.75rem 1.5rem',
            background: '#111',
            color: '#fff',
            textDecoration: 'none',
            borderRadius: 4,
          }}
        >
          Start free trial →
        </Link>
      </header>

      <section>
        <h2>Plans (NGN / month)</h2>
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))',
            gap: '1.5rem',
            marginTop: '1rem',
          }}
        >
          {PLANS.map((plan) => (
            <article
              key={plan.slug}
              style={{
                border: '1px solid #ddd',
                borderRadius: 8,
                padding: '1.5rem',
              }}
            >
              <h3>{plan.name}</h3>
              <p style={{ fontSize: '1.5rem', fontWeight: 700 }}>
                {formatNgn(plan.priceKobo)}
                <span style={{ fontSize: '0.875rem', fontWeight: 400 }}>
                  {' '}
                  / mo
                </span>
              </p>
              <p>{plan.description}</p>
              <Link href={`/signup?plan=${plan.slug}`}>Choose {plan.name}</Link>
            </article>
          ))}
        </div>
      </section>
    </main>
  );
}
