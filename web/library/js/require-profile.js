/*
 * Architecture: https://github.com/ryanfitzer/Example-RequireJS-jQuery-Project
 * All config options: https://github.com/jrburke/r.js/blob/master/build/example.build.js
 * To build this project, execute the following commands:
 *    $ cd {location of require-profile.js}
 *    $ r.js -o require-profile.js
 */
({
    dir: '../../library-build/',
    appDir: '../',
    baseUrl: 'js/',
    optimize: 'uglify',
    optimizeCss: 'none', // https://github.com/jrburke/r.js/issues/167
    fileExclusionRegExp: /^\.|node_modules/,
    findNestedDependencies: true,
    mainConfigFile: 'require-config.js',

    // don't need these modules to function in the build. So create stubs.
    stubModules: [ 'plugins/tpl' ],// 'plugins/text' ],

    modules: [
        {
            name: 'core',
            excludeShallow: [
                'util/hogan',
                'util/hogan-compile'
            ]
        }
    ]
})