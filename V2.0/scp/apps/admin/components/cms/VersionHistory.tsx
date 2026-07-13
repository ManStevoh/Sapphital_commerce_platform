'use client';

import { Alert, Button, Card } from '@sapphital/scp-ui';
import { useCallback, useEffect, useState } from 'react';

export interface CmsContentVersion {
  id: string;
  version_number: number;
  label: string | null;
  created_at: string | null;
  snapshot_json: Record<string, unknown>;
}

interface VersionHistoryProps {
  versions: CmsContentVersion[];
  loading?: boolean;
  working?: boolean;
  error?: string | null;
  onRefresh: () => void;
  onRestore: (versionId: string) => Promise<void>;
}

export function VersionHistory({
  versions,
  loading,
  working,
  error,
  onRefresh,
  onRestore,
}: VersionHistoryProps) {
  return (
    <Card title="Version history">
      <div style={{ display: 'flex', gap: '0.5rem', marginBottom: 12 }}>
        <Button type="button" variant="secondary" disabled={loading || working} onClick={onRefresh}>
          Refresh
        </Button>
      </div>
      {error && <Alert>{error}</Alert>}
      {loading ? (
        <p>Loading versions…</p>
      ) : versions.length === 0 ? (
        <p>No versions yet. Save changes to create the first snapshot.</p>
      ) : (
        <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
          {versions.map((version) => (
            <li
              key={version.id}
              style={{
                display: 'flex',
                justifyContent: 'space-between',
                gap: '1rem',
                alignItems: 'center',
                borderTop: '1px solid #e5e7eb',
                padding: '0.75rem 0',
              }}
            >
              <div>
                <strong>v{version.version_number}</strong>
                {version.label ? ` · ${version.label}` : ''}
                <div style={{ fontSize: '0.875rem', color: '#6b7280' }}>
                  {version.created_at
                    ? new Date(version.created_at).toLocaleString()
                    : 'Unknown time'}
                </div>
              </div>
              <Button
                type="button"
                variant="secondary"
                disabled={working}
                onClick={() => void onRestore(version.id)}
              >
                Restore
              </Button>
            </li>
          ))}
        </ul>
      )}
    </Card>
  );
}

export function useCmsVersions(loader: () => Promise<CmsContentVersion[]>) {
  const [versions, setVersions] = useState<CmsContentVersion[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setVersions(await loader());
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load versions.');
    } finally {
      setLoading(false);
    }
  }, [loader]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  return { versions, loading, error, refresh, setVersions, setError };
}
