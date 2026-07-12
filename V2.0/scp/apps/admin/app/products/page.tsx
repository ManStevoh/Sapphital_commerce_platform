'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  deleteProduct,
  fetchProducts,
  formatNgn,
  getStoredTenantId,
  getStoredToken,
  type Product,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function ProductsPage() {
  const router = useRouter();
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deletingId, setDeletingId] = useState<string | null>(null);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchProducts(tenantId)
      .then(setProducts)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load products.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleDelete(productId: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId || !window.confirm('Delete this product?')) {
      return;
    }

    setDeletingId(productId);
    setError(null);

    try {
      await deleteProduct(tenantId, productId);
      setProducts((current) => current.filter((product) => product.id !== productId));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete product.');
    } finally {
      setDeletingId(null);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Products" subtitle="Loading…" nav={adminNav} activeHref="/products">
        <p>Loading products…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Products"
      subtitle={`${products.length} total`}
      nav={adminNav}
      activeHref="/products"
      onSignOut={handleLogout}
    >
      <div style={{ marginBottom: 16 }}>
        <Link href="/products/new">
          <Button>New product</Button>
        </Link>
      </div>

      {error && <Alert>{error}</Alert>}

      {products.length === 0 ? (
        <p>
          No products yet. <Link href="/products/new">Create one</Link>.
        </p>
      ) : (
        <Table aria-label="Product list">
          <thead>
            <tr>
              <Th>Name</Th>
              <Th>Price</Th>
              <Th>Status</Th>
              <Th>Stock</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody>
            {products.map((product) => (
              <tr key={product.id}>
                <Td>{product.name}</Td>
                <Td>{formatNgn(product.price_kobo)}</Td>
                <Td>{product.status}</Td>
                <Td>{product.inventory_qty}</Td>
                <Td>
                  <Link href={`/products/${product.id}/edit`} style={{ marginRight: 12 }}>
                    Edit
                  </Link>
                  <Button
                    variant="danger"
                    onClick={() => handleDelete(product.id)}
                    disabled={deletingId === product.id}
                  >
                    {deletingId === product.id ? 'Deleting…' : 'Delete'}
                  </Button>
                </Td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </AdminShell>
  );
}
