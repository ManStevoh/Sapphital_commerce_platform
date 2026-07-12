const SESSION_KEY = 'scp_storefront_session_id';

function generateUuid(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID();
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
    const random = Math.floor(Math.random() * 16);
    const value = char === 'x' ? random : (random & 0x3) | 0x8;
    return value.toString(16);
  });
}

export function getSessionId(): string {
  if (typeof window === 'undefined') {
    return '';
  }

  const existing = localStorage.getItem(SESSION_KEY);

  if (existing) {
    return existing;
  }

  const sessionId = generateUuid();
  localStorage.setItem(SESSION_KEY, sessionId);

  return sessionId;
}
