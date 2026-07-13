import type { MetadataRoute } from 'next';

const SITE_URL = process.env.NEXT_PUBLIC_MARKETING_URL ?? 'https://sapphital.africa';

export default function sitemap(): MetadataRoute.Sitemap {
  return [
    {
      url: SITE_URL,
      lastModified: new Date(),
      changeFrequency: 'weekly',
      priority: 1,
    },
    {
      url: `${SITE_URL}/signup`,
      lastModified: new Date(),
      changeFrequency: 'monthly',
      priority: 0.9,
    },
  ];
}
