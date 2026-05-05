const { spawn } = require('child_process');

const RESTART_DELAY_MS = 2000;
let child = null;
let shuttingDown = false;

function startChild() {
    if (shuttingDown) {
        return;
    }

    console.log('[watchdog] starting fingerprint server...');

    child = spawn(process.execPath, ['server.js'], {
        cwd: __dirname,
        stdio: 'inherit',
        windowsHide: false,
    });

    child.on('exit', (code, signal) => {
        if (shuttingDown) {
            return;
        }

        const reason = signal ? `signal ${signal}` : `exit code ${code}`;
        console.error(`[watchdog] fingerprint server stopped (${reason}). restarting in ${RESTART_DELAY_MS / 1000}s...`);

        setTimeout(() => {
            startChild();
        }, RESTART_DELAY_MS);
    });

    child.on('error', (error) => {
        console.error('[watchdog] failed to start fingerprint server:', error.message);
        setTimeout(() => {
            startChild();
        }, RESTART_DELAY_MS);
    });
}

function shutdown() {
    shuttingDown = true;

    if (child && !child.killed) {
        child.kill('SIGINT');
    }

    setTimeout(() => process.exit(0), 300);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

startChild();
