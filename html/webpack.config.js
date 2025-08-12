const fs = require('fs');
const glob = require('glob');
const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const CopyPlugin = require('copy-webpack-plugin');
const AutoPrefixerPlugin = require('autoprefixer');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');

// const HtmlCriticalWebpackPlugin = require("html-critical-webpack-plugin");

function getEntries(pattern) {
    const entries = {};
    glob.sync(pattern).forEach((file) => {
        const outputFileKey = file.replace('src/', '').replace('.js', '').replace('.scss', '.styles').replace('.css', '.styles');
        if (outputFileKey.indexOf('/_') == -1) {
            entries[outputFileKey] = path.join(__dirname, file);
        }
    });
    return entries;
}

function generateHtmlPlugins(templateDir, mode) {
    // Read files in template directory
    const templateFiles = fs.readdirSync(path.resolve(__dirname, templateDir))
    return templateFiles.map(item => {
        // Split names and extension
        const parts = item.split('.')
        const name = parts[0]
        const extension = parts[1]
        // Create new HTMLWebpackPlugin with options
        return new HtmlWebpackPlugin({
            filename: `${name}.html`,
            inject: false,
            template: 'ejs-webpack-loader!' + path.resolve(__dirname, `${templateDir}/${name}.${extension}`),
            templateParameters: {"mode": mode}
        })
    })
};

function generateCriticalCssPlugins(templateDir) {
    // Read files in template directory
    const templateFiles = fs.readdirSync(path.resolve(__dirname, templateDir))
    return templateFiles.map(item => {
        // Split names and extension
        const parts = item.split('.')
        const name = parts[0]
        const extension = parts[1]
        // Create new HtmlCriticalWebpackPlugin with options

        return new HtmlCriticalWebpackPlugin({
            base: path.resolve(__dirname, 'dist'),
            src: `${name}.html`,
            dest: `${name}.critical.html`,
            inline: true,
            width: 375,
            height: 565,
            penthouse: {
                blockJSRequests: false,
            }
        })
    })
};

const entries = getEntries('src/[!_]**/[!_]**/[!_]*.{css,scss,js,jsx}');
// const criticalPlugins = generateCriticalCssPlugins('./src/pages');

module.exports = (env, args) => {

    const htmlPlugins = generateHtmlPlugins('./src/pages', env.mode);
    const config = {
        mode: "development",
        entry: entries,
        output: {
            path: __dirname + '/dist',
            filename: '[name].js',
        },
        plugins: [
            new RemoveEmptyScriptsPlugin(),
            new MiniCssExtractPlugin({filename: e => e.chunk.name.indexOf('.styles') > 0 ? '[name].css' : '[name].styles.css'}),
            new CopyPlugin({
                patterns: [
                    {from: "assets", to: "assets"},
                    {from: "api", to: "api"},
                ],
            }),
            new CopyPlugin({
                patterns: [
                    {from: "index.html", to: "index.html", toType: "file"},
                    {from: ".htaccess", to: ".htaccess", toType: "file"},
                ],
            }),
        ].concat(htmlPlugins),
        // ].concat(htmlPlugins).concat(criticalPlugins),
        resolve: {
            extensions: ['.js', '.scss', '.css'],
        },
        module: {
            rules: [
                {
                    test: /\.(js|jsx)$/,
                    exclude: /node_modules/,
                    use: ['babel-loader']
                },
                {
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader, // 3 Extract css to files
                        {loader: 'css-loader', options: {url: false}}, // 2 convert CSS into JS modules (commonjs)
                        {loader: 'resolve-url-loader', options: {removeCR: true, sourceMap: env.mode === 'development'}}, // for fonts in scss
                        {loader: 'postcss-loader'}, // Auto prefixer
                        {loader: 'sass-loader', options: {sourceMap: env.mode === 'development'}}, // 1 compile Sass to CSS
                    ]
                },
                {
                    test: /\.css$/i,
                    use: [
                        MiniCssExtractPlugin.loader, // 2 Extract css to files
                        "css-loader", // 1 convert CSS into JS modules (commonjs)
                    ]
                },
            ]
        }
    }

    if (env.mode === 'development') {
        config.devtool = 'source-map';
    } else {
        config.plugins.unshift(new CleanWebpackPlugin());
    }

    return config;
};