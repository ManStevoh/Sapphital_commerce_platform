import Link from 'next/link';

interface CmsSection {
  type: string;
  content?: string;
  heading?: string;
  subheading?: string;
  image_url?: string;
  cta_label?: string;
  cta_href?: string;
  embed_url?: string;
  items?: Array<{
    question?: string;
    answer?: string;
    quote?: string;
    author?: string;
    role?: string;
  }>;
}

interface CmsSectionRendererProps {
  sections: CmsSection[];
}

function videoEmbedSrc(url: string): string {
  if (url.includes('youtube.com/watch')) {
    const videoId = new URL(url).searchParams.get('v');
    if (videoId) {
      return `https://www.youtube.com/embed/${videoId}`;
    }
  }

  if (url.includes('youtu.be/')) {
    const videoId = url.split('youtu.be/')[1]?.split(/[?#]/)[0];
    if (videoId) {
      return `https://www.youtube.com/embed/${videoId}`;
    }
  }

  return url;
}

export function CmsSectionRenderer({ sections }: CmsSectionRendererProps) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: '2rem' }}>
      {sections.map((section, index) => {
        switch (section.type) {
          case 'rich-text':
            return (
              <div
                key={`section-${index}`}
                style={{ lineHeight: 1.7, whiteSpace: 'pre-wrap' }}
              >
                {section.content}
              </div>
            );

          case 'image-banner':
            return (
              <section
                key={`section-${index}`}
                style={{
                  borderRadius: 12,
                  overflow: 'hidden',
                  background: 'var(--color-surface-muted, #f3f4f6)',
                }}
              >
                {section.image_url && (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={section.image_url}
                    alt={section.heading ?? 'Banner'}
                    style={{ width: '100%', maxHeight: 320, objectFit: 'cover' }}
                  />
                )}
                <div style={{ padding: '1.25rem' }}>
                  {section.heading && <h2 style={{ margin: 0 }}>{section.heading}</h2>}
                  {section.subheading && (
                    <p style={{ color: 'var(--color-text-secondary)', marginTop: '0.5rem' }}>
                      {section.subheading}
                    </p>
                  )}
                  {section.cta_label && section.cta_href && (
                    <p style={{ marginTop: '1rem' }}>
                      <Link href={section.cta_href}>{section.cta_label}</Link>
                    </p>
                  )}
                </div>
              </section>
            );

          case 'faq-accordion':
            return (
              <section key={`section-${index}`}>
                {section.heading && <h2>{section.heading}</h2>}
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                  {(section.items ?? []).map((item, itemIndex) => (
                    <details
                      key={`faq-${index}-${itemIndex}`}
                      style={{
                        border: '1px solid var(--color-border, #e5e7eb)',
                        borderRadius: 8,
                        padding: '0.75rem 1rem',
                      }}
                    >
                      <summary style={{ cursor: 'pointer', fontWeight: 600 }}>
                        {item.question}
                      </summary>
                      <p style={{ marginTop: '0.75rem', lineHeight: 1.6 }}>{item.answer}</p>
                    </details>
                  ))}
                </div>
              </section>
            );

          case 'testimonials':
            return (
              <section key={`section-${index}`}>
                {section.heading && <h2>{section.heading}</h2>}
                <div
                  style={{
                    display: 'grid',
                    gap: '1rem',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
                  }}
                >
                  {(section.items ?? []).map((item, itemIndex) => (
                    <blockquote
                      key={`testimonial-${index}-${itemIndex}`}
                      style={{
                        margin: 0,
                        padding: '1rem',
                        borderLeft: '4px solid var(--color-primary, #2563eb)',
                        background: 'var(--color-surface-muted, #f9fafb)',
                      }}
                    >
                      <p style={{ margin: 0, fontStyle: 'italic' }}>&ldquo;{item.quote}&rdquo;</p>
                      <footer style={{ marginTop: '0.75rem', fontSize: '0.875rem' }}>
                        <strong>{item.author}</strong>
                        {item.role ? ` · ${item.role}` : ''}
                      </footer>
                    </blockquote>
                  ))}
                </div>
              </section>
            );

          case 'video-embed':
            return (
              <section key={`section-${index}`}>
                {section.heading && <h2>{section.heading}</h2>}
                {section.embed_url && (
                  <div
                    style={{
                      position: 'relative',
                      paddingBottom: '56.25%',
                      height: 0,
                      overflow: 'hidden',
                      borderRadius: 8,
                    }}
                  >
                    <iframe
                      title={section.heading ?? 'Embedded video'}
                      src={videoEmbedSrc(section.embed_url)}
                      style={{
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        width: '100%',
                        height: '100%',
                        border: 0,
                      }}
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                      allowFullScreen
                    />
                  </div>
                )}
              </section>
            );

          default:
            return null;
        }
      })}
    </div>
  );
}
