'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { searchAutocomplete, type AutocompleteSuggestion } from '@/lib/api';
import { resolveClientTenantId } from '@/lib/tenant-client';

interface SearchAutocompleteProps {
  defaultQuery?: string;
}

export function SearchAutocomplete({ defaultQuery = '' }: SearchAutocompleteProps) {
  const [query, setQuery] = useState(defaultQuery);
  const [suggestions, setSuggestions] = useState<AutocompleteSuggestion[]>([]);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    if (query.trim().length < 2) {
      setSuggestions([]);
      return;
    }

    let cancelled = false;
    const timer = window.setTimeout(() => {
      void (async () => {
        try {
          const tenantId = await resolveClientTenantId();
          const rows = await searchAutocomplete(tenantId, query.trim());
          if (!cancelled) {
            setSuggestions(rows);
            setOpen(true);
          }
        } catch {
          if (!cancelled) {
            setSuggestions([]);
          }
        }
      })();
    }, 180);

    return () => {
      cancelled = true;
      window.clearTimeout(timer);
    };
  }, [query]);

  return (
    <div style={{ position: 'relative' }}>
      <input
        name="q"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        onFocus={() => setOpen(suggestions.length > 0)}
        onBlur={() => {
          window.setTimeout(() => setOpen(false), 150);
        }}
        placeholder="Search products"
        aria-label="Search products"
        autoComplete="off"
        style={{ width: '100%', padding: '0.6rem 0.75rem', fontSize: '1rem' }}
      />
      {open && suggestions.length > 0 && (
        <ul
          style={{
            position: 'absolute',
            zIndex: 20,
            left: 0,
            right: 0,
            margin: 0,
            padding: 0,
            listStyle: 'none',
            background: 'var(--color-surface, #fff)',
            border: '1px solid var(--color-border, #e5e7eb)',
            maxHeight: 240,
            overflow: 'auto',
          }}
        >
          {suggestions.map((item) => (
            <li key={item.id} style={{ borderTop: '1px solid var(--color-border, #e5e7eb)' }}>
              <Link
                href={`/products/${item.id}`}
                style={{ display: 'block', padding: '0.6rem 0.75rem', textDecoration: 'none' }}
              >
                {item.name}
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
