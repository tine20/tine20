const path = require('path')
const merge = require('webpack-merge')
const prod = require('./webpack.prod.js')
const _ = require('lodash')

module.exports = merge(prod, {
    entry: null,
    module: {
        rules: [
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

// adopt babel-loader to cope with inject-loader
let rules = module.exports.module.rules
let babelLoader = _.find(rules, {loader: 'babel-loader'})
let presetEnv = _.find(babelLoader.options.presets, function(preset) {
    return preset[0] == '@babel/preset-env'
})

// needed for inject loader to work
babelLoader.options.plugins.push('@babel/plugin-transform-modules-commonjs')

// inject loader does not work with useBuiltIns -> we really need to get rid of it!
presetEnv.pop()

