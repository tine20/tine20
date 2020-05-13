const merge = require('webpack-merge');
const common = require('./webpack.common.js');
const TerserPlugin = require('terser-webpack-plugin')

module.exports = merge(common, {
    devtool: 'eval',
    devServer: {
        hot: true,
        inline: false,
        host: '0.0.0.0',
        port: 10443,
        disableHostCheck: true,
        headers: {
            "Access-Control-Allow-Origin": "*",
            "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, PATCH, OPTIONS",
            "Access-Control-Allow-Headers": "X-Requested-With, content-type, Authorization"
        },
        proxy: [
            {
                context: ['**', '!/webpack-dev-server*/**'],
                target: 'http://localhost/',
                secure: false
            }
        ],
        before: function(app, server) {
            app.use(function(req, res, next) {
                // check for langfile chunk requests
                // build on demand
                // extract-text
                next();
            });
        }
    },
});
