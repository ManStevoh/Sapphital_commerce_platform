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
  tags?: string[] | null;
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
  tags?: string[];
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

export interface RefundResult {
  id: string;
  order_id: string;
  amount_kobo: number;
  currency: string;
  status: string;
  gateway_refund_reference: string | null;
}

export interface RefundOrderResponse {
  data: {
    refund: RefundResult;
    order: Order;
  };
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

export interface ReturnLine {
  id: string;
  order_item_id: string;
  quantity: number;
  restock: boolean;
}

export interface ReturnRequest {
  id: string;
  tenant_id: string;
  order_id: string;
  status: string;
  reason: string;
  notes: string | null;
  rejection_reason: string | null;
  requested_at: string | null;
  resolved_at: string | null;
  lines: ReturnLine[];
}

export interface ReturnRequestListResponse {
  data: ReturnRequest[];
}

export interface ReturnRequestResponse {
  data: ReturnRequest;
}

export interface Dispute {
  id: string;
  tenant_id: string;
  order_id: string;
  type: string;
  provider: string;
  status: string;
  provider_case_id: string;
  amount_kobo: number;
  currency: string;
  paystack_reference: string;
  due_at: string | null;
  resolved_at: string | null;
  created_at: string | null;
}

export interface DisputeListResponse {
  data: Dispute[];
}

export interface DisputeResponse {
  data: Dispute;
}

export interface PaymentReconciliationEntry {
  type: 'charge' | 'refund';
  occurred_at: string;
  order_id: string;
  order_number: string | null;
  reference: string;
  amount_kobo: number;
  currency: string;
  status: string;
}

export interface PaymentReconciliationReport {
  period: {
    from: string;
    to: string;
  };
  summary: {
    charge_count: number;
    refund_count: number;
    total_charges_kobo: number;
    total_refunds_kobo: number;
    net_kobo: number;
    currency: string;
  };
  entries: PaymentReconciliationEntry[];
}

export interface PaymentReconciliationResponse {
  data: PaymentReconciliationReport;
}

export interface StoreSettings {
  return_window_days: number;
  currency: string;
  timezone: string;
  payment_provider: 'paystack' | 'flutterwave';
}

export interface StoreSettingsResponse {
  data: StoreSettings;
}

export interface BillingPlan {
  id: string;
  slug: string;
  name: string;
  price_ngn: number;
  product_limit: number;
  staff_limit: number;
  custom_domain: boolean;
}

export interface BillingSubscription {
  id: string;
  tenant_id: string;
  plan_id: string;
  status: string;
  trial_ends_at: string | null;
  current_period_end: string | null;
  plan: BillingPlan | null;
}

export interface BillingSubscriptionResponse {
  data: BillingSubscription;
}

export interface BillingInvoice {
  id: string;
  number: string;
  status: string;
  currency: string;
  subtotal: number;
  tax: number;
  total: number;
  period_start: string | null;
  period_end: string | null;
  created_at: string | null;
}

export interface BillingInvoiceListResponse {
  data: BillingInvoice[];
}

export interface BillingSettings {
  vat_registered: boolean;
  currency: string;
}

export interface BillingSettingsResponse {
  data: BillingSettings;
}

export interface BillingPaymentInitResponse {
  data: {
    authorization_url: string;
    reference: string;
  };
}

export interface BillingActivateResponse {
  data: {
    subscription: BillingSubscription;
    invoice: {
      id: string;
      number: string;
      status: string;
      total: number;
      currency: string;
    } | null;
  };
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
  turnstileToken?: string,
): Promise<LoginResponse> {
  const response = await fetch(`${API_URL}/api/v1/auth/merchant/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({
      email,
      password,
      ...(turnstileToken ? { 'cf-turnstile-response': turnstileToken } : {}),
    }),
  });

  return parseJson<LoginResponse>(response);
}

export interface MerchantHandoffResponse extends LoginResponse {
  tenant_id: string;
}

export async function exchangeMerchantHandoff(handoffToken: string): Promise<MerchantHandoffResponse> {
  const response = await fetch(`${API_URL}/api/v1/auth/merchant/handoff`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ handoff_token: handoffToken }),
  });

  return parseJson<MerchantHandoffResponse>(response);
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

export async function refundOrder(
  tenantId: string,
  orderId: string,
  input?: { amount_kobo?: number; reason?: string },
): Promise<{ refund: RefundResult; order: Order }> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/orders/${encodeURIComponent(orderId)}/refund`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
        'Idempotency-Key': crypto.randomUUID(),
      },
      body: JSON.stringify(input ?? {}),
    },
  );

  const result = await parseJson<RefundOrderResponse>(response);
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

export async function listReturnRequests(tenantId: string): Promise<ReturnRequest[]> {
  const response = await fetch(`${API_URL}/api/v1/commerce/returns`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<ReturnRequestListResponse>(response);
  return result.data;
}

export async function approveReturnRequest(
  tenantId: string,
  returnId: string,
  issueRefund = false,
): Promise<ReturnRequest> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/returns/${encodeURIComponent(returnId)}/approve`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
        'Idempotency-Key': crypto.randomUUID(),
      },
      body: JSON.stringify({ issue_refund: issueRefund }),
    },
  );

  const result = await parseJson<ReturnRequestResponse>(response);
  return result.data;
}

export async function shipReturnRequest(
  tenantId: string,
  returnId: string,
): Promise<ReturnRequest> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/returns/${encodeURIComponent(returnId)}/ship`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
    },
  );

  const result = await parseJson<ReturnRequestResponse>(response);
  return result.data;
}

export async function receiveReturnRequest(
  tenantId: string,
  returnId: string,
): Promise<ReturnRequest> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/returns/${encodeURIComponent(returnId)}/receive`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
        'Idempotency-Key': crypto.randomUUID(),
      },
    },
  );

  const result = await parseJson<ReturnRequestResponse>(response);
  return result.data;
}

export async function rejectReturnRequest(
  tenantId: string,
  returnId: string,
  rejectionReason: string,
): Promise<ReturnRequest> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/returns/${encodeURIComponent(returnId)}/reject`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ rejection_reason: rejectionReason }),
    },
  );

  const result = await parseJson<ReturnRequestResponse>(response);
  return result.data;
}

export async function listDisputes(tenantId: string): Promise<Dispute[]> {
  const response = await fetch(`${API_URL}/api/v1/platform/financial-services/disputes`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<DisputeListResponse>(response);
  return result.data;
}

export async function resolveDispute(
  tenantId: string,
  disputeId: string,
  status: 'won' | 'lost' | 'withdrawn',
): Promise<Dispute> {
  const response = await fetch(
    `${API_URL}/api/v1/platform/financial-services/disputes/${encodeURIComponent(disputeId)}/resolve`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ status }),
    },
  );

  const result = await parseJson<DisputeResponse>(response);
  return result.data;
}

export async function fetchPaymentReconciliation(
  tenantId: string,
  from: string,
  to: string,
): Promise<PaymentReconciliationReport> {
  const params = new URLSearchParams({ from, to });
  const response = await fetch(
    `${API_URL}/api/v1/platform/financial-services/reconciliation?${params.toString()}`,
    {
      headers: tenantHeaders(tenantId),
    },
  );

  const result = await parseJson<PaymentReconciliationResponse>(response);
  return result.data;
}

export async function downloadPaymentReconciliationCsv(
  tenantId: string,
  from: string,
  to: string,
): Promise<void> {
  const params = new URLSearchParams({ from, to });
  const response = await fetch(
    `${API_URL}/api/v1/platform/financial-services/reconciliation/export?${params.toString()}`,
    {
      headers: tenantHeaders(tenantId),
    },
  );

  if (!response.ok) {
    const message = await response.text();
    throw new Error(message || 'Failed to export reconciliation report.');
  }

  const blob = await response.blob();
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `payments-reconciliation-${from}-to-${to}.csv`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

export async function fetchStoreSettings(tenantId: string): Promise<StoreSettings> {
  const response = await fetch(`${API_URL}/api/v1/commerce/storefront/settings`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<StoreSettingsResponse>(response);
  return result.data;
}

export async function updateReturnWindowDays(
  tenantId: string,
  returnWindowDays: number,
): Promise<StoreSettings> {
  const response = await fetch(`${API_URL}/api/v1/commerce/storefront/settings/returns`, {
    method: 'PUT',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ return_window_days: returnWindowDays }),
  });

  const result = await parseJson<StoreSettingsResponse>(response);
  return result.data;
}

export interface PaymentProviderSettings {
  payment_provider: 'paystack' | 'flutterwave';
  currency: string;
}

export interface PaymentProviderSettingsResponse {
  data: PaymentProviderSettings;
}

export async function fetchPaymentProviderSettings(
  tenantId: string,
): Promise<PaymentProviderSettings> {
  const response = await fetch(`${API_URL}/api/v1/commerce/storefront/settings/payments`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<PaymentProviderSettingsResponse>(response);
  return result.data;
}

export async function updatePaymentProvider(
  tenantId: string,
  paymentProvider: 'paystack' | 'flutterwave',
): Promise<PaymentProviderSettings> {
  const response = await fetch(`${API_URL}/api/v1/commerce/storefront/settings/payments`, {
    method: 'PUT',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ payment_provider: paymentProvider }),
  });

  const result = await parseJson<PaymentProviderSettingsResponse>(response);
  return result.data;
}

export interface PaymentProviderCredentialStatus {
  configured: boolean;
  masked_secret_key: string | null;
  uses_platform_key: boolean;
  webhook_hash_configured?: boolean;
}

export interface PaymentCredentialsStatus {
  paystack: PaymentProviderCredentialStatus;
  flutterwave: PaymentProviderCredentialStatus & { webhook_hash_configured: boolean };
}

export interface PaymentCredentialsStatusResponse {
  data: PaymentCredentialsStatus;
}

export async function fetchPaymentCredentials(
  tenantId: string,
): Promise<PaymentCredentialsStatus> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/storefront/settings/payments/credentials`,
    {
      headers: tenantHeaders(tenantId),
    },
  );

  const result = await parseJson<PaymentCredentialsStatusResponse>(response);
  return result.data;
}

export async function updatePaymentCredentials(
  tenantId: string,
  payload: {
    provider: 'paystack' | 'flutterwave';
    secret_key?: string;
    secret_hash?: string;
  },
): Promise<PaymentCredentialsStatus> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/storefront/settings/payments/credentials`,
    {
      method: 'PUT',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    },
  );

  const result = await parseJson<PaymentCredentialsStatusResponse>(response);
  return result.data;
}

export async function fetchBillingSubscription(tenantId: string): Promise<BillingSubscription> {
  const response = await fetch(`${API_URL}/api/v1/platform/billing/subscription`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<BillingSubscriptionResponse>(response);
  return result.data;
}

export async function fetchBillingSettings(tenantId: string): Promise<BillingSettings> {
  const response = await fetch(`${API_URL}/api/v1/platform/billing/settings`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<BillingSettingsResponse>(response);
  return result.data;
}

export async function updateBillingSettings(
  tenantId: string,
  payload: Pick<BillingSettings, 'vat_registered'>,
): Promise<BillingSettings> {
  const response = await fetch(`${API_URL}/api/v1/platform/billing/settings`, {
    method: 'PUT',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const result = await parseJson<BillingSettingsResponse>(response);
  return result.data;
}

export async function listBillingInvoices(tenantId: string): Promise<BillingInvoice[]> {
  const response = await fetch(`${API_URL}/api/v1/platform/billing/invoices`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<BillingInvoiceListResponse>(response);
  return result.data;
}

export async function downloadBillingInvoicePdf(tenantId: string, invoiceId: string): Promise<void> {
  const response = await fetch(
    `${API_URL}/api/v1/platform/billing/invoices/${encodeURIComponent(invoiceId)}/pdf`,
    {
      headers: tenantHeaders(tenantId),
    },
  );

  if (!response.ok) {
    throw new Error('Failed to download invoice PDF.');
  }

  const blob = await response.blob();
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `invoice-${invoiceId}.pdf`;
  link.click();
  URL.revokeObjectURL(url);
}

export async function initializeBillingPayment(
  tenantId: string,
  email: string,
): Promise<{ authorization_url: string; reference: string }> {
  const response = await fetch(
    `${API_URL}/api/v1/platform/billing/subscriptions/${encodeURIComponent(tenantId)}/initialize-payment`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
        'Idempotency-Key': crypto.randomUUID(),
      },
      body: JSON.stringify({ email }),
    },
  );

  const result = await parseJson<BillingPaymentInitResponse>(response);
  return result.data;
}

export async function activateBillingSubscription(
  tenantId: string,
  paystackReference?: string,
): Promise<BillingActivateResponse['data']> {
  const response = await fetch(
    `${API_URL}/api/v1/platform/billing/subscriptions/${encodeURIComponent(tenantId)}/activate`,
    {
      method: 'POST',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        paystack_reference: paystackReference ?? null,
      }),
    },
  );

  const result = await parseJson<BillingActivateResponse>(response);
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

export interface CmsPage {
  id: string;
  title: string;
  slug: string;
  content_type: string;
  status: string;
  seo_title: string | null;
  seo_description: string | null;
  seo_og_image_url: string | null;
  seo_canonical_url: string | null;
  published_at: string | null;
  scheduled_publish_at: string | null;
  scheduled_unpublish_at: string | null;
  body_json: { sections?: Array<Record<string, unknown>> } | null;
}

export interface CmsPageListResponse {
  data: CmsPage[];
}

export async function fetchCmsPages(tenantId: string): Promise<CmsPage[]> {
  const response = await fetch(`${API_URL}/api/v1/content/cms/pages`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<CmsPageListResponse>(response);
  return result.data;
}

export async function createCmsPage(
  tenantId: string,
  payload: {
    title: string;
    slug?: string;
    status?: string;
    seo_title?: string;
    seo_description?: string;
    body_json?: { sections: Array<{ type: string; content: string }> };
    published_at?: string | null;
  },
): Promise<CmsPage> {
  const response = await fetch(`${API_URL}/api/v1/content/cms/pages`, {
    method: 'POST',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const result = await parseJson<{ data: CmsPage }>(response);
  return result.data;
}

export async function updateCmsPage(
  tenantId: string,
  pageId: string,
  payload: {
    title?: string;
    slug?: string;
    status?: string;
    seo_title?: string | null;
    seo_description?: string | null;
    seo_og_image_url?: string | null;
    seo_canonical_url?: string | null;
    body_json?: { sections: Array<Record<string, unknown>> };
    published_at?: string | null;
    scheduled_publish_at?: string | null;
    scheduled_unpublish_at?: string | null;
  },
): Promise<CmsPage> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/pages/${encodeURIComponent(pageId)}`,
    {
      method: 'PUT',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    },
  );

  const result = await parseJson<{ data: CmsPage }>(response);
  return result.data;
}

export async function deleteCmsPage(tenantId: string, pageId: string): Promise<void> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/pages/${encodeURIComponent(pageId)}`,
    {
      method: 'DELETE',
      headers: tenantHeaders(tenantId),
    },
  );

  if (!response.ok && response.status !== 204) {
    throw new Error('Failed to delete page.');
  }
}

export interface CmsBlogPost {
  id: string;
  title: string;
  slug: string;
  excerpt: string | null;
  author_name: string;
  tags: string[] | null;
  featured_image_url: string | null;
  status: string;
  published_at: string | null;
  scheduled_publish_at?: string | null;
  scheduled_unpublish_at?: string | null;
  seo_title?: string | null;
  seo_description?: string | null;
  seo_og_image_url?: string | null;
  seo_canonical_url?: string | null;
  body_json?: { sections?: Array<{ type: string; content?: string }> } | null;
}

export interface CmsBlogPostListResponse {
  data: CmsBlogPost[];
}

export async function fetchCmsBlogPosts(tenantId: string): Promise<CmsBlogPost[]> {
  const response = await fetch(`${API_URL}/api/v1/content/cms/blog-posts`, {
    headers: tenantHeaders(tenantId),
  });

  const result = await parseJson<CmsBlogPostListResponse>(response);
  return result.data;
}

export async function createCmsBlogPost(
  tenantId: string,
  payload: {
    title: string;
    slug?: string;
    author_name: string;
    excerpt?: string;
    tags?: string[];
    status?: string;
    published_at?: string | null;
    body_json?: { sections: Array<{ type: string; content: string }> };
  },
): Promise<CmsBlogPost> {
  const response = await fetch(`${API_URL}/api/v1/content/cms/blog-posts`, {
    method: 'POST',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const result = await parseJson<{ data: CmsBlogPost }>(response);
  return result.data;
}

export async function updateCmsBlogPost(
  tenantId: string,
  postId: string,
  payload: {
    title?: string;
    slug?: string;
    author_name?: string;
    excerpt?: string | null;
    tags?: string[];
    featured_image_url?: string | null;
    seo_title?: string | null;
    seo_description?: string | null;
    seo_og_image_url?: string | null;
    seo_canonical_url?: string | null;
    status?: string;
    published_at?: string | null;
    scheduled_publish_at?: string | null;
    scheduled_unpublish_at?: string | null;
    body_json?: { sections: Array<Record<string, unknown>> };
  },
): Promise<CmsBlogPost> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/blog-posts/${encodeURIComponent(postId)}`,
    {
      method: 'PUT',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    },
  );

  const result = await parseJson<{ data: CmsBlogPost }>(response);
  return result.data;
}

export async function deleteCmsBlogPost(tenantId: string, postId: string): Promise<void> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/blog-posts/${encodeURIComponent(postId)}`,
    {
      method: 'DELETE',
      headers: tenantHeaders(tenantId),
    },
  );

  if (!response.ok && response.status !== 204) {
    throw new Error('Failed to delete blog post.');
  }
}

export interface CmsNavLink {
  label: string;
  href: string;
  open_in_new_tab?: boolean;
}

export interface CmsNavigationMenu {
  id?: string;
  location: string;
  links: CmsNavLink[];
}

export async function fetchCmsNavigation(
  tenantId: string,
  location: 'header' | 'footer',
): Promise<CmsNavigationMenu> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/navigation/${encodeURIComponent(location)}`,
    { headers: tenantHeaders(tenantId) },
  );

  const result = await parseJson<{ data: CmsNavigationMenu }>(response);
  return result.data;
}

export async function updateCmsNavigation(
  tenantId: string,
  location: 'header' | 'footer',
  links: CmsNavLink[],
): Promise<CmsNavigationMenu> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/navigation/${encodeURIComponent(location)}`,
    {
      method: 'PUT',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ links }),
    },
  );

  const result = await parseJson<{ data: CmsNavigationMenu }>(response);
  return result.data;
}

export interface CmsContentVersion {
  id: string;
  version_number: number;
  label: string | null;
  created_at: string | null;
  snapshot_json: Record<string, unknown>;
}

export async function fetchCmsPageVersions(
  tenantId: string,
  pageId: string,
): Promise<CmsContentVersion[]> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/pages/${encodeURIComponent(pageId)}/versions`,
    { headers: tenantHeaders(tenantId) },
  );
  const result = await parseJson<{ data: CmsContentVersion[] }>(response);
  return result.data;
}

export async function restoreCmsPageVersion(
  tenantId: string,
  pageId: string,
  versionId: string,
): Promise<CmsPage> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/pages/${encodeURIComponent(pageId)}/versions/${encodeURIComponent(versionId)}/restore`,
    {
      method: 'POST',
      headers: tenantHeaders(tenantId),
    },
  );
  const result = await parseJson<{ data: CmsPage }>(response);
  return result.data;
}

export async function fetchCmsBlogPostVersions(
  tenantId: string,
  postId: string,
): Promise<CmsContentVersion[]> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/blog-posts/${encodeURIComponent(postId)}/versions`,
    { headers: tenantHeaders(tenantId) },
  );
  const result = await parseJson<{ data: CmsContentVersion[] }>(response);
  return result.data;
}

export async function restoreCmsBlogPostVersion(
  tenantId: string,
  postId: string,
  versionId: string,
): Promise<CmsBlogPost> {
  const response = await fetch(
    `${API_URL}/api/v1/content/cms/blog-posts/${encodeURIComponent(postId)}/versions/${encodeURIComponent(versionId)}/restore`,
    {
      method: 'POST',
      headers: tenantHeaders(tenantId),
    },
  );
  const result = await parseJson<{ data: CmsBlogPost }>(response);
  return result.data;
}

export type CollectionType = 'manual' | 'smart';
export type CollectionStatus = 'draft' | 'published' | 'scheduled';
export type CollectionSortOrder =
  | 'manual'
  | 'best_selling'
  | 'newest'
  | 'price_asc'
  | 'price_desc';

export interface CatalogCollection {
  id: string;
  tenant_id: string;
  title: string;
  slug: string;
  description: string | null;
  type: CollectionType;
  rules_json: Record<string, unknown> | null;
  sort_order: CollectionSortOrder | string;
  status: CollectionStatus;
  published_at: string | null;
  starts_at: string | null;
  ends_at: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface CatalogCollectionInput {
  title: string;
  slug?: string;
  description?: string | null;
  type: CollectionType;
  rules_json?: Record<string, unknown> | null;
  sort_order?: CollectionSortOrder;
  status: CollectionStatus;
  published_at?: string | null;
  starts_at?: string | null;
  ends_at?: string | null;
  product_ids?: string[];
}

export async function fetchCollections(tenantId: string): Promise<CatalogCollection[]> {
  const response = await fetch(`${API_URL}/api/v1/commerce/catalog/collections`, {
    headers: tenantHeaders(tenantId),
  });
  const result = await parseJson<{ data: CatalogCollection[] }>(response);
  return result.data;
}

export async function fetchCollection(
  tenantId: string,
  collectionId: string,
): Promise<CatalogCollection> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/collections/${encodeURIComponent(collectionId)}`,
    { headers: tenantHeaders(tenantId) },
  );
  const result = await parseJson<{ data: CatalogCollection }>(response);
  return result.data;
}

export async function createCollection(
  tenantId: string,
  input: CatalogCollectionInput,
): Promise<CatalogCollection> {
  const response = await fetch(`${API_URL}/api/v1/commerce/catalog/collections`, {
    method: 'POST',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(input),
  });
  const result = await parseJson<{ data: CatalogCollection }>(response);
  return result.data;
}

export async function updateCollection(
  tenantId: string,
  collectionId: string,
  input: CatalogCollectionInput,
): Promise<CatalogCollection> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/collections/${encodeURIComponent(collectionId)}`,
    {
      method: 'PUT',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(input),
    },
  );
  const result = await parseJson<{ data: CatalogCollection }>(response);
  return result.data;
}

export async function deleteCollection(
  tenantId: string,
  collectionId: string,
): Promise<void> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/collections/${encodeURIComponent(collectionId)}`,
    {
      method: 'DELETE',
      headers: tenantHeaders(tenantId),
    },
  );

  if (!response.ok && response.status !== 204) {
    await parseJson(response);
  }
}

export async function syncCollectionProducts(
  tenantId: string,
  collectionId: string,
  productIds: string[],
): Promise<Product[]> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/collections/${encodeURIComponent(collectionId)}/products`,
    {
      method: 'PUT',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ product_ids: productIds }),
    },
  );
  const result = await parseJson<{ data: Product[] }>(response);
  return result.data;
}

export async function fetchCollectionProducts(
  tenantId: string,
  collectionId: string,
): Promise<Product[]> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/collections/${encodeURIComponent(collectionId)}/products?limit=50`,
    { headers: tenantHeaders(tenantId) },
  );
  const result = await parseJson<{ data: Product[] }>(response);
  return result.data;
}

export interface SearchAnalytics {
  top_queries: Array<{ query: string; searches: number; avg_results: number }>;
  zero_result_queries: Array<{ query: string; searches: number }>;
  window_days: number;
}

export interface SearchSynonym {
  id: string;
  tenant_id: string;
  term: string;
  synonym: string;
}

export async function fetchSearchAnalytics(tenantId: string): Promise<SearchAnalytics> {
  const response = await fetch(`${API_URL}/api/v1/commerce/catalog/search/analytics`, {
    headers: tenantHeaders(tenantId),
  });
  const result = await parseJson<{ data: SearchAnalytics }>(response);
  return result.data;
}

export async function fetchSearchSynonyms(tenantId: string): Promise<SearchSynonym[]> {
  const response = await fetch(`${API_URL}/api/v1/commerce/catalog/search/synonyms`, {
    headers: tenantHeaders(tenantId),
  });
  const result = await parseJson<{ data: SearchSynonym[] }>(response);
  return result.data;
}

export async function createSearchSynonym(
  tenantId: string,
  input: { term: string; synonym: string },
): Promise<SearchSynonym> {
  const response = await fetch(`${API_URL}/api/v1/commerce/catalog/search/synonyms`, {
    method: 'POST',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(input),
  });
  const result = await parseJson<{ data: SearchSynonym }>(response);
  return result.data;
}

export async function deleteSearchSynonym(tenantId: string, synonymId: string): Promise<void> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/catalog/search/synonyms/${encodeURIComponent(synonymId)}`,
    {
      method: 'DELETE',
      headers: tenantHeaders(tenantId),
    },
  );

  if (!response.ok && response.status !== 204) {
    await parseJson(response);
  }
}
