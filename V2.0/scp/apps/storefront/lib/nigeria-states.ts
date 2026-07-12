export const NIGERIA_STATES = [
  'Abia',
  'Adamawa',
  'Akwa Ibom',
  'Anambra',
  'Bauchi',
  'Bayelsa',
  'Benue',
  'Borno',
  'Cross River',
  'Delta',
  'Ebonyi',
  'Edo',
  'Ekiti',
  'Enugu',
  'FCT',
  'Gombe',
  'Imo',
  'Jigawa',
  'Kaduna',
  'Kano',
  'Katsina',
  'Kebbi',
  'Kogi',
  'Kwara',
  'Lagos',
  'Nasarawa',
  'Niger',
  'Ogun',
  'Ondo',
  'Osun',
  'Oyo',
  'Plateau',
  'Rivers',
  'Sokoto',
  'Taraba',
  'Yobe',
  'Zamfara',
] as const;

export function isValidNgPhone(phone: string): boolean {
  const normalized = phone.replace(/\s+/g, '');

  return /^(\+234|0)[789][01]\d{8}$/.test(normalized);
}

export function normalizeNgPhone(phone: string): string {
  const trimmed = phone.replace(/\s+/g, '');

  if (trimmed.startsWith('0')) {
    return `+234${trimmed.slice(1)}`;
  }

  return trimmed;
}
