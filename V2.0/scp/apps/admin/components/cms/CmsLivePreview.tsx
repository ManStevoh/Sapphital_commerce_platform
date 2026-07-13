'use client';

import { Button, Card } from '@sapphital/scp-ui';
import { useState } from 'react';
import { sectionTypeLabel, type CmsBodyJson, type CmsSection } from '@/lib/cms-sections';

type PreviewMode = 'desktop' | 'mobile';

interface CmsLivePreviewProps {
  title: string;
  body: CmsBodyJson;
}

function renderSection(section: CmsSection, index: number) {
  switch (section.type) {
    case 'rich-text':
      return (
        <div key={index} style={{ whiteSpace: 'pre-wrap', lineHeight: 1.7 }}>
          {section.content || <em style={{ color: '#6b7280' }}>Empty rich text</em>}
        </div>
      );
    case 'image-banner':
      return (
        <section key={index} style={{ background: '#f3f4f6', borderRadius: 8, overflow: 'hidden' }}>
          {section.image_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={section.image_url}
              alt={section.heading || 'Banner'}
              style={{ width: '100%', maxHeight: 180, objectFit: 'cover' }}
            />
          ) : null}
          <div style={{ padding: '0.75rem 1rem' }}>
            <strong>{section.heading || 'Image banner'}</strong>
            {section.subheading ? <p style={{ margin: '0.35rem 0 0' }}>{section.subheading}</p> : null}
          </div>
        </section>
      );
    case 'faq-accordion':
      return (
        <section key={index}>
          {section.heading ? <h3 style={{ marginTop: 0 }}>{section.heading}</h3> : null}
          {(section.items ?? []).map((item, itemIndex) => (
            <details key={itemIndex} style={{ marginBottom: 8 }}>
              <summary>{item.question || `Question ${itemIndex + 1}`}</summary>
              <p style={{ margin: '0.5rem 0 0' }}>{item.answer}</p>
            </details>
          ))}
        </section>
      );
    case 'testimonials':
      return (
        <section key={index}>
          {section.heading ? <h3 style={{ marginTop: 0 }}>{section.heading}</h3> : null}
          {(section.items ?? []).map((item, itemIndex) => (
            <blockquote
              key={itemIndex}
              style={{
                margin: '0 0 0.75rem',
                paddingLeft: 12,
                borderLeft: '3px solid #2563eb',
              }}
            >
              <p style={{ margin: 0, fontStyle: 'italic' }}>&ldquo;{item.quote}&rdquo;</p>
              <footer style={{ marginTop: 6, fontSize: '0.875rem' }}>— {item.author}</footer>
            </blockquote>
          ))}
        </section>
      );
    case 'video-embed':
      return (
        <section key={index}>
          {section.heading ? <h3 style={{ marginTop: 0 }}>{section.heading}</h3> : null}
          <p style={{ fontSize: '0.875rem', color: '#6b7280', wordBreak: 'break-all' }}>
            {section.embed_url || 'No embed URL'}
          </p>
        </section>
      );
    default:
      return (
        <p key={index} style={{ color: '#6b7280' }}>
          Unsupported section: {sectionTypeLabel((section as CmsSection).type)}
        </p>
      );
  }
}

export function CmsLivePreview({ title, body }: CmsLivePreviewProps) {
  const [mode, setMode] = useState<PreviewMode>('desktop');

  return (
    <Card title="Live preview">
      <div style={{ display: 'flex', gap: '0.5rem', marginBottom: 12 }}>
        <Button
          type="button"
          variant={mode === 'desktop' ? 'primary' : 'secondary'}
          onClick={() => setMode('desktop')}
        >
          Desktop
        </Button>
        <Button
          type="button"
          variant={mode === 'mobile' ? 'primary' : 'secondary'}
          onClick={() => setMode('mobile')}
        >
          Mobile
        </Button>
      </div>

      <div
        style={{
          margin: '0 auto',
          width: '100%',
          maxWidth: mode === 'mobile' ? 390 : 720,
          border: '1px solid #e5e7eb',
          borderRadius: 12,
          padding: '1rem',
          background: '#fff',
          minHeight: 240,
          transition: 'max-width 160ms ease',
        }}
      >
        <h2 style={{ marginTop: 0, fontSize: mode === 'mobile' ? '1.25rem' : '1.5rem' }}>
          {title || 'Untitled'}
        </h2>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
          {(body.sections ?? []).map((section, index) => renderSection(section, index))}
        </div>
      </div>
    </Card>
  );
}
