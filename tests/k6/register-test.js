import http from 'k6/http';
import { check, sleep, group, fail } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

const registrationDuration = new Trend('registration_duration');
const successRate = new Rate('registration_success');
const totalRegistrations = new Counter('total_registrations');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '5s', target: 3 },
    { duration: '10s', target: 10 },
    { duration: '5s', target: 0 },
  ],
  thresholds: {
    'registration_success': ['rate>0.60'],
    'http_req_failed': ['rate<0.50'],
  },
};

function generateUser(index) {
  const ts = Date.now();
  const rnd = Math.random().toString(36).substring(2, 8);

  return {
    name: `Test User ${ts}-${index}`,
    email: `testuser.${ts}.${index}.${rnd}@example.com`,
    password: 'Secret!Pass123#Secure',
    password_confirmation: 'Secret!Pass123#Secure',
  };
}

function getCsrfToken() {
  const res = http.get(`${BASE_URL}/register`, {
    tags: { name: 'get_register_page' },
  });

  const metaMatch = res.body.match(/<meta[^>]+content="([^"]+)"[^>]+csrf-token/i);
  if (metaMatch) return metaMatch[1];

  const inputMatch = res.body.match(/<input[^>]+name="_token"[^>]+value="([^"]+)"/i);
  if (inputMatch) return inputMatch[1];

  return null;
}

function registerUser(token, userData) {
  const payload = [
    `_token=${encodeURIComponent(token)}`,
    `name=${encodeURIComponent(userData.name)}`,
    `email=${encodeURIComponent(userData.email)}`,
    `password=${encodeURIComponent(userData.password)}`,
    `password_confirmation=${encodeURIComponent(userData.password_confirmation)}`,
  ].join('&');

  const start = Date.now();

  const res = http.post(`${BASE_URL}/register`, payload, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    redirects: 0,
    tags: { name: 'post_register' },
  });

  registrationDuration.add(Date.now() - start);
  totalRegistrations.add(1);

  return res;
}

export default function () {
  group('01-get-csrf', () => {
    const token = getCsrfToken();

    check(token, {
      'CSRF token obtained': (t) => t !== null && t.length > 10,
    });

    if (!token) {
      fail('Could not extract CSRF token from registration page');
    }

    group('02-register', () => {
      const user = generateUser(__VU * 1000 + __ITER);
      const res = registerUser(token, user);

      const ok = check(res, {
        'redirects (302)': (r) => r.status === 302,
        'redirects to app': (r) => {
          const loc = r.headers['Location'] || '';
          return loc.includes('/dashboard') || loc.includes('/email/verify');
        },
      });

      successRate.add(ok);
    });
  });

  sleep(1);
}
