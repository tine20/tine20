const merge = require('webpack-merge');
const UnminifiedWebpackPlugin = require('unminified-webpack-plugin');
const MinifyPlugin = require("babel-minify-webpack-plugin");
const common = require('./webpack.common.js');

module.exports = merge(common, {
    // @see https://github.com/webpack/webpack/issues/5931
    devtool: 'none',
    mode: 'production',
    optimization:{
        minimize: false, // disables uglify -> we use babel minify
    },
    plugins: [
        new UnminifiedWebpackPlugin({
            postfix : 'debug'
        }),
        new MinifyPlugin({
            // mangle: false,
            // keepFnName: true,
            keepClassName: true, // twing problem @see https://github.com/ericmorand/twing/issues/314
        }, {
            comments: false,
        })
    ],
});

