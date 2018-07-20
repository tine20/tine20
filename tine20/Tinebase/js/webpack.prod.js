const merge = require('webpack-merge');
const UnminifiedWebpackPlugin = require('unminified-webpack-plugin');
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');
const common = require('./webpack.common.js');

module.exports = merge(common, {
    devtool: 'source-map',
    plugins: [
        new UnminifiedWebpackPlugin({
            postfix : 'debug'
        }),
        new UglifyJSPlugin({})
    ],
});
