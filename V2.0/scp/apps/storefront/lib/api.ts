import { headers } from 'next/headers';



const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';



export interface TenantSummary {

  id: string;

  slug: string;

  name: string;

  status: string;

}



export interface Product {

  id: string;

  tenant_id: string;

  name: string;

  slug: string;

  price_kobo: number;

  status: string;

  inventory_qty: number;

  tags?: string[] | null;

}



export interface ProductListResponse {

  data: Product[];

}



export interface ProductResponse {

  data: Product;

}



export interface CartItem {

  id: string;

  product_id: string;

  quantity: number;

  unit_price_kobo: number;

  line_total_kobo: number;

}



export interface Cart {

  id: string;

  tenant_id: string;

  session_id: string;

  currency: string;

  items: CartItem[];

  total_kobo: number;

}



export interface CartResponse {

  data: Cart;

}



export interface AddToCartResponse {

  data: {

    item: CartItem & { cart_id?: string };

    cart: Cart;

  };

}



export interface CheckoutSession {

  session_id: string;

  total_kobo: number;

  status: string;

}



export interface CheckoutSessionResponse {

  data: CheckoutSession;

}



export interface PaymentInitialization {

  authorization_url: string;

  reference: string;

}



export interface PaymentInitializationResponse {

  data: PaymentInitialization;

}



export interface ShippingRate {

  id: string;

  zone_id: string;

  name: string;

  type: string;

  price_kobo: number;

  base_price_kobo: number;

  is_free_shipping: boolean;

  estimated_days_min: number | null;

  estimated_days_max: number | null;

}



export interface ShippingRatesResponse {

  data: ShippingRate[];

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

  version: string;

  schema_version: string;

  market: string;

  templates: string[];

  colors: Record<string, string>;

  settings: ThemeSettings;

}



export interface ThemeResponse {

  data: ThemeConfig;

}



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



function newIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID();
  }

  return `idemp-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function moneyHeaders(tenantId: string, idempotencyKey?: string): HeadersInit {
  return {
    ...tenantHeaders(tenantId),
    'Idempotency-Key': idempotencyKey ?? newIdempotencyKey(),
  };
}

function tenantHeaders(tenantId: string, sessionId?: string): HeadersInit {

  const headers: Record<string, string> = {

    Accept: 'application/json',

    'X-Tenant-ID': tenantId,

  };



  if (sessionId) {

    headers['X-Session-ID'] = sessionId;

  }



  return headers;

}



export function formatNgn(kobo: number): string {

  return new Intl.NumberFormat('en-NG', {

    style: 'currency',

    currency: 'NGN',

    minimumFractionDigits: 2,

  }).format(kobo / 100);

}



export async function resolveTenantBySlug(slug: string): Promise<TenantSummary> {

  const response = await fetch(

    `${API_URL}/api/v1/platform/tenancy/tenants/by-slug/${encodeURIComponent(slug)}`,

    {

      headers: { Accept: 'application/json' },

      next: { revalidate: 60 },

    },

  );



  return parseJson<TenantSummary>(response);

}



export async function resolveTenantId(tenantSlug?: string): Promise<string> {

  const envTenantId = process.env.NEXT_PUBLIC_TENANT_ID;



  if (envTenantId) {

    return envTenantId;

  }



  let slug = tenantSlug;



  if (!slug) {

    const requestHeaders = await headers();

    slug = requestHeaders.get('x-tenant-slug') ?? undefined;

  }



  if (!slug) {

    throw new Error('Tenant context not resolved. Set NEXT_PUBLIC_TENANT_ID or use a tenant subdomain.');

  }



  const tenant = await resolveTenantBySlug(slug);

  return tenant.id;

}



export async function fetchProducts(tenantSlug?: string): Promise<Product[]> {

  const tenantId = await resolveTenantId(tenantSlug);



  const response = await fetch(`${API_URL}/api/v1/commerce/catalog/products`, {

    headers: tenantHeaders(tenantId),

    next: { revalidate: 30 },

  });



  const result = await parseJson<ProductListResponse>(response);

  return result.data.filter((product) => product.status === 'published');

}



export async function fetchProduct(

  productId: string,

  tenantSlug?: string,

): Promise<Product> {

  const tenantId = await resolveTenantId(tenantSlug);



  const response = await fetch(

    `${API_URL}/api/v1/commerce/catalog/products/${encodeURIComponent(productId)}`,

    {

      headers: tenantHeaders(tenantId),

      next: { revalidate: 30 },

    },

  );



  const result = await parseJson<ProductResponse>(response);

  return result.data;

}



export async function fetchRelatedProducts(

  productId: string,

  tenantSlug?: string,

): Promise<Product[]> {

  try {

    const tenantId = await resolveTenantId(tenantSlug);

    const response = await fetch(

      `${API_URL}/api/v1/commerce/catalog/products/${encodeURIComponent(productId)}/related?limit=4`,

      {

        headers: tenantHeaders(tenantId),

        next: { revalidate: 60 },

      },

    );

    if (!response.ok) {

      return [];

    }

    const result = await parseJson<ProductListResponse>(response);

    return result.data;

  } catch {

    return [];

  }

}



export interface CatalogCollectionSummary {

  id: string;

  tenant_id: string;

  title: string;

  slug: string;

  description: string | null;

  type: string;

  status: string;

}



export async function fetchCollectionBySlug(

  slug: string,

  tenantSlug?: string,

  limit = 24,

): Promise<{

  collection: CatalogCollectionSummary;

  products: Product[];

  nextCursor: string | null;

} | null> {

  try {

    const tenantId = await resolveTenantId(tenantSlug);

    const response = await fetch(

      `${API_URL}/api/v1/commerce/catalog/collections/by-slug/${encodeURIComponent(slug)}?limit=${limit}`,

      {

        headers: tenantHeaders(tenantId),

        next: { revalidate: 60 },

      },

    );

    if (response.status === 404) {

      return null;

    }

    const result = await parseJson<{

      data: { collection: CatalogCollectionSummary; products: Product[] };

      meta: { next_cursor: string | null };

    }>(response);

    return {

      collection: result.data.collection,

      products: result.data.products,

      nextCursor: result.meta.next_cursor,

    };

  } catch {

    return null;

  }

}



export async function fetchTheme(tenantId: string): Promise<ThemeConfig> {

  const response = await fetch(`${API_URL}/api/v1/commerce/storefront/theme`, {

    headers: tenantHeaders(tenantId),

    next: { revalidate: 300 },

  });



  const result = await parseJson<ThemeResponse>(response);

  return result.data;

}



export async function getCart(sessionId: string, tenantId: string): Promise<Cart> {

  const response = await fetch(`${API_URL}/api/v1/commerce/cart`, {

    headers: tenantHeaders(tenantId, sessionId),

    cache: 'no-store',

  });



  const result = await parseJson<CartResponse>(response);

  return result.data;

}



export async function addToCart(

  productId: string,

  quantity: number,

  sessionId: string,

  tenantId: string,

): Promise<AddToCartResponse> {

  const response = await fetch(`${API_URL}/api/v1/commerce/cart/items`, {

    method: 'POST',

    headers: {

      ...tenantHeaders(tenantId, sessionId),

      'Content-Type': 'application/json',

    },

    body: JSON.stringify({ product_id: productId, quantity }),

  });



  return parseJson<AddToCartResponse>(response);

}



export async function createCheckout(
  cartId: string,
  tenantId: string,
  idempotencyKey?: string,
  turnstileToken?: string,
): Promise<CheckoutSession> {
  const response = await fetch(`${API_URL}/api/v1/commerce/checkout/sessions`, {
    method: 'POST',
    headers: {
      ...moneyHeaders(tenantId, idempotencyKey),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      cart_id: cartId,
      ...(turnstileToken ? { 'cf-turnstile-response': turnstileToken } : {}),
    }),
  });



  const result = await parseJson<CheckoutSessionResponse>(response);

  return result.data;

}



export interface CheckoutSettings {
  payment_provider: 'paystack' | 'flutterwave';
  currency: string;
}

export interface CheckoutSettingsResponse {
  data: CheckoutSettings;
}

export async function fetchCheckoutSettings(tenantId: string): Promise<CheckoutSettings> {
  const response = await fetch(`${API_URL}/api/v1/commerce/storefront/checkout-settings`, {
    headers: tenantHeaders(tenantId),
    cache: 'no-store',
  });

  const result = await parseJson<CheckoutSettingsResponse>(response);
  return result.data;
}

export async function initializePayment(
  checkoutSessionId: string,
  email: string,
  tenantId: string,
  idempotencyKey?: string,
): Promise<PaymentInitialization> {
  const response = await fetch(
    `${API_URL}/api/v1/platform/financial-services/payments/initialize`,
    {
      method: 'POST',
      headers: {
        ...moneyHeaders(tenantId, idempotencyKey),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        checkout_session_id: checkoutSessionId,
        email,
      }),
    },
  );



  const result = await parseJson<PaymentInitializationResponse>(response);

  return result.data;

}



export async function getShippingRates(

  orderTotalKobo: number,

  tenantId: string,

): Promise<ShippingRate[]> {

  const response = await fetch(

    `${API_URL}/api/v1/commerce/shipping/rates?order_total_kobo=${orderTotalKobo}`,

    {

      headers: tenantHeaders(tenantId),

      cache: 'no-store',

    },

  );



  const result = await parseJson<ShippingRatesResponse>(response);

  return result.data;

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
  order_number: string;
  status: string;
  total_kobo: number;
  customer_email: string | null;
  items: OrderItem[];
}

export interface OrderResponse {
  data: Order;
}

export interface PaymentVerification {
  status: string;
  reference: string;
  checkout_session_id: string;
  order_id?: string;
}

export interface PaymentVerificationResponse {
  data: PaymentVerification;
}

export interface ShippingAddress {
  line1: string;
  city: string;
  state: string;
  lga?: string;
}

export async function updateCheckoutSession(
  sessionId: string,
  tenantId: string,
  input: {
    customer_email?: string;
    customer_phone?: string;
    shipping_rate_id?: string | null;
    shipping_address?: ShippingAddress;
  },
): Promise<CheckoutSession> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/checkout/sessions/${encodeURIComponent(sessionId)}`,
    {
      method: 'PATCH',
      headers: {
        ...tenantHeaders(tenantId),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(input),
    },
  );

  const result = await parseJson<CheckoutSessionResponse>(response);
  return result.data;
}

export async function verifyPayment(
  reference: string,
  tenantId: string,
  idempotencyKey?: string,
): Promise<PaymentVerification> {
  const response = await fetch(
    `${API_URL}/api/v1/platform/financial-services/payments/verify`,
    {
      method: 'POST',
      headers: {
        ...moneyHeaders(tenantId, idempotencyKey),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ reference }),
    },
  );

  const result = await parseJson<PaymentVerificationResponse>(response);
  return result.data;
}

export async function fetchOrder(orderId: string, tenantId: string): Promise<Order> {
  const response = await fetch(
    `${API_URL}/api/v1/commerce/orders/${encodeURIComponent(orderId)}`,
    {
      headers: tenantHeaders(tenantId),
      cache: 'no-store',
    },
  );

  const result = await parseJson<OrderResponse>(response);
  return result.data;
}

export interface GuestReturnOrderItem {
  id: string;
  product_name: string;
  quantity: number;
}

export interface GuestReturnLookup {
  order_id: string;
  order_number: string;
  items: GuestReturnOrderItem[];
}

export interface GuestReturnLookupResponse {
  data: GuestReturnLookup;
}

export interface GuestReturnRequest {
  id: string;
  status: string;
  reason: string;
}

export interface GuestReturnRequestResponse {
  data: GuestReturnRequest;
}

export async function lookupGuestReturnOrder(
  tenantId: string,
  orderNumber: string,
  customerEmail: string,
): Promise<GuestReturnLookup> {
  const response = await fetch(`${API_URL}/api/v1/commerce/returns/guest/lookup`, {
    method: 'POST',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      order_number: orderNumber,
      customer_email: customerEmail,
    }),
  });

  const result = await parseJson<GuestReturnLookupResponse>(response);
  return result.data;
}

export async function submitGuestReturnRequest(
  tenantId: string,
  input: {
    order_number: string;
    customer_email: string;
    reason: string;
    notes?: string;
    lines: Array<{ order_item_id: string; quantity: number }>;
  },
): Promise<GuestReturnRequest> {
  const response = await fetch(`${API_URL}/api/v1/commerce/returns/guest`, {
    method: 'POST',
    headers: {
      ...tenantHeaders(tenantId),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(input),
  });

  const result = await parseJson<GuestReturnRequestResponse>(response);
  return result.data;
}

export interface CmsPage {
  id: string;
  title: string;
  slug: string;
  status: string;
  seo_title: string | null;
  seo_description: string | null;
  seo_og_image_url: string | null;
  seo_canonical_url: string | null;
  body_json: { sections?: Array<{ type: string; content?: string }> } | null;
}

export interface CmsPageSummary {
  id: string;
  title: string;
  slug: string;
  published_at: string | null;
  updated_at: string | null;
}

export async function fetchPublishedCmsPages(tenantSlug?: string): Promise<CmsPageSummary[]> {
  try {
    const tenantId = await resolveTenantId(tenantSlug);
    const response = await fetch(`${API_URL}/api/v1/content/cms/pages/published`, {
      headers: tenantHeaders(tenantId),
      next: { revalidate: 300 },
    });

    if (!response.ok) {
      return [];
    }

    const result = await parseJson<{ data: CmsPageSummary[] }>(response);
    return result.data;
  } catch {
    return [];
  }
}

export async function fetchCmsPageBySlug(
  slug: string,
  tenantSlug?: string,
): Promise<CmsPage | null> {
  try {
    const tenantId = await resolveTenantId(tenantSlug);
    const response = await fetch(
      `${API_URL}/api/v1/content/cms/pages/by-slug/${encodeURIComponent(slug)}`,
      {
        headers: tenantHeaders(tenantId),
        next: { revalidate: 60 },
      },
    );

    if (response.status === 404) {
      return null;
    }

    const result = await parseJson<{ data: CmsPage }>(response);
    return result.data;
  } catch {
    return null;
  }
}

export interface CmsBlogPostSummary {
  id: string;
  title: string;
  slug: string;
  excerpt: string | null;
  author_name: string;
  tags: string[] | null;
  featured_image_url: string | null;
  published_at: string | null;
}

export interface CmsBlogPostPage {
  data: CmsBlogPostSummary[];
  meta: {
    limit: number;
    next_cursor: string | null;
  };
}

export interface CmsBlogPost extends CmsBlogPostSummary {
  seo_title: string | null;
  seo_description: string | null;
  seo_og_image_url: string | null;
  seo_canonical_url: string | null;
  body_json: { sections?: Array<{ type: string; content?: string }> } | null;
}

export interface CmsNavLink {
  label: string;
  href: string;
  open_in_new_tab?: boolean;
}

export async function fetchPublishedBlogPosts(
  tenantSlug?: string,
): Promise<CmsBlogPostSummary[]> {
  const page = await fetchPublishedBlogPostPage(tenantSlug, { limit: 50 });

  return page.data;
}

export async function fetchPublishedBlogPostPage(
  tenantSlug?: string,
  options: { limit?: number; cursor?: string } = {},
): Promise<CmsBlogPostPage> {
  try {
    const tenantId = await resolveTenantId(tenantSlug);
    const params = new URLSearchParams();

    if (options.limit) {
      params.set('limit', String(options.limit));
    }

    if (options.cursor) {
      params.set('cursor', options.cursor);
    }

    const query = params.toString();
    const response = await fetch(
      `${API_URL}/api/v1/content/cms/blog-posts/published${query ? `?${query}` : ''}`,
      {
        headers: tenantHeaders(tenantId),
        next: { revalidate: 60 },
      },
    );

    if (!response.ok) {
      return { data: [], meta: { limit: options.limit ?? 10, next_cursor: null } };
    }

    const result = await parseJson<CmsBlogPostPage>(response);
    return result;
  } catch {
    return { data: [], meta: { limit: options.limit ?? 10, next_cursor: null } };
  }
}

export async function fetchBlogPostBySlug(
  slug: string,
  tenantSlug?: string,
): Promise<CmsBlogPost | null> {
  try {
    const tenantId = await resolveTenantId(tenantSlug);
    const response = await fetch(
      `${API_URL}/api/v1/content/cms/blog-posts/by-slug/${encodeURIComponent(slug)}`,
      {
        headers: tenantHeaders(tenantId),
        next: { revalidate: 60 },
      },
    );

    if (response.status === 404) {
      return null;
    }

    const result = await parseJson<{ data: CmsBlogPost }>(response);
    return result.data;
  } catch {
    return null;
  }
}

export async function fetchRelatedBlogPosts(
  postId: string,
  tenantSlug?: string,
): Promise<CmsBlogPostSummary[]> {
  try {
    const tenantId = await resolveTenantId(tenantSlug);
    const response = await fetch(
      `${API_URL}/api/v1/content/cms/blog-posts/${encodeURIComponent(postId)}/related?limit=3`,
      {
        headers: tenantHeaders(tenantId),
        next: { revalidate: 300 },
      },
    );

    if (!response.ok) {
      return [];
    }

    const result = await parseJson<{ data: CmsBlogPostSummary[] }>(response);
    return result.data;
  } catch {
    return [];
  }
}

export async function fetchStoreNavigation(
  location: 'header' | 'footer',
  tenantSlug?: string,
): Promise<CmsNavLink[]> {
  try {
    const tenantId = await resolveTenantId(tenantSlug);
    const response = await fetch(
      `${API_URL}/api/v1/content/cms/navigation/${encodeURIComponent(location)}`,
      {
        headers: tenantHeaders(tenantId),
        next: { revalidate: 300 },
      },
    );

    if (!response.ok) {
      return [];
    }

    const result = await parseJson<{ data: { links: CmsNavLink[] } }>(response);
    return result.data.links ?? [];
  } catch {
    return [];
  }
}

export function blogBodyText(post: CmsBlogPost): string {
  const sections = post.body_json?.sections ?? [];
  const richText = sections
    .filter((section) => section.type === 'rich-text' && typeof section.content === 'string')
    .map((section) => section.content as string)
    .join('\n\n')
    .trim();

  return richText || post.excerpt || post.title;
}

export async function fetchBlogFeedXml(tenantSlug?: string): Promise<string | null> {
  try {
    const tenantId = await resolveTenantId(tenantSlug);
    const response = await fetch(`${API_URL}/api/v1/content/cms/blog/feed.xml`, {
      headers: tenantHeaders(tenantId),
      next: { revalidate: 300 },
    });

    if (!response.ok) {
      return null;
    }

    return await response.text();
  } catch {
    return null;
  }
}
