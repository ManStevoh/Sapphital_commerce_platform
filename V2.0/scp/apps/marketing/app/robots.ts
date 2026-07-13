import type { MetadataRoute } from 'next';

const SITE_URL = process.env.NEXT_PUBLIC_MARKETING_URL ?? 'https://sapphital.africa';

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: '*',
      allow: '/',
      disallow: ['/signup/success'],
    },
    sitemap: `${SITE_URL}/sitemap.xml`,
  };
}
