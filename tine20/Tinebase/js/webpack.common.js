var fs = require('fs');
var _ = require('lodash');
var path = require('path');
var webpack = require('webpack');
var AssetsPlugin = require('assets-webpack-plugin');
var assetsPluginInstance = new AssetsPlugin({
    // path: 'Tinebase/js',
    // fullPath: false,
    keepInMemory: true,
    filename: 'webpack-assets-FAT.json',
    prettyPrint: true
});
var VueLoaderPlugin = require('vue-loader/lib/plugin');

var baseDir  = path.resolve(__dirname , '../../'),
    entryPoints = {};

// find all entry points
fs.readdirSync(baseDir).forEach(function(baseName) {
    try {
        // try npm package.json
        var pkgDef = JSON.parse(fs.readFileSync(baseDir + '/' + baseName + '/js/package.json').toString());

        _.each(_.get(pkgDef, 'tine20.entryPoints', []), function(entryPoint) {
            entryPoints[baseName + '/js/' + entryPoint] = baseDir + '/' + baseName + '/js/' + entryPoint;
        });

    } catch (e) {
        // no package.json - no entry defined
    }
});

module.exports = {
    entry: entryPoints,
    optimization:{
        splitChunks: {
            cacheGroups: {
                vendors: {
                    // automaticNamePrefix: '',
                    test: /(?!x)x/ // never matches - we don't use vendors cache yet
                },
            }
        }
    },
    output: {
        path: baseDir + '/',
        // avoid public path, see #13430.
        // publicPath: '/',
        filename: '[name]-[hash]-FAT.js',
        chunkFilename: "[name]-[chunkhash]-FAT.js",
        libraryTarget: "umd"
    },
    plugins: [
        assetsPluginInstance,
        new VueLoaderPlugin()
    ],
    module: {
        rules: [
            {
                test: /\.(es6\.js|vue)$/,
                loader: 'eslint-loader',
                enforce: "pre",
                exclude: /node_modules/,
                options: {
                    formatter: require('eslint-friendly-formatter')
                }
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: [
                    /node_modules/,
                ],
                options: {
                    plugins: [
                        "@babel/plugin-transform-runtime",
                        "@babel/plugin-syntax-dynamic-import"
                    ],
                    presets: [
                        ["@babel/preset-env"/*, { "modules": false }*/]
                    ]
                }
            },
            {
                test: /\.js$/,
                include: [
                    require.resolve("bootstrap-vue"), // white-list bootstrap-vue
                ],
                loader: "babel-loader"
            },

            // use script loader for old library classes as some of them the need to be included in window context
            {test: /\.js$/, include: [baseDir + '/library'], enforce: "pre", use: [{loader: "script-loader"}]},
            {test: /\.jsb2$/, use: [{loader: "./jsb2-loader"}]},
            {test: /\.css$/, use: [{loader: "style-loader"}, {loader: "css-loader"}]},
            {test: /\.png/, use: [{loader: "url-loader", options: {limit: 100000}}]},
            {test: /\.gif/, use: [{loader: "url-loader", options: {limit: 100000}}]},
            {test: /\.svg/, use: [{loader: "svg-url-loader"},{loader: "./svg-fix-size-loader"}]},
            {
                test: /\.(woff2?|eot|ttf|otf)(\?.*)?$/,
                use: [{loader: "url-loader", options: {limit: 100000}}]
            },
        ]
    },
    resolveLoader: {
        modules: [path.resolve(__dirname, "node_modules")]
    },
    resolve: {
        extensions: [".js", ".es6.js"],
        // add browserify which is used by some libs (e.g. director)
        mainFields: ["browser", "browserify", "module", "main"],
        // we need an absolut path here so that apps can resolve modules too
        modules: [
            path.resolve(__dirname, "../.."),
            __dirname,
            path.resolve(__dirname, "node_modules")
        ]
    }
};
