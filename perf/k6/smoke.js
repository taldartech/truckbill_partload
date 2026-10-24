/**
 * k6 smoke — thresholds aligned to the existing PHPUnit 5s report budget
 * (tests/Performance/*). Not an invented production SLO.
 *
 * Usage (manual / dedicated CI):
 *   k6 run -e BASE_URL=http://127.0.0.1:8000 perf/k6/smoke.js
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 1,
  duration: '15s',
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<5000'],
  },
};

const base = __ENV.BASE_URL || 'http://127.0.0.1:8000';

export default function () {
  const res = http.get(`${base}/login`);
  check(res, {
    'login is 200': (r) => r.status === 200,
  });
  sleep(1);
}
