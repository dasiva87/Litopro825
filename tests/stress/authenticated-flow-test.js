import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Métricas personalizadas
const loginSuccess = new Rate('login_success');
const pageLoadTime = new Trend('page_load_time');

export const options = {
    scenarios: {
        // Escenario 1: Usuarios normales
        normal_users: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '1m', target: 20 },
                { duration: '3m', target: 20 },
                { duration: '1m', target: 0 },
            ],
            gracefulRampDown: '30s',
        },
        // Escenario 2: Pico de carga
        spike_test: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '10s', target: 100 },
                { duration: '30s', target: 100 },
                { duration: '10s', target: 0 },
            ],
            startTime: '5m', // Empieza después de 5 minutos
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<1000', 'p(99)<2000'],
        http_req_failed: ['rate<0.05'],
        login_success: ['rate>0.95'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';

// Credenciales de prueba (usar usuario de demo)
const TEST_USER = {
    email: __ENV.TEST_EMAIL || 'admin@demo.com',
    password: __ENV.TEST_PASSWORD || 'password',
};

export function setup() {
    console.log(`Iniciando prueba contra: ${BASE_URL}`);
    console.log(`Usuario de prueba: ${TEST_USER.email}`);
    return { baseUrl: BASE_URL };
}

export default function (data) {
    let jar = http.cookieJar();

    group('1. Login Flow', function () {
        // Obtener página de login (y CSRF token)
        let loginPage = http.get(`${BASE_URL}/admin/login`);
        pageLoadTime.add(loginPage.timings.duration);

        check(loginPage, {
            'login page loads': (r) => r.status === 200,
        });

        // Extraer CSRF token del HTML
        let csrfToken = loginPage.html().find('input[name="_token"]').attr('value');

        if (csrfToken) {
            // Intentar login
            let loginRes = http.post(`${BASE_URL}/admin/login`, {
                email: TEST_USER.email,
                password: TEST_USER.password,
                _token: csrfToken,
            });

            let loginOk = loginRes.status === 200 || loginRes.status === 302;
            loginSuccess.add(loginOk);

            check(loginRes, {
                'login successful': (r) => r.status === 200 || r.status === 302,
            });
        }

        sleep(1);
    });

    group('2. Dashboard', function () {
        let dashboardRes = http.get(`${BASE_URL}/admin`);
        pageLoadTime.add(dashboardRes.timings.duration);

        check(dashboardRes, {
            'dashboard loads': (r) => r.status === 200,
            'dashboard time < 2s': (r) => r.timings.duration < 2000,
        });

        sleep(2);
    });

    group('3. Cotizaciones List', function () {
        let docsRes = http.get(`${BASE_URL}/admin/documents`);
        pageLoadTime.add(docsRes.timings.duration);

        check(docsRes, {
            'documents list loads': (r) => r.status === 200,
            'documents time < 2s': (r) => r.timings.duration < 2000,
        });

        sleep(1);
    });

    group('4. Contactos List', function () {
        let contactsRes = http.get(`${BASE_URL}/admin/contacts`);
        pageLoadTime.add(contactsRes.timings.duration);

        check(contactsRes, {
            'contacts list loads': (r) => r.status === 200,
        });

        sleep(1);
    });

    group('5. Stock Page', function () {
        let stockRes = http.get(`${BASE_URL}/admin/stock`);
        pageLoadTime.add(stockRes.timings.duration);

        check(stockRes, {
            'stock page loads': (r) => r.status === 200,
        });

        sleep(1);
    });

    // Simular tiempo de lectura/trabajo del usuario
    sleep(Math.random() * 3 + 2);
}

export function teardown(data) {
    console.log('Prueba de estrés completada');
}
