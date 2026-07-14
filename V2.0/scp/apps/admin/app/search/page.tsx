'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  createSearchSynonym,
  deleteSearchSynonym,
  fetchSearchAnalytics,
  fetchSearchSynonyms,
  generateZeroResultSuggest,
  getStoredTenantId,
  getStoredToken,
  type SearchAnalytics,
  type SearchSynonym,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function SearchAdminPage() {
  const router = useRouter();
  const [analytics, setAnalytics] = useState<SearchAnalytics | null>(null);
  const [synonyms, setSynonyms] = useState<SearchSynonym[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);
  const [term, setTerm] = useState('');
  const [synonym, setSynonym] = useState('');
  const [suggestDraft, setSuggestDraft] = useState<string | null>(null);
  const [suggestQuery, setSuggestQuery] = useState<string | null>(null);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    Promise.all([fetchSearchAnalytics(tenantId), fetchSearchSynonyms(tenantId)])
      .then(([analyticsResult, synonymResult]) => {
        setAnalytics(analyticsResult);
        setSynonyms(synonymResult);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load search tools.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !term.trim() || !synonym.trim()) {
      setError('Term and synonym are required.');
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const created = await createSearchSynonym(tenantId, {
        term: term.trim(),
        synonym: synonym.trim(),
      });
      setSynonyms((current) => [...current, created]);
      setTerm('');
      setSynonym('');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create synonym.');
    } finally {
      setWorking(false);
    }
  }

  async function handleSuggest(query: string, searches: number) {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const result = await generateZeroResultSuggest(tenantId, {
        query,
        search_count: searches,
      });
      setSuggestQuery(query);
      setSuggestDraft(result.draft);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to generate suggestions.');
    } finally {
      setWorking(false);
    }
  }

  async function handleDelete(id: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId || !window.confirm('Delete this synonym?')) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      await deleteSearchSynonym(tenantId, id);
      setSynonyms((current) => current.filter((item) => item.id !== id));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete synonym.');
    } finally {
      setWorking(false);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Search" subtitle="Loading…" nav={adminNav} activeHref="/search">
        <p>Loading search analytics…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Search"
      subtitle="Facets · synonyms · analytics"
      nav={adminNav}
      activeHref="/search"
      onLogout={handleLogout}
    >
      {error && <Alert variant="error">{error}</Alert>}

      <Card title="Top queries (30 days)">
        <Table>
          <thead>
            <tr>
              <Th>Query</Th>
              <Th>Searches</Th>
              <Th>Avg results</Th>
            </tr>
          </thead>
          <tbody>
            {(analytics?.top_queries ?? []).map((row) => (
              <tr key={row.query}>
                <Td>{row.query}</Td>
                <Td>{row.searches}</Td>
                <Td>{row.avg_results}</Td>
              </tr>
            ))}
          </tbody>
        </Table>
        {(analytics?.top_queries.length ?? 0) === 0 && <p>No search traffic yet.</p>}
      </Card>

      <Card title="Zero-result queries">
        <Table>
          <thead>
            <tr>
              <Th>Query</Th>
              <Th>Searches</Th>
              <Th />
            </tr>
          </thead>
          <tbody>
            {(analytics?.zero_result_queries ?? []).map((row) => (
              <tr key={row.query}>
                <Td>{row.query}</Td>
                <Td>{row.searches}</Td>
                <Td>
                  <Button
                    type="button"
                    variant="secondary"
                    disabled={working}
                    onClick={() => handleSuggest(row.query, row.searches)}
                  >
                    Suggest products
                  </Button>
                </Td>
              </tr>
            ))}
          </tbody>
        </Table>
        {(analytics?.zero_result_queries.length ?? 0) === 0 && <p>No zero-result queries.</p>}
        {suggestDraft && (
          <div style={{ marginTop: 16 }}>
            <p style={{ marginTop: 0 }}>
              AI draft for <strong>{suggestQuery}</strong> (edit before acting):
            </p>
            <pre style={{ whiteSpace: 'pre-wrap', fontFamily: 'inherit' }}>{suggestDraft}</pre>
          </div>
        )}
      </Card>

      <Card title="Synonym dictionary">
        <form onSubmit={handleCreate} style={{ display: 'flex', gap: '0.75rem', flexWrap: 'wrap' }}>
          <Input label="Term" value={term} onChange={(event) => setTerm(event.target.value)} />
          <Input
            label="Synonym"
            value={synonym}
            onChange={(event) => setSynonym(event.target.value)}
          />
          <Button type="submit" disabled={working}>
            Add
          </Button>
        </form>
        <Table>
          <thead>
            <tr>
              <Th>Term</Th>
              <Th>Synonym</Th>
              <Th />
            </tr>
          </thead>
          <tbody>
            {synonyms.map((item) => (
              <tr key={item.id}>
                <Td>{item.term}</Td>
                <Td>{item.synonym}</Td>
                <Td>
                  <Button type="button" variant="secondary" onClick={() => handleDelete(item.id)}>
                    Delete
                  </Button>
                </Td>
              </tr>
            ))}
          </tbody>
        </Table>
      </Card>
    </AdminShell>
  );
}
