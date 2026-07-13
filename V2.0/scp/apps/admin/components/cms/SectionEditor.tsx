'use client';

import { Button, Card, Input } from '@sapphital/scp-ui';
import { useState, type DragEvent } from 'react';
import {
  CMS_SECTION_TYPES,
  emptySection,
  sectionTypeLabel,
  type CmsBodyJson,
  type CmsSection,
  type CmsSectionType,
} from '@/lib/cms-sections';

interface SectionEditorProps {
  value: CmsBodyJson;
  onChange: (body: CmsBodyJson) => void;
  disabled?: boolean;
}

function updateSection(
  sections: CmsSection[],
  index: number,
  next: CmsSection,
): CmsSection[] {
  return sections.map((section, i) => (i === index ? next : section));
}

export function SectionEditor({ value, onChange, disabled }: SectionEditorProps) {
  const sections = value.sections;
  const [dragIndex, setDragIndex] = useState<number | null>(null);
  const [dropIndex, setDropIndex] = useState<number | null>(null);

  function setSections(next: CmsSection[]) {
    onChange({ sections: next });
  }

  function addSection(type: CmsSectionType) {
    setSections([...sections, emptySection(type)]);
  }

  function moveSection(index: number, direction: -1 | 1) {
    const target = index + direction;
    if (target < 0 || target >= sections.length) {
      return;
    }

    const next = [...sections];
    const [item] = next.splice(index, 1);
    next.splice(target, 0, item);
    setSections(next);
  }

  function removeSection(index: number) {
    setSections(sections.filter((_, i) => i !== index));
  }

  function handleDragStart(index: number) {
    if (disabled) {
      return;
    }

    setDragIndex(index);
  }

  function handleDragOver(event: DragEvent<HTMLDivElement>, index: number) {
    event.preventDefault();

    if (disabled || dragIndex === null || dragIndex === index) {
      return;
    }

    setDropIndex(index);
  }

  function handleDrop(index: number) {
    if (disabled || dragIndex === null || dragIndex === index) {
      setDragIndex(null);
      setDropIndex(null);
      return;
    }

    const next = [...sections];
    const [item] = next.splice(dragIndex, 1);
    next.splice(index, 0, item);
    setSections(next);
    setDragIndex(null);
    setDropIndex(null);
  }

  return (
    <div>
      {sections.map((section, index) => (
        <div
          key={`section-${index}`}
          draggable={!disabled}
          onDragStart={() => handleDragStart(index)}
          onDragOver={(event) => handleDragOver(event, index)}
          onDrop={() => handleDrop(index)}
          onDragEnd={() => {
            setDragIndex(null);
            setDropIndex(null);
          }}
          style={{
            marginBottom: 12,
            outline: dropIndex === index ? '2px dashed #2563eb' : undefined,
            opacity: dragIndex === index ? 0.65 : 1,
            cursor: disabled ? 'default' : 'grab',
          }}
        >
        <Card
          title={`${sectionTypeLabel(section.type)} · section ${index + 1}`}
        >
          <div style={{ display: 'flex', gap: '0.5rem', marginBottom: 12, flexWrap: 'wrap' }}>
            <Button type="button" variant="secondary" disabled>
              Drag to reorder
            </Button>
            <Button
              type="button"
              variant="secondary"
              disabled={disabled || index === 0}
              onClick={() => moveSection(index, -1)}
            >
              Move up
            </Button>
            <Button
              type="button"
              variant="secondary"
              disabled={disabled || index === sections.length - 1}
              onClick={() => moveSection(index, 1)}
            >
              Move down
            </Button>
            <Button
              type="button"
              variant="secondary"
              disabled={disabled || sections.length <= 1}
              onClick={() => removeSection(index)}
            >
              Remove
            </Button>
          </div>

          {section.type === 'rich-text' && (
            <label style={{ display: 'block' }}>
              <span style={{ display: 'block', marginBottom: 4, fontWeight: 600 }}>Content</span>
              <textarea
                rows={6}
                value={section.content}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, {
                      ...section,
                      content: e.target.value,
                    }),
                  )
                }
                style={{ width: '100%', padding: 8, fontFamily: 'inherit' }}
              />
            </label>
          )}

          {section.type === 'image-banner' && (
            <>
              <Input
                label="Heading"
                value={section.heading}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, heading: e.target.value }),
                  )
                }
              />
              <Input
                label="Subheading"
                value={section.subheading ?? ''}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, subheading: e.target.value }),
                  )
                }
              />
              <Input
                label="Image URL"
                value={section.image_url}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, image_url: e.target.value }),
                  )
                }
              />
              <Input
                label="CTA label"
                value={section.cta_label ?? ''}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, cta_label: e.target.value }),
                  )
                }
              />
              <Input
                label="CTA link"
                value={section.cta_href ?? ''}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, cta_href: e.target.value }),
                  )
                }
              />
            </>
          )}

          {section.type === 'faq-accordion' && (
            <>
              <Input
                label="Section heading"
                value={section.heading ?? ''}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, heading: e.target.value }),
                  )
                }
              />
              {section.items.map((item, itemIndex) => (
                <div
                  key={`faq-${index}-${itemIndex}`}
                  style={{ borderTop: '1px solid #e5e7eb', marginTop: 12, paddingTop: 12 }}
                >
                  <Input
                    label={`Question ${itemIndex + 1}`}
                    value={item.question}
                    disabled={disabled}
                    onChange={(e) => {
                      const items = section.items.map((row, i) =>
                        i === itemIndex ? { ...row, question: e.target.value } : row,
                      );
                      setSections(updateSection(sections, index, { ...section, items }));
                    }}
                  />
                  <label style={{ display: 'block' }}>
                    <span style={{ display: 'block', marginBottom: 4, fontWeight: 600 }}>
                      Answer {itemIndex + 1}
                    </span>
                    <textarea
                      rows={3}
                      value={item.answer}
                      disabled={disabled}
                      onChange={(e) => {
                        const items = section.items.map((row, i) =>
                          i === itemIndex ? { ...row, answer: e.target.value } : row,
                        );
                        setSections(updateSection(sections, index, { ...section, items }));
                      }}
                      style={{ width: '100%', padding: 8, fontFamily: 'inherit' }}
                    />
                  </label>
                  <Button
                    type="button"
                    variant="secondary"
                    disabled={disabled || section.items.length <= 1}
                    onClick={() => {
                      const items = section.items.filter((_, i) => i !== itemIndex);
                      setSections(updateSection(sections, index, { ...section, items }));
                    }}
                  >
                    Remove question
                  </Button>
                </div>
              ))}
              <Button
                type="button"
                variant="secondary"
                disabled={disabled}
                onClick={() => {
                  const items = [...section.items, { question: '', answer: '' }];
                  setSections(updateSection(sections, index, { ...section, items }));
                }}
              >
                Add question
              </Button>
            </>
          )}

          {section.type === 'testimonials' && (
            <>
              <Input
                label="Section heading"
                value={section.heading ?? ''}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, heading: e.target.value }),
                  )
                }
              />
              {section.items.map((item, itemIndex) => (
                <div
                  key={`testimonial-${index}-${itemIndex}`}
                  style={{ borderTop: '1px solid #e5e7eb', marginTop: 12, paddingTop: 12 }}
                >
                  <label style={{ display: 'block' }}>
                    <span style={{ display: 'block', marginBottom: 4, fontWeight: 600 }}>
                      Quote {itemIndex + 1}
                    </span>
                    <textarea
                      rows={3}
                      value={item.quote}
                      disabled={disabled}
                      onChange={(e) => {
                        const items = section.items.map((row, i) =>
                          i === itemIndex ? { ...row, quote: e.target.value } : row,
                        );
                        setSections(updateSection(sections, index, { ...section, items }));
                      }}
                      style={{ width: '100%', padding: 8, fontFamily: 'inherit' }}
                    />
                  </label>
                  <Input
                    label="Author"
                    value={item.author}
                    disabled={disabled}
                    onChange={(e) => {
                      const items = section.items.map((row, i) =>
                        i === itemIndex ? { ...row, author: e.target.value } : row,
                      );
                      setSections(updateSection(sections, index, { ...section, items }));
                    }}
                  />
                  <Input
                    label="Role (optional)"
                    value={item.role ?? ''}
                    disabled={disabled}
                    onChange={(e) => {
                      const items = section.items.map((row, i) =>
                        i === itemIndex ? { ...row, role: e.target.value } : row,
                      );
                      setSections(updateSection(sections, index, { ...section, items }));
                    }}
                  />
                  <Button
                    type="button"
                    variant="secondary"
                    disabled={disabled || section.items.length <= 1}
                    onClick={() => {
                      const items = section.items.filter((_, i) => i !== itemIndex);
                      setSections(updateSection(sections, index, { ...section, items }));
                    }}
                  >
                    Remove testimonial
                  </Button>
                </div>
              ))}
              <Button
                type="button"
                variant="secondary"
                disabled={disabled}
                onClick={() => {
                  const items = [...section.items, { quote: '', author: '' }];
                  setSections(updateSection(sections, index, { ...section, items }));
                }}
              >
                Add testimonial
              </Button>
            </>
          )}

          {section.type === 'video-embed' && (
            <>
              <Input
                label="Section heading"
                value={section.heading ?? ''}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, heading: e.target.value }),
                  )
                }
              />
              <Input
                label="Embed URL (YouTube, Vimeo, etc.)"
                value={section.embed_url}
                disabled={disabled}
                onChange={(e) =>
                  setSections(
                    updateSection(sections, index, { ...section, embed_url: e.target.value }),
                  )
                }
              />
            </>
          )}
        </Card>
        </div>
      ))}

      <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap', marginTop: 12 }}>
        {CMS_SECTION_TYPES.map((type) => (
          <Button
            key={type}
            type="button"
            variant="secondary"
            disabled={disabled}
            onClick={() => addSection(type)}
          >
            + {sectionTypeLabel(type)}
          </Button>
        ))}
      </div>
    </div>
  );
}
