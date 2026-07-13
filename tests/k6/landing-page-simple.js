import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

// Test configuration
export const options = {
  stages: [
    { duration: '10s', target: 5 }, // Ramp up to 5 users
    { duration: '15s', target: 10 }, // Ramp up to 10 users
    { duration: '10s', target: 5 }, // Ramp down to 5 users
  ],
};

export default function () {
  const res = http.get(`${BASE_URL}/`);

  check(res, {
    'status is 200': (r) => r.status === 200,
    'page contains heading': (r) => r.body.includes('Solid Foundation for Your App'),
    'page contains login link': (r) => r.body.includes('Log in'),
    'page contains register link': (r) => r.body.includes('Register'),
    'response time < 1000ms': (r) => r.timings.duration < 1000,
  });

  sleep(1);
}
