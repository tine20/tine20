require('dotenv').config();

module.exports = {
    globals: {
        browser: '',
        page: '',
        app: '',
    },
    testMatch: [
        "**/test/**/*.test.js"
    ],
    verbose: true,
    maxWorkers: 1,
    testTimeout: 60000,
};