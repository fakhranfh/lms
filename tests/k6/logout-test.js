import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

const logoutDuration = new Trend('logout_duration');
const successRate = new Rate('logout_success');
const totalLogouts = new Counter('total_logouts');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const EMAIL = __ENV.TEST_EMAIL || 'loadtest@example.com';
const PASSWORD = __ENV.TEST_PASSWORD || 'password';
const SETUP_SESSIONS = parseInt(__ENV.SETUP_SESSIONS || '20');

export const options = {
  stages: [
    { duration: '5s', target: 5 },
    { duration: '10s', target: 20 },
    { duration: '5s', target: 0 },
  ],
  thresholds: {
    'logout_success': ['rate>0.90'],
    'http_req_failed': ['rate<0.10'],
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

// Pre-create N authenticated sessions before the stress test starts
export function setup() {
  console.log(`Creating ${SETUP_SESSIONS} pre-authenticated sessions...`);
  const sessions = [];

  for (let i = 0; i < SETUP_SESSIONS; i++) {
    const ip = randomIp();

    const page = http.get(`${BASE_URL}/login`, {
      headers: { 'X-Forwarded-For': ip },
      tags: { name: 'setup_login_page' },
    });
    const token = extractCsrf(page.body);

    if (!token) continue;

    // Extract session cookie from GET /login response
    const setCookie = page.headers['Set-Cookie'] || page.headers['set-cookie'] || '';
    const match = setCookie.match(/([a-z0-9_-]+session)=([^;]+)/i);
    const cookieName = match ? match[1] : null;
    const cookieVal = match ? match[2] : null;
    if (!cookieName || !cookieVal) { console.log(`No session cookie in response`); continue; }

    const loginRes = postForm('/login', {
      _token: token, email: EMAIL, password: PASSWORD,
    }, ip);
    if (loginRes.status !== 302) continue;

    const dash = http.get(`${BASE_URL}/dashboard`, {
      headers: { 'X-Forwarded-For': ip },
      tags: { name: 'setup_dashboard' },
    });
    const dashToken = extractCsrf(dash.body);
    if (dashToken) sessions.push({ cookieName, cookieVal, csrf: dashToken });
  }

  console.log(`Done: ${sessions.length} sessions ready`);
  return sessions;
}

export default function (sessions) {
  if (!sessions || sessions.length === 0) return;

  const session = sessions[Math.floor(Math.random() * sessions.length)];
  const ip = randomIp();
  const cookieHeader = `${session.cookieName}=${session.cookieVal}`;

  const dash = http.get(`${BASE_URL}/dashboard`, {
    headers: { 'X-Forwarded-For': ip, 'Cookie': cookieHeader },
    tags: { name: 'get_dashboard' },
  });

  const logoutToken = extractCsrf(dash.body);
  if (!logoutToken) return;

  const start = Date.now();
  const res = postForm('/logout', { _token: logoutToken }, ip, {
    'Cookie': cookieHeader,
  });

  logoutDuration.add(Date.now() - start);
  totalLogouts.add(1);

  const ok = check(res, {
    'logout redirects (302)': (r) => r.status === 302,
    'redirects to login': (r) => (r.headers['Location'] || '').includes('/login'),
  });

  successRate.add(ok);
  sleep(1);
}
