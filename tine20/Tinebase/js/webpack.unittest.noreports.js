let path = require('path')
const merge = require('webpack-merge');
const common = require('./webpack.common.js');

module.exports = merge(common, {
    entry: null,
    devtool: 'inline-source-map',
    module: {
        rules: [
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: [
                    /node_modules/,
                    '/!(chai-as-promised)/'
                ],
                options: {
                    plugins: ['@babel/plugin-transform-runtime'],
                    presets: [
                        ["@babel/env"/*, { "modules": false }*/]

                    ]
                }
            }
        ]
    },
    resolve: {
        extensions: [".spec.js"],
        modules: [
            path.resolve(__dirname, '../../../tests/js/unit')
        ],
    }
});
