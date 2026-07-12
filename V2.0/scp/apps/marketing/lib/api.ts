const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

export type PlanSlug = 'starter' | 'growth' | 'pro';

export interface SignupPayload {
  email: string;
  password: string;
  store_name: string;
  plan_slug: PlanSlug;
}

export interface SignupResponse {
  tenant_id: string;
  provisioning_run_id: string;
  status: string;
  poll_url: string;
}

export interface ProvisioningStatus {
  tenant_id: string;
  provisioning_run_id: string;
  status: string;
  steps: Record<string, string>;
  started_at: string | null;
  completed_at: string | null;
  error: string | null;
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

export async function signup(payload: SignupPayload): Promise<SignupResponse> {
  const response = await fetch(`${API_URL}/api/v1/signup`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload),
  });

  return parseJson<SignupResponse>(response);
}

export async function getProvisioningStatus(
  tenantId: string,
): Promise<ProvisioningStatus> {
  const response = await fetch(
    `${API_URL}/api/v1/provisioning/${tenantId}/status`,
    {
      headers: { Accept: 'application/json' },
    },
  );

  return parseJson<ProvisioningStatus>(response);
}

export async function pollProvisioningStatus(
  tenantId: string,
  options?: { intervalMs?: number; maxAttempts?: number },
): Promise<ProvisioningStatus> {
  const intervalMs = options?.intervalMs ?? 2000;
  const maxAttempts = options?.maxAttempts ?? 60;

  for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
    const status = await getProvisioningStatus(tenantId);

    if (status.status === 'completed' || status.status === 'failed') {
      return status;
    }

    await new Promise((resolve) => setTimeout(resolve, intervalMs));
  }

  throw new Error('Provisioning timed out. Please try again later.');
}
