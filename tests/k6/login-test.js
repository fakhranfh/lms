import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

const loginDuration = new Trend('login_duration');
const successRate = new Rate('login_success');
const totalLogins = new Counter('total_logins');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const VALID_EMAIL = __ENV.TEST_EMAIL || 'loadtest@example.com';
const VALID_PASSWORD = __ENV.TEST_PASSWORD || 'password';

export const options = {
  stages: [
    { duration: '5s', target: 5 },
    { duration: '10s', target: 20 },
    { duration: '5s', target: 0 },
  ],
  thresholds: {
    'login_success': ['rate>0.70'],
    'http_req_failed': ['rate<0.30'],
  },
};

function randomIp() {
  return `${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 254) + 1}`;
}

function getCsrfToken(ip) {
  const res = http.get(`${BASE_URL}/login`, {
    headers: { 'X-Forwarded-For': ip },
    tags: { name: 'get_login_page' },
  });

  const metaMatch = res.body.match(/<meta[^>]+content="([^"]+)"[^>]+csrf-token/i);
  if (metaMatch) return metaMatch[1];

  const inputMatch = res.body.match(/<input[^>]+name="_token"[^>]+value="([^"]+)"/i);
  if (inputMatch) return inputMatch[1];

  return null;
}

function attemptLogin(token, ip) {
  const payload = [
    `_token=${encodeURIComponent(token)}`,
    `email=${encodeURIComponent(VALID_EMAIL)}`,
    `password=${encodeURIComponent(VALID_PASSWORD)}`,
  ].join('&');

  const start = Date.now();

  const res = http.post(`${BASE_URL}/login`, payload, {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Forwarded-For': ip,
    },
    redirects: 0,
    tags: { name: 'post_login' },
  });

  loginDuration.add(Date.now() - start);
  totalLogins.add(1);

  return res;
}

export default function () {
  const ip = randomIp();

  group('01-get-csrf', () => {
    const token = getCsrfToken(ip);

    check(token, {
      'CSRF token obtained': (t) => t !== null && t.length > 10,
    });

    if (!token) return;

    group('02-login', () => {
      const res = attemptLogin(token, ip);

      const ok = check(res, {
        'login redirects (302)': (r) => r.status === 302,
        'redirects to dashboard': (r) => {
          const loc = r.headers['Location'] || '';
          return loc.includes('/dashboard') || loc.includes('/email/verify');
        },
      });

      successRate.add(ok);
    });
  });

  sleep(1);
}
