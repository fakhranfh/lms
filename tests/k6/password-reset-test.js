import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';
import { SharedArray } from 'k6/data';

const forgotDuration = new Trend('forgot_duration');
const resetDuration = new Trend('reset_duration');
const forgotSuccess = new Rate('forgot_success');
const resetSuccess = new Rate('reset_success');
const totalResets = new Counter('total_resets');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const TOKENS_FILE = __ENV.TOKENS_FILE || 'tokens.json';

// Load pre-created tokens
const tokens = new SharedArray('tokens', function () {
  try {
    return JSON.parse(open(TOKENS_FILE));
  } catch (e) {
    console.warn(`Could not load tokens: ${e}. Only testing forgot-password.`);
    return [];
  }
});

export const options = {
  stages: [
    { duration: '5s', target: 3 },
    { duration: '10s', target: 10 },
    { duration: '5s', target: 0 },
  ],
  thresholds: {
    'http_req_failed': ['rate<0.50'],
  },
};

function randomIp() {
  return `${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 254) + 1}`;
}

function extractCsrf(body) {
  const m = body.match(/<meta[^>]+content="([^"]+)"[^>]+csrf-token/i)
    || body.match(/<input[^>]+name="_token"[^>]+value="([^"]+)"/i);
  return m ? m[1] : null;
}

function postForm(url, fields, ip, extraHeaders = {}) {
  const payload = Object.entries(fields)
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&');

  return http.post(`${BASE_URL}${url}`, payload, {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Forwarded-For': ip,
      ...extraHeaders,
    },
    redirects: 0,
  });
}

export default function () {
  const ip = randomIp();

  if (tokens.length === 0) return;

  const entry = tokens[Math.floor(Math.random() * tokens.length)];
  const resetUrl = `/reset-password/${entry.token}?email=${encodeURIComponent(entry.email)}`;

  const resetPage = http.get(`${BASE_URL}${resetUrl}`, {
    headers: { 'X-Forwarded-For': ip },
    tags: { name: 'get_reset_page' },
  });

  const csrfToken = extractCsrf(resetPage.body);
  const setCookie = resetPage.headers['Set-Cookie'] || resetPage.headers['set-cookie'] || '';
  const match = setCookie.match(/([a-z0-9_-]+session)=([^;]+)/i);
  const cookieHeader = match ? `${match[1]}=${match[2]}` : '';

  if (!csrfToken) return;

  const start = Date.now();

  const resetRes = postForm('/reset-password', {
    _token: csrfToken,
    token: entry.token,
    email: entry.email,
    password: 'Xy9#mK2$pL7@qR5!vN3&wB8',
    password_confirmation: 'Xy9#mK2$pL7@qR5!vN3&wB8',
  }, ip, cookieHeader ? { 'Cookie': cookieHeader } : {});

  resetDuration.add(Date.now() - start);
  totalResets.add(1);

  const ok = check(resetRes, {
    'reset redirects (302)': (r) => r.status === 302,
  });

  resetSuccess.add(ok);

  sleep(1);
}
