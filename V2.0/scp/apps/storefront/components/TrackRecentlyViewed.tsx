'use client';

import { useEffect } from 'react';
import { recordRecentlyViewed } from '@/lib/recently-viewed';

interface TrackRecentlyViewedProps {
  productId: string;
  tenantKey: string;
}

export function TrackRecentlyViewed({ productId, tenantKey }: TrackRecentlyViewedProps) {
  useEffect(() => {
    if (!productId || !tenantKey) {
      return;
    }

    recordRecentlyViewed(tenantKey, productId);
  }, [productId, tenantKey]);

  return null;
}
