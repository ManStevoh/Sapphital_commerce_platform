'use client';

import { useEffect, useState } from 'react';
import { Button } from '@sapphital/scp-ui';
import { isInWishlist, toggleWishlistId } from '@/lib/wishlist';

export function WishlistButton({ productId }: { productId: string }) {
  const [active, setActive] = useState(false);

  useEffect(() => {
    setActive(isInWishlist(productId));
  }, [productId]);

  return (
    <Button
      type="button"
      variant="secondary"
      onClick={() => {
        const next = toggleWishlistId(productId);
        setActive(next.includes(productId));
      }}
    >
      {active ? 'Saved to wishlist' : 'Add to wishlist'}
    </Button>
  );
}
