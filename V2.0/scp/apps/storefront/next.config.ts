import path from 'path';
import type { NextConfig } from 'next';

const uiPackage = path.join(__dirname, '../packages/ui/src');

const nextConfig: NextConfig = {
  reactStrictMode: true,
  webpack: (config) => {
    config.resolve.alias['@sapphital/scp-ui'] = uiPackage;
    return config;
  },
};

export default nextConfig;
