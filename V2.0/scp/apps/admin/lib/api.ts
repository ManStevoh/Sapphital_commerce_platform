const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

export interface LoginResponse {
  token: string;
  token_type: string;
}

export interface MeResponse {
  id: string;
  type: string;
  email: string;
  tenant_id: string;
}

export interface Product {
  id: string;
  tenant_id: string;
  name: string;
  slug: string;
  price_kobo: number;
  status: 'draft' | 'published';
  inventory_qty: number;
}

export interface ProductListResponse {
  data: Product[];
}

export interface ProductResponse {
  data: Product;
}

export interface ProductInput {
  name: string;
  slug?: string;
  price_kobo: number;
  status: 'draft' | 'published';
  inventory_qty: number;
}

export interface OrderItem {
  id: string;
  product_id: string;
  product_name: string;
  quantity: number;
  unit_price_kobo: number;
  line_total_kobo: number;
}

export interface Order {
  id: string;
  tenant_id: string;
  checkout_session_id: string | null;
  order_number: string;
  status: string;
  currency: string;
  subtotal_kobo: number;
  total_kobo: number;
  customer_email: string | null;
  paystack_reference: string | null;
  items: OrderItem[];
  created_at: string | null;
  updated_at: string | null;
}

export interface OrderListResponse {
  data: Order[];
}

export interface OrderResponse {
  data: Order;
}

export interface ShipmentLine {
  id: string;
  order_item_id: string;
  quantity: number;
}

export interface Shipment {
  id: string;
  tenant_id: string;
  order_id: string;
  status: string;
  carrier: string | null;
  tracking_number: string | null;
  tracking_url: string | null;
  weight_grams: number | null;
  shipped_at: string | null;
  delivered_at: string | null;
  lines: ShipmentLine[];
  created_at: string | null;
  updated_at: string | null;
}

export interface ShipmentListResponse {
  data: Shipment[];
}

export interface ShipmentResponse {
  data: Shipment;
}

const TOKEN_KEY = 'scp_admin_token';
const TENANT_ID_KEY = 'scp_admin_tenant_id';

async function parseJson<T>(response: Response): Promise<T> {
  const body = await response.json().catch(() => ({}));

  if (!response.ok) {
    const message =
      typeof body === 'object' &&
      body !== null &&
      'message' in body &&
      typeof body.message === 'string'
        ? body.message
        : `Request failed (${response.status})`;
    throw new Error(message);
  }

  return body as T;
}

export function getStoredToken(): string | null {
  if (typeof window === 'undefined') {
    return null;
  }
  return localStorage.getItem(TOKEN_KEY);
}

export function getStoredTenantId(): string | null {
  if (typeof window === 'undefined') {
    return null;
  }
  return localStorage.getItem(TENANT_ID_KEY);
}

export function storeAuth(token: string, tenantId: string): void {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(TENANT_ID_KEY, tenantId);
}

export function clearAuth(): void {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(TENANT_ID_KEY);
}

export function formatNgn(kobo: number): string {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
  }).format(kobo / 100);
}

export async function merchantLogin(
  email: string,
  password: string,
): Promise<LoginResponse> {
  const response = await fetch(`${API_URL}/api/v1/auth/merchant/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  });

  return parseJson<LoginResponse>(response);
}

export async function fetchMe(token: string): Promise<MeResponse> {
  const response = await fetch(`${API_URL}/api/v1/auth/me`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });

  return parseJson<MeResponse>(response);
}

function tenantHeaders(tenantId: string): HeadersInit {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Tenant-ID': tenantId,
  };

  const token = getStoredToken();

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  return headers;
}

export async function fetchProducts(tenantId: string): Promise<Product[]> {
  const response = await fetch(`${API_URL}/api/v1/commerce/catalog/products`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<ProductListResponse>(response);
  return result.data;
}

export async function fetchProduct(
  tenantId: string,
  productId: string,
): Promise<Product> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/products/${encodeURIComponent(productId)}`,
    {
      headers: tenantHeaders(tenantId),
    },
  );

  const result = await parseJson<ProductResponse>(response);
  return result.data;
}

export async function createProduct(
  tenantId: string,
  input: ProductInput,
): Promise<Product> {
  const response = await fetch(`${API_URL}/api/v1/commerce/catalog/products`, {
    method: 'POST',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(input),
  });

  const result = await parseJson<ProductResponse>(response);
  return result.data;
}

export async function updateProduct(
  tenantId: string,
  productId: string,
  input: ProductInput,
): Promise<Product> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/products/${encodeURIComponent(productId)}`,
    {
      method: 'PUT',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(input),
    },
  );

  const result = await parseJson<ProductResponse>(response);
  return result.data;
}

export async function deleteProduct(
  tenantId: string,
  productId: string,
): Promise<void> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/products/${encodeURIComponent(productId)}`,
    {
      method: 'DELETE',
      headers: tenantHeaders(tenantId),
    },
  );

  if (!response.ok) {
    await parseJson(response);
  }
}

export async function listOrders(tenantId: string): Promise<Order[]> {
  const response = await fetch(`${API_URL}/api/v1/commerce/orders`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<OrderListResponse>(response);
  return result.data;
}

export async function getOrder(tenantId: string, orderId: string): Promise<Order> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/orders/${encodeURIComponent(orderId)}`,
    {
      headers: tenantHeaders(tenantId),
    },
  );

  const result = await parseJson<OrderResponse>(response);
  return result.data;
}

export async function listShipments(tenantId: string): Promise<Shipment[]> {
  const response = await fetch(`${API_URL}/api/v1/commerce/shipping/shipments`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<ShipmentListResponse>(response);
  return result.data;
}

export async function createShipmentFromOrder(
  tenantId: string,
  orderId: string,
): Promise<Shipment> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/shipping/shipments/from-order`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ order_id: orderId }),
    },
  );

  const result = await parseJson<ShipmentResponse>(response);
  return result.data;
}

export async function markShipmentShipped(
  tenantId: string,
  shipmentId: string,
  trackingNumber: string,
  trackingUrl?: string,
): Promise<Shipment> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/shipping/shipments/${encodeURIComponent(shipmentId)}/ship`,
    {
      method: 'PATCH',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        tracking_number: trackingNumber,
        tracking_url: trackingUrl ?? null,
      }),
    },
  );

  const result = await parseJson<ShipmentResponse>(response);
  return result.data;
}

export async function markShipmentDelivered(
  tenantId: string,
  shipmentId: string,
): Promise<Shipment> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/shipping/shipments/${encodeURIComponent(shipmentId)}/deliver`,
    {
      method: 'PATCH',
      headers: tenantHeaders(tenantId),
    },
  );

  const result = await parseJson<ShipmentResponse>(response);
  return result.data;
}

export interface ThemeSettings {
  primary_color: string;
  font_heading: string;
  logo_url: string | null;
}

export interface ThemeConfig {
  theme_id: string;
  id: string;
  name: string;
  settings: ThemeSettings;
  colors: Record<string, string>;
}

export interface ThemeResponse {
  data: ThemeConfig;
}

export async function fetchTheme(tenantId: string): Promise<ThemeConfig> {
  const response = await fetch(`${API_URL}/api/v1/commerce/storefront/theme`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<ThemeResponse>(response);
  return result.data;
}

export async function updateThemeSettings(
  tenantId: string,
  settings: Partial<ThemeSettings>,
): Promise<ThemeConfig> {
  const response = await fetch(`${API_URL}/api/v1/commerce/storefront/theme/settings`, {
    method: 'PUT',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(settings),
  });

  const result = await parseJson<ThemeResponse>(response);
  return result.data;
}
