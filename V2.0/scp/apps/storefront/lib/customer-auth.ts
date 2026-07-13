const CUSTOMER_TOKEN_KEY = 'scp_customer_token';
const CUSTOMER_EMAIL_KEY = 'scp_customer_email';

export function getCustomerToken(): string | null {
  if (typeof window === 'undefined') {
    return null;
  }

  return window.localStorage.getItem(CUSTOMER_TOKEN_KEY);
}

export function getCustomerEmail(): string | null {
  if (typeof window === 'undefined') {
    return null;
  }

  return window.localStorage.getItem(CUSTOMER_EMAIL_KEY);
}

export function setCustomerSession(token: string, email: string): void {
  window.localStorage.setItem(CUSTOMER_TOKEN_KEY, token);
  window.localStorage.setItem(CUSTOMER_EMAIL_KEY, email);
}

export function clearCustomerSession(): void {
  window.localStorage.removeItem(CUSTOMER_TOKEN_KEY);
  window.localStorage.removeItem(CUSTOMER_EMAIL_KEY);
}
