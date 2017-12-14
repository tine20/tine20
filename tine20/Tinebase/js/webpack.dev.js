const merge = require('webpack-merge');
const common = require('./webpack.common.js');

module.exports = merge(common, {
    devtool: 'inline-source-map',
    devServer: {
        hot: false,
        inline: false,
        host: '0.0.0.0',
        port: 10443,
        disableHostCheck: true,
        proxy: [
            {
                context: ['**', '!/webpack-dev-server*/**'],
                target: 'http://localhost/',
                secure: false
            }
        ],
    },
});
