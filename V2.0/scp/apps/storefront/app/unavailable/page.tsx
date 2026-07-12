export default async function UnavailablePage({
  searchParams,
}: {
  searchParams: Promise<{ store?: string }>;
}) {
  const params = await searchParams;
  const storeName = params.store ?? 'This store';

  return (
    <main
      style={{
        maxWidth: 520,
        margin: '4rem auto',
        padding: '0 1.5rem',
        textAlign: 'center',
      }}
    >
      <h1 style={{ marginBottom: '0.5rem' }}>Store temporarily unavailable</h1>
      <p style={{ color: 'var(--color-text-secondary)' }}>
        {storeName} is not accepting orders right now. Please check back soon or contact the
        merchant directly.
      </p>
    </main>
  );
}
