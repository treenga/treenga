const mix = require('laravel-mix')
const path = require('path')
const Dotenv = require('dotenv-webpack')

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.webpackConfig({
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources'),
      '~': path.resolve(__dirname),
    },
  },
  plugins: [
    new Dotenv(),
  ],
})

mix.js('resources/js/app.js', 'public/js')
  .js('resources/admin/admin.js', 'public/js')
  .sass('resources/sass/app.scss', 'public/css')
  .version()
