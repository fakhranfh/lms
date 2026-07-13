import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

const resendDuration = new Trend('resend_duration');
const successRate = new Rate('resend_success');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '5s', target: 3 },
    { duration: '10s', target: 10 },
    { duration: '5s', target: 0 },
  ],
  thresholds: {
    'resend_success': ['rate>0.60'],
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

export default function () {
  const ip = randomIp();
  const ts = Date.now();
  const vuid = __VU * 10000 + __ITER;
  const email = `verifytest.${ts}.${vuid}@example.com`;

  // 1. Register
  const regPage = http.get(`${BASE_URL}/register`, {
    headers: { 'X-Forwarded-For': ip },
  });
  const regToken = extractCsrf(regPage.body);
  if (!regToken) return;

  const payload = [
    `_token=${encodeURIComponent(regToken)}`,
    `name=${encodeURIComponent(`Test ${vuid}`)}`,
    `email=${encodeURIComponent(email)}`,
    `password=${encodeURIComponent('Secret!Pass123#Secure')}`,
    `password_confirmation=${encodeURIComponent('Secret!Pass123#Secure')}`,
  ].join('&');

  http.post(`${BASE_URL}/register`, payload, {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Forwarded-For': ip,
    },
  });

  // 2. Visit verify page (k6 cookie jar auto-manages session)
  const verifyPage = http.get(`${BASE_URL}/email/verify`, {
    headers: { 'X-Forwarded-For': ip },
  });
  const csrf = extractCsrf(verifyPage.body);
  if (!csrf) return;

  // 3. Resend verification
  const start = Date.now();

  const resendPayload = `_token=${encodeURIComponent(csrf)}`;
  const res = http.post(`${BASE_URL}/email/verification-notification`, resendPayload, {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Forwarded-For': ip,
    },
    redirects: 0,
  });

  resendDuration.add(Date.now() - start);

  const ok = check(res, {
    'resend redirects (302)': (r) => r.status === 302,
    'back to verify page': (r) =>
      (r.headers['Location'] || '').includes('/email/verify'),
  });

  successRate.add(ok);
  sleep(1);
}

