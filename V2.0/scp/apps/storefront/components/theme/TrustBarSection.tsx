export function TrustBarSection() {
  const items = ['Secure Paystack payments', 'NGN pricing', 'Nationwide shipping'];

  return (
    <section
      aria-label="Trust indicators"
      style={{
        display: 'flex',
        flexWrap: 'wrap',
        gap: '1rem',
        marginTop: '2.5rem',
        padding: '1rem 0',
        borderTop: '1px solid var(--color-border)',
        fontSize: '0.875rem',
        color: 'var(--color-text-secondary)',
      }}
    >
      {items.map((item) => (
        <span key={item}>✓ {item}</span>
      ))}
    </section>
  );
}
