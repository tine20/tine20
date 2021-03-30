module.exports = {
    launch: {
        headless: process.env.TEST_MODE !== 'debug',
        executablePath: "./node_modules/puppeteer/.local-chromium/linux-856583/chrome-linux/chrome"
    },
}