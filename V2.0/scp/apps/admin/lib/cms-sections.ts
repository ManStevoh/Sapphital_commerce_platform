export const CMS_SECTION_TYPES = [
  'rich-text',
  'image-banner',
  'faq-accordion',
  'testimonials',
  'video-embed',
] as const;

export type CmsSectionType = (typeof CMS_SECTION_TYPES)[number];

export interface CmsFaqItem {
  question: string;
  answer: string;
}

export interface CmsTestimonialItem {
  quote: string;
  author: string;
  role?: string;
}

export interface RichTextSection {
  type: 'rich-text';
  content: string;
}

export interface ImageBannerSection {
  type: 'image-banner';
  heading: string;
  subheading?: string;
  image_url: string;
  cta_label?: string;
  cta_href?: string;
}

export interface FaqAccordionSection {
  type: 'faq-accordion';
  heading?: string;
  items: CmsFaqItem[];
}

export interface TestimonialsSection {
  type: 'testimonials';
  heading?: string;
  items: CmsTestimonialItem[];
}

export interface VideoEmbedSection {
  type: 'video-embed';
  heading?: string;
  embed_url: string;
}

export type CmsSection =
  | RichTextSection
  | ImageBannerSection
  | FaqAccordionSection
  | TestimonialsSection
  | VideoEmbedSection;

export interface CmsBodyJson {
  sections: CmsSection[];
}

export function emptySection(type: CmsSectionType): CmsSection {
  switch (type) {
    case 'rich-text':
      return { type: 'rich-text', content: '' };
    case 'image-banner':
      return { type: 'image-banner', heading: '', image_url: '' };
    case 'faq-accordion':
      return { type: 'faq-accordion', heading: '', items: [{ question: '', answer: '' }] };
    case 'testimonials':
      return { type: 'testimonials', heading: '', items: [{ quote: '', author: '' }] };
    case 'video-embed':
      return { type: 'video-embed', heading: '', embed_url: '' };
  }
}

export function sectionTypeLabel(type: CmsSectionType): string {
  switch (type) {
    case 'rich-text':
      return 'Rich text';
    case 'image-banner':
      return 'Image banner';
    case 'faq-accordion':
      return 'FAQ accordion';
    case 'testimonials':
      return 'Testimonials';
    case 'video-embed':
      return 'Video embed';
  }
}

export function normalizeBodyJson(body: unknown): CmsBodyJson {
  if (
    typeof body === 'object' &&
    body !== null &&
    'sections' in body &&
    Array.isArray((body as CmsBodyJson).sections)
  ) {
    return { sections: (body as CmsBodyJson).sections };
  }

  return { sections: [{ type: 'rich-text', content: '' }] };
}
