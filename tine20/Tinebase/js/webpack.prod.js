const merge = require('webpack-merge');
const UnminifiedWebpackPlugin = require('unminified-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin')
const common = require('./webpack.common.js');

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
        new UnminifiedWebpackPlugin({
            postfix : 'debug'
        }),
    ],
});

