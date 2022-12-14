const merge = require('webpack-merge');
const prod = require('./webpack.prod.js');

var AssetsPlugin = require('assets-webpack-plugin');
var assetsPluginInstance = new AssetsPlugin({
    path: '/out/tine20/Tinebase/js',
    keepInMemory: true,
    filename: 'webpack-assets-FAT.json',
    prettyPrint: true
});

module.exports = merge(prod, {
    output: {
        path: '/out/tine20'
    },
    plugins: [
        assetsPluginInstance
    ]
});

