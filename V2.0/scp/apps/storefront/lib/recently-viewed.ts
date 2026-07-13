const COOKIE_PREFIX = 'scp_recently_viewed';
const MAX_ITEMS = 8;
const MAX_AGE_SECONDS = 60 * 60 * 24 * 30;

export function recentlyViewedCookieName(tenantKey: string): string {
  const safe = tenantKey.replace(/[^a-zA-Z0-9_-]/g, '_').slice(0, 48);

  return `${COOKIE_PREFIX}_${safe || 'store'}`;
}

/**
 * Prepend a product id and keep the list unique + capped (most-recent first).
 */
export function pushRecentlyViewedId(current: string[], productId: string, max = MAX_ITEMS): string[] {
  const id = productId.trim();

  if (id === '') {
    return current.slice(0, max);
  }

  return [id, ...current.filter((item) => item !== id)].slice(0, max);
}

export function parseRecentlyViewedCookie(value: string | undefined | null): string[] {
  if (!value) {
    return [];
  }

  try {
    const decoded = decodeURIComponent(value);
    const parsed = JSON.parse(decoded) as unknown;

    if (Array.isArray(parsed)) {
      return parsed.filter((item): item is string => typeof item === 'string' && item !== '');
    }

    if (
      parsed &&
      typeof parsed === 'object' &&
      Array.isArray((parsed as { ids?: unknown }).ids)
    ) {
      return (parsed as { ids: unknown[] }).ids.filter(
        (item): item is string => typeof item === 'string' && item !== '',
      );
    }
  } catch {
    return value
      .split(',')
      .map((part) => part.trim())
      .filter(Boolean);
  }

  return [];
}

export function serializeRecentlyViewedCookie(ids: string[]): string {
  return encodeURIComponent(JSON.stringify({ ids: ids.slice(0, MAX_ITEMS) }));
}

export function writeRecentlyViewedCookie(tenantKey: string, ids: string[]): void {
  if (typeof document === 'undefined') {
    return;
  }

  const name = recentlyViewedCookieName(tenantKey);
  const value = serializeRecentlyViewedCookie(ids);
  document.cookie = `${name}=${value}; Path=/; Max-Age=${MAX_AGE_SECONDS}; SameSite=Lax`;
}

export function readRecentlyViewedCookie(tenantKey: string): string[] {
  if (typeof document === 'undefined') {
    return [];
  }

  const name = recentlyViewedCookieName(tenantKey);
  const parts = document.cookie.split(';');

  for (const part of parts) {
    const [rawKey, ...rest] = part.trim().split('=');
    if (rawKey === name) {
      return parseRecentlyViewedCookie(rest.join('='));
    }
  }

  return [];
}

export function clearRecentlyViewedCookie(tenantKey: string): void {
  if (typeof document === 'undefined') {
    return;
  }

  const name = recentlyViewedCookieName(tenantKey);
  document.cookie = `${name}=; Path=/; Max-Age=0; SameSite=Lax`;
}

export function recordRecentlyViewed(tenantKey: string, productId: string): string[] {
  const next = pushRecentlyViewedId(readRecentlyViewedCookie(tenantKey), productId);
  writeRecentlyViewedCookie(tenantKey, next);

  return next;
}

export { MAX_ITEMS as RECENTLY_VIEWED_MAX };
