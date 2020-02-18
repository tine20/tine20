require('dotenv').config();

module.exports = {
    globals: {
        browser: '',
        page: '',
        app: '',
    },
    testMatch: [
        "**/" + process.env.TEST_DIR + "/**/*.test.js"
    ],
    verbose: true,
    maxWorkers: process.env.TEST_WORKER,
    testTimeout: 60000,
};