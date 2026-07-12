const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

export interface LoginResponse {
  token: string;
  token_type: string;
}

export interface Tenant {
  id: string;
  slug: string;
  name: string;
  status: string;
  country: string;
  created_at: string | null;
}

export interface TenantListResponse {
  data: Tenant[];
  meta: { total: number };
}

const TOKEN_KEY = 'scp_platform_admin_token';

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

export function storeToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY);
}

export async function platformLogin(
  email: string,
  password: string,
): Promise<LoginResponse> {
  const response = await fetch(`${API_URL}/api/v1/auth/platform/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  });

  return parseJson<LoginResponse>(response);
}

export async function fetchTenants(token: string): Promise<TenantListResponse> {
  const response = await fetch(`${API_URL}/api/v1/platform/tenants`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });

  return parseJson<TenantListResponse>(response);
}

export async function updateTenantStatus(
  token: string,
  tenantId: string,
  status: 'active' | 'suspended',
): Promise<Tenant> {
  const response = await fetch(
    `${API_URL}/api/v1/platform/tenants/${encodeURIComponent(tenantId)}/status`,
    {
      method: 'PATCH',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ status }),
    },
  );

  const result = await parseJson<{ data: Tenant }>(response);
  return result.data;
}
