import http from 'k6/http';
import { check, sleep } from 'k6';

// Configuración de la prueba de estrés
export const options = {
    // Escenarios de carga
    stages: [
        { duration: '30s', target: 10 },   // Subir a 10 usuarios en 30s
        { duration: '1m', target: 50 },    // Subir a 50 usuarios en 1 min
        { duration: '2m', target: 50 },    // Mantener 50 usuarios por 2 min
        { duration: '30s', target: 100 },  // Pico de 100 usuarios
        { duration: '1m', target: 100 },   // Mantener pico
        { duration: '30s', target: 0 },    // Bajar a 0
    ],

    // Umbrales de aceptación
    thresholds: {
        http_req_duration: ['p(95)<500'],  // 95% de requests < 500ms
        http_req_failed: ['rate<0.01'],    // Menos de 1% de errores
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';

export default function () {
    // Test 1: Página principal
    let homeRes = http.get(`${BASE_URL}/`);
    check(homeRes, {
        'home status 200': (r) => r.status === 200,
        'home time < 500ms': (r) => r.timings.duration < 500,
    });

    sleep(1);

    // Test 2: Página de login
    let loginPageRes = http.get(`${BASE_URL}/admin/login`);
    check(loginPageRes, {
        'login page status 200': (r) => r.status === 200,
        'login page time < 500ms': (r) => r.timings.duration < 500,
    });

    sleep(1);

    // Test 3: Health check (si existe)
    let healthRes = http.get(`${BASE_URL}/api/health`, {
        tags: { name: 'HealthCheck' },
    });
    check(healthRes, {
        'health check responds': (r) => r.status === 200 || r.status === 404,
    });

    sleep(Math.random() * 2 + 1); // Sleep aleatorio 1-3 segundos
}

// Función para generar reporte al final
export function handleSummary(data) {
    return {
        'tests/stress/results/summary.json': JSON.stringify(data, null, 2),
        stdout: textSummary(data, { indent: ' ', enableColors: true }),
    };
}

// Helper para el resumen en texto
function textSummary(data, options) {
    const indent = options.indent || '  ';
    let output = '\n=== RESUMEN DE PRUEBA DE ESTRÉS ===\n\n';

    if (data.metrics) {
        output += `${indent}Requests totales: ${data.metrics.http_reqs?.values?.count || 'N/A'}\n`;
        output += `${indent}Requests fallidos: ${data.metrics.http_req_failed?.values?.rate || 'N/A'}\n`;
        output += `${indent}Duración promedio: ${Math.round(data.metrics.http_req_duration?.values?.avg || 0)}ms\n`;
        output += `${indent}Duración p95: ${Math.round(data.metrics.http_req_duration?.values['p(95)'] || 0)}ms\n`;
        output += `${indent}Duración máxima: ${Math.round(data.metrics.http_req_duration?.values?.max || 0)}ms\n`;
    }

    return output;
}
