const WISHLIST_KEY = 'scp_wishlist_v1';

export function readWishlistIds(): string[] {
  if (typeof window === 'undefined') {
    return [];
  }

  try {
    const raw = window.localStorage.getItem(WISHLIST_KEY);
    if (!raw) {
      return [];
    }

    const parsed = JSON.parse(raw) as unknown;
    if (!Array.isArray(parsed)) {
      return [];
    }

    return parsed.filter((value): value is string => typeof value === 'string' && value.length > 0);
  } catch {
    return [];
  }
}

export function writeWishlistIds(ids: string[]): void {
  if (typeof window === 'undefined') {
    return;
  }

  window.localStorage.setItem(WISHLIST_KEY, JSON.stringify(ids.slice(0, 100)));
}

export function toggleWishlistId(productId: string): string[] {
  const current = readWishlistIds();
  const next = current.includes(productId)
    ? current.filter((id) => id !== productId)
    : [productId, ...current];

  writeWishlistIds(next);
  return next;
}

export function isInWishlist(productId: string): boolean {
  return readWishlistIds().includes(productId);
}
