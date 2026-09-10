const express = require('express');

const app = express();

app.use(express.json());

/**
 * 印出收到的 Webhook
 */
app.use((req, res, next) => {
    console.log('--------------------------------');
    console.log('Time:', new Date().toISOString());
    console.log('Method:', req.method);
    console.log('URL:', req.url);
    console.log('Body:', req.body);

    next();
});

/**
 * 模擬成功
 */
app.post('/success', (req, res) => {
    return res.status(200).json({
        success: true,
        message: 'Webhook received successfully',
    });
});

/**
 * 模擬 Bad Request
 *
 * Permanent Failure
 * 不應 Retry
 */
app.post('/bad-request', (req, res) => {
    return res.status(400).json({
        success: false,
        message: 'Simulated bad request',
    });
});

/**
 * 模擬 Rate Limit
 *
 * Retryable
 */
app.post('/rate-limit', (req, res) => {
    return res.status(429).json({
        success: false,
        message: 'Simulated rate limit',
    });
});

/**
 * 模擬 Provider 暫時故障
 *
 * Retryable
 */
app.post('/server-error', (req, res) => {
    return res.status(503).json({
        success: false,
        message: 'Simulated service unavailable',
    });
});

/**
 * 模擬 Timeout
 *
 * Laravel timeout 如果設定 5 秒，
 * 這裡故意等 10 秒。
 */
app.post('/timeout', async (req, res) => {
    await new Promise(
        resolve => setTimeout(resolve, 10000)
    );

    return res.status(200).json({
        success: true,
        message: 'Delayed response',
    });
});

const PORT = 9000;

app.listen(PORT, () => {
    console.log(
        `Mock receiver running on http://127.0.0.1:${PORT}`
    );
});