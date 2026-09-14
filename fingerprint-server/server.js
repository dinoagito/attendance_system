/**
 * ZKTeco ZK9500 Fingerprint Scanner Server
 * 
 * This server handles fingerprint capture, enrollment, and verification
 * for the Attendance Monitoring System.
 * 
 * @author Attendance System
 * @version 1.0.0
 */

require('dotenv').config();
const express = require('express');
const cors = require('cors');
const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');
const WebSocket = require('ws');
const FingerprintService = require('./services/ZKFingerprintService');
const DatabaseService = require('./services/DatabaseService');
const routes = require('./routes');

const app = express();
const PORT = process.env.PORT || 3001;
const RETRY_DELAY_MS = 5000;

// HTTPS dual-mode: use TLS when FINGERPRINT_SSL=true and cert files exist, else fall back to HTTP
const useHttpsEnv = String(process.env.FINGERPRINT_SSL || 'true').toLowerCase();
const wantHttps = useHttpsEnv !== 'false' && useHttpsEnv !== '0';
const tlsKeyPath = process.env.TLS_KEY_PATH || path.join(__dirname, 'certs', '127.0.0.1+2-key.pem');
const tlsCertPath = process.env.TLS_CERT_PATH || path.join(__dirname, 'certs', '127.0.0.1+2.pem');
let server;
let isHttps = false;
if (wantHttps && fs.existsSync(tlsKeyPath) && fs.existsSync(tlsCertPath)) {
    try {
        const tlsOptions = {
            key: fs.readFileSync(tlsKeyPath),
            cert: fs.readFileSync(tlsCertPath)
        };
        server = https.createServer(tlsOptions, app);
        isHttps = true;
    } catch (err) {
        console.warn(`⚠️  Failed to load TLS certs (${tlsKeyPath}, ${tlsCertPath}): ${err.message}`);
        console.warn('   Falling back to HTTP');
        server = http.createServer(app);
    }
} else {
    if (wantHttps) {
        console.log(`ℹ️  TLS certs not found (${tlsKeyPath}, ${tlsCertPath}) — running in HTTP fallback mode`);
    }
    server = http.createServer(app);
}

// WebSocket server for real-time communication with browser (works with both http and https)
const wss = new WebSocket.Server({ server, path: '/ws' });

// Middleware
const defaultOrigins = [
    'http://localhost:8000',
    'http://127.0.0.1:8000'
];
const configuredOrigins = process.env.CORS_ORIGINS
    ? process.env.CORS_ORIGINS.split(',').map(s => s.trim()).filter(Boolean)
    : defaultOrigins;
const allowedOrigins = [...new Set([...defaultOrigins, ...configuredOrigins])];

// Private Network Access — allow the HTTPS Railway page to reach the
// loopback fingerprint-server. Chrome sends `Access-Control-Request-Private-Network: true`
// on the OPTIONS preflight for public → loopback; we must echo
// `Access-Control-Allow-Private-Network: true`, otherwise Chrome blocks
// with "Permission denied ... loopback address space" even though CORS origin is allowed.
app.use((req, res, next) => {
    if (req.headers['access-control-request-private-network']) {
        res.setHeader('Access-Control-Allow-Private-Network', 'true');
    }
    next();
});

app.use(cors({
    origin: (origin, callback) => {
        if (!origin) return callback(null, true);
        if (allowedOrigins.includes(origin)) return callback(null, true);
        return callback(null, false);
    },
    credentials: true,
    allowedHeaders: ['Content-Type', 'Authorization', 'Access-Control-Request-Private-Network'],
    exposedHeaders: ['Access-Control-Allow-Private-Network']
}));
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// Store WebSocket connections
const clients = new Set();

wss.on('connection', (ws) => {
    console.log('🔌 New WebSocket client connected');
    clients.add(ws);

    ws.on('message', async (message) => {
        try {
            const data = JSON.parse(message);
            await handleWebSocketMessage(ws, data);
        } catch (error) {
            console.error('WebSocket message error:', error);
            ws.send(JSON.stringify({ type: 'error', message: error.message }));
        }
    });

    ws.on('close', () => {
        console.log('🔌 WebSocket client disconnected');
        clients.delete(ws);
    });

    // Send connection confirmation
    ws.send(JSON.stringify({ type: 'connected', message: 'Connected to fingerprint server' }));
});

/**
 * Handle WebSocket messages
 */
async function handleWebSocketMessage(ws, data) {
    const fingerprintService = FingerprintService.getInstance();

    switch (data.type) {
        case 'start_capture':
            // Start fingerprint capture mode
            ws.send(JSON.stringify({ type: 'status', message: 'Place your finger on the scanner...' }));
            try {
                const result = await fingerprintService.captureFingerprint();
                ws.send(JSON.stringify({ type: 'capture_result', ...result }));
            } catch (error) {
                ws.send(JSON.stringify({ type: 'capture_error', message: error.message }));
            }
            break;

        case 'verify':
            // Verify fingerprint against database
            try {
                const verifyResult = await fingerprintService.verifyFingerprint(null, { allowAttendance: true });
                ws.send(JSON.stringify({ type: 'verify_result', ...verifyResult }));
            } catch (error) {
                ws.send(JSON.stringify({ type: 'verify_error', message: error.message }));
            }
            break;

        case 'enroll':
            // Enroll new fingerprint for employee
            try {
                const enrollResult = await fingerprintService.enrollFingerprint(data.employeeId);
                ws.send(JSON.stringify({ type: 'enroll_result', ...enrollResult }));
            } catch (error) {
                ws.send(JSON.stringify({ type: 'enroll_error', message: error.message }));
            }
            break;

        case 'scanner_status':
            // Check scanner status
            const status = fingerprintService.getDeviceStatus();
            ws.send(JSON.stringify({ type: 'scanner_status', ...status }));
            break;

        default:
            ws.send(JSON.stringify({ type: 'error', message: 'Unknown command' }));
    }
}

/**
 * Broadcast message to all connected clients
 */
function broadcast(message) {
    const data = JSON.stringify(message);
    clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(data);
        }
    });
}

// Make broadcast available globally
global.broadcast = broadcast;

// API Routes
app.use('/api', routes);

// Health check endpoint
app.get('/health', (req, res) => {
    const fingerprintService = FingerprintService.getInstance();
    res.json({
        status: 'ok',
        scanner: fingerprintService.getDeviceStatus(),
        timestamp: new Date().toISOString()
    });
});

// Error handling middleware
app.use((err, req, res, next) => {
    console.error('Server Error:', err);
    res.status(500).json({
        success: false,
        message: err.message || 'Internal server error'
    });
});

async function initializeServices() {
    try {
        // Initialize database connection
        await DatabaseService.initialize();
        console.log('✅ Database connected');

        // Initialize fingerprint scanner
        const fingerprintService = FingerprintService.getInstance();
        const initResult = await fingerprintService.initialize();
        
        if (initResult.success) {
            console.log('✅ Fingerprint scanner initialized');
        } else {
            console.warn('⚠️  Fingerprint scanner not available:', initResult.message);
            console.log('   Running in simulation mode for development');
        }
    } catch (error) {
        console.error('❌ Service initialization failed:', error.message);
        console.log(`↻ Retrying service initialization in ${RETRY_DELAY_MS / 1000}s...`);
        setTimeout(initializeServices, RETRY_DELAY_MS);
    }
}

// Initialize and start server
async function startServer() {
    server.once('error', (error) => {
        if (error.code === 'EADDRINUSE') {
            console.error(`❌ Port ${PORT} is already in use. Retrying in ${RETRY_DELAY_MS / 1000}s...`);
            setTimeout(startServer, RETRY_DELAY_MS);
            return;
        }

        console.error(`❌ HTTP${isHttps ? 'S' : ''} server error:`, error);
        setTimeout(startServer, RETRY_DELAY_MS);
    });

    try {
        const scheme = isHttps ? 'https' : 'http';
        const wsScheme = isHttps ? 'wss' : 'ws';
        server.listen(PORT, async () => {
            console.log(`\n🚀 Fingerprint Server running on port ${PORT} (${isHttps ? 'HTTPS' : 'HTTP'})`);
            console.log(`   REST API: ${scheme}://localhost:${PORT}/api`);
            console.log(`   WebSocket: ${wsScheme}://localhost:${PORT}/ws`);
            console.log(`   Health Check: ${scheme}://localhost:${PORT}/health\n`);
            await initializeServices();
        });
    } catch (error) {
        if (error.code === 'EADDRINUSE') {
            console.error(`❌ Port ${PORT} is already in use. Retrying in ${RETRY_DELAY_MS / 1000}s...`);
            setTimeout(startServer, RETRY_DELAY_MS);
            return;
        }

        console.error(`❌ Failed to start ${isHttps ? 'HTTPS' : 'HTTP'} listener:`, error);
        setTimeout(startServer, RETRY_DELAY_MS);
    }
}

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('\n🛑 Shutting down server...');
    
    const fingerprintService = FingerprintService.getInstance();
    await fingerprintService.cleanup();
    
    await DatabaseService.close();
    
    server.close(() => {
        console.log('✅ Server shut down gracefully');
        process.exit(0);
    });
});

process.on('unhandledRejection', (reason) => {
    console.error('⚠️ Unhandled rejection:', reason);
});

process.on('uncaughtException', (error) => {
    console.error('⚠️ Uncaught exception:', error);
});

// Start the server
startServer();
