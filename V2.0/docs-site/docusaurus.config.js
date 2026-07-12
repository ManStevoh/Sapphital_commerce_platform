// @ts-check
/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'SAPPHITAL Commerce Platform',
  tagline: 'Official Engineering Specification v1.0',
  favicon: 'img/favicon.ico',

  url: 'https://sapphital.github.io',  baseUrl: '/commerce-platform/',

  organizationName: 'sapphital',
  projectName: 'commerce-platform',

  onBrokenLinks: 'warn',
  onBrokenMarkdownLinks: 'warn',

  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  markdown: {
    mermaid: true,
  },

  themes: ['@docusaurus/theme-mermaid'],

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          path: '../docs',
          routeBasePath: '/',
          sidebarPath: './sidebars.js',
          editUrl: 'https://github.com/sapphital/commerce-platform/tree/main/V2.0/docs/',
          showLastUpdateTime: false,
          showLastUpdateAuthor: false,
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      image: 'img/scp-social-card.jpg',
      colorMode: {
        defaultMode: 'light',
        respectPrefersColorScheme: true,
      },
      navbar: {
        title: 'SCP Engineering Spec',
        logo: {
          alt: 'SAPPHITAL Commerce Platform',
          src: 'img/logo.svg',
        },
        items: [
          {
            type: 'docSidebar',
            sidebarId: 'specSidebar',
            position: 'left',
            label: 'Specification',
          },
          {
            href: 'https://github.com/sapphital/commerce-platform',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'Specification',
            items: [
              { label: 'Volume 1: Vision', to: '/vision/' },
              { label: 'Engineering Principles', to: '/meta/engineering-principles' },
              { label: 'Glossary', to: '/meta/glossary' },
            ],
          },
          {
            title: 'Company',
            items: [
              { label: 'Sapphital Learning Company', href: 'https://sapphital.com' },
              { label: 'GitHub', href: 'https://github.com/sapphital' },
            ],
          },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} Sapphital Learning Company. Built with Docusaurus.`,
      },
      prism: {
        theme: require('prism-react-renderer').themes.github,
        darkTheme: require('prism-react-renderer').themes.dracula,
        additionalLanguages: ['php', 'bash', 'json', 'sql', 'typescript'],
      },
      docs: {
        sidebar: {
          hideable: true,
          autoCollapseCategories: true,
        },
      },
    }),
};

module.exports = config;
