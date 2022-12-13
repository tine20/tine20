const merge = require('webpack-merge');
const webpack = require('webpack');
const UnminifiedWebpackPlugin = require('unminified-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin')
const common = require('./webpack.common.js');
const BrotliPlugin = require('brotli-webpack-plugin');

module.exports = merge(common, {
    devtool: 'source-map',
    mode: 'production',
    optimization:{
        minimizer: [new TerserPlugin({
            sourceMap: true,
            extractComments: true,
            terserOptions: {
                // twing problem @see https://github.com/ericmorand/twing/issues/314,
                // this can be removed when https://github.com/ericmorand/twing/issues/336 is solved
                keep_classnames: true,
            },
        })],
    },
    plugins: [
        new webpack.DefinePlugin({
            BUILD_TYPE: "'RELEASE'"
        }),
        new UnminifiedWebpackPlugin({
            postfix : 'debug',
            replace: [[/(Tine\.clientVersion\.buildType\s*=\s*)'RELEASE'/, "$1'DEBUG'"]]
        }),
        new BrotliPlugin({})
    ],
});

