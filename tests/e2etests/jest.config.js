require('dotenv').config();

module.exports = {
    globals: {
        browser: '',
        page: '',
    },
    roots: [
        "<rootDir>",
        process.env.PWD + "../../../tine20/vendor/metaways"
    ],
    testMatch: [
        "**/" + process.env.TEST_DIR + "/**/*.test.js",
        "**/tine20/vendor/metaways/*/tests/e2etests/*.test.js"
    ],
    testPathIgnorePatterns: [
        "node_modules"
    ],
    verbose: true,
    maxWorkers: process.env.TEST_WORKER,
    testTimeout: 60000,
};