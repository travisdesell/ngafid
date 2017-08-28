var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Less
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix
        .styles([
            "app.css",
            "./vendor/nostalgiaz/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css"
        ], "public/css/vendor.css")
        .styles([
            "custom.css"
        ], "public/css/custom.css")
        .scripts([
            "./vendor/nostalgiaz/bootstrap-switch/dist/js/bootstrap-switch.min.js"
        ], "public/js/vendor.js")
        .version([
            "css/vendor.css",
            "css/custom.css",
            "js/vendor.js"
        ]);
});
