import { NextResponse } from 'next/server';

export function GET() {
  return NextResponse.json({
    status: 'ok',
    app: 'storefront',
    timestamp: new Date().toISOString(),
  });
}
