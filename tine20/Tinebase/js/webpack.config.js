const webpack = require('webpack');
const UnminifiedWebpackPlugin = require('unminified-webpack-plugin');

module.exports = {
    entry: './Tinebase.js',
    output: {
        path: './',
        filename: 'Tinebase-libs-FAT.js'
    },
    devServer: {
        port: 10443,
    },
    plugins: [
        new webpack.optimize.UglifyJsPlugin({
            compress: {
                warnings: false,
            },
            output: {
                comments: false,
            },
        }),
        new UnminifiedWebpackPlugin({
            noMinSuffix : '-debug.js'
        })
    ]
};