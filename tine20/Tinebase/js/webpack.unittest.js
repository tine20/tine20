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
                    /!(chai-as-promised)/
                ],
                options: {
                    plugins: [
                        '@babel/plugin-transform-runtime',
                        '@babel/plugin-transform-modules-commonjs'
                    ],
                    presets: [
                        ["@babel/env"/*, { "modules": false }*/]

                    ]
                }
            },
            {
                // instrument only testing sources with Istanbul
                // https://github.com/webpack-contrib/istanbul-instrumenter-loader/issues/73
                // disable this loader to have correct traces in you code :-(
                test: /\.js$/,
                use: {
                    loader: 'istanbul-instrumenter-loader',
                    options: {
                        esModules: true,
                        produceSourceMap: true
                    }
                },
                enforce: 'post',
                exclude: /(node_modules|ux)/,
                include: path.resolve(__dirname, '../../')
            },
            {
                test: /\.js$/,
                use: ['webpack-conditional-loader']
            },
        ]
    },
    resolve: {
        extensions: [".spec.js"],
        modules: [
            path.resolve(__dirname, '../../../tests/js/unit')
        ],
    }
});
