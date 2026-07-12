import Link from 'next/link';

export default async function StoreNotFoundPage({
  searchParams,
}: {
  searchParams: Promise<{ slug?: string }>;
}) {
  const params = await searchParams;
  const slug = params.slug;

  return (
    <main
      style={{
        maxWidth: 520,
        margin: '4rem auto',
        padding: '0 1.5rem',
        textAlign: 'center',
      }}
    >
      <h1 style={{ marginBottom: '0.5rem' }}>Store not found</h1>
      <p style={{ color: 'var(--color-text-secondary)' }}>
        {slug
          ? `We could not find a store at ${slug}.shops.sapphital.test.`
          : 'This store does not exist or may have been removed.'}
      </p>
      <p style={{ marginTop: '1.5rem' }}>
        <Link href="https://sapphital.africa">Visit SAPPHITAL</Link>
      </p>
    </main>
  );
}
