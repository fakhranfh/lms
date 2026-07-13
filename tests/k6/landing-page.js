import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

// Test configuration with realistic thresholds
export const options = {
  stages: [
    { duration: '10s', target: 5 },   // Ramp up to 5 VUs
    { duration: '15s', target: 10 },  // Ramp up to 10 VUs
    { duration: '10s', target: 5 },   // Ramp down to 5 VUs
    { duration: '5s', target: 0 },    // Ramp down to 0 VUs
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% requests < 2000ms
    http_req_failed: ['rate<0.05'],    // Less than 5% failure rate
    checks: ['rate>0.95'],             // 95%+ checks pass
  },
};

/**
 * Test landing page
 */
function testLandingPage() {
  const res = http.get(`${BASE_URL}/`);

  check(res, {
    'status is 200': (r) => r.status === 200,
    'page contains heading': (r) => r.body.includes('Solid Foundation for Your App'),
    'page contains login link': (r) => r.body.includes('Log in'),
    'page contains register link': (r) => r.body.includes('Register'),
  });

  sleep(1);
}

/**
 * Test landing page with different user agents
 */
function testWithUserAgent() {
  const userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
  ];

  const ua = userAgents[Math.floor(Math.random() * userAgents.length)];

  const res = http.get(`${BASE_URL}/`, {
    headers: {
      'User-Agent': ua,
    },
  });

  check(res, {
    'status is 200': (r) => r.status === 200,
    'contains content': (r) => r.body.length > 1000,
  });

  sleep(1);
}

/**
 * Test navigation flow
 */
function testNavigation() {
  const homeRes = http.get(`${BASE_URL}/`);
  check(homeRes, {
    'landing page loads': (r) => r.status === 200,
  });
  sleep(0.5);

  const registerRes = http.get(`${BASE_URL}/register`);
  check(registerRes, {
    'register page loads': (r) => r.status === 200,
  });
  sleep(1);
}

/**
 * Main test execution
 */
export default function () {
  const testType = Math.random();

  if (testType < 0.5) {
    testLandingPage();
  } else if (testType < 0.75) {
    testNavigation();
  } else {
    testWithUserAgent();
  }
}
