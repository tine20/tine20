let path = require('path')
let basePath = path.resolve(__dirname, "../../../tests/js/unit")

process.env.CHROME_BIN = require('puppeteer').executablePath()

module.exports = function (config) {
    config.set({
        // base path that will be used to resolve all patterns (eg. files, exclude)
        basePath: basePath,

        // available frameworks: https://npmjs.org/browse/keyword/karma-adapter
        frameworks: ['mocha', 'chai', 'chai-as-promised', 'sinon'],

        // list of files / patterns to load in the browser
        files: [
            path.resolve(__dirname, "../../library/ExtJS/adapter/ext/ext-base-debug.js"),
            path.resolve(__dirname, "../../library/ExtJS/ext-all-debug.js"),
            '**/*.spec.js'
        ],

        // list of files / patterns to exclude
        exclude: [],

        // preprocess matching files before serving them to the browser
        // available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
        preprocessors: {
            "**/*.js": ["eslint", "webpack", "sourcemap"]
        },

        // webpack configuration
        webpack: require("./webpack.unittest.noreports.js"),
        webpackMiddleware: {
            stats: "errors-only"
        },

        eslint: {
            // errorThreshold: 10000,
            // stopAboveErrorThreshold: true,
            // stopOnError: true,
            // stopOnWarning: true,
            // showWarnings: true,
        },

        // web server port
        port: 9876,

        // enable / disable colors in the output (reporters and logs)
        colors: true,

        // level of logging
        // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        logLevel: config.LOG_INFO,

        // enable / disable watching file and executing tests whenever any file changes
        autoWatch: true,

        // start these browsers
        // available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
        // browsers: ['PhantomJS', 'Chrome', 'ChromeWithoutSecurity', 'Firefox', 'Safari', 'IE'],
        browsers: ['ChromeHeadlessCloud'],
        customLaunchers: {
            ChromeWithoutSecurity: {
                base: 'Chrome',
                flags: ['--disable-web-security']
            },
            ChromeHeadlessCloud: {
                base: 'ChromeHeadless',
                flags: ['--disable-web-security', '--no-sandbox']
            }
        },

        // Continuous Integration mode
        // if true, Karma captures browsers, runs the tests and exits
        singleRun: false,

        // Concurrency level
        // how many browser should be started simultaneous
        concurrency: Infinity
    })
}
