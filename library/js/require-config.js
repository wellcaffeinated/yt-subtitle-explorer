/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
require.config({ 
      
    waitSeconds: 30,
    urlArgs: 'v=0.2b',

    shim: {
        'bootstrap': ['jquery']
    },
    
    paths: {
        'jquery': 'libs/jquery',
        'stapes': 'libs/stapes',
        'bootstrap': 'libs/bootstrap.min'
    },

    map: {

        // '*': {
        //     'jquery': 'modules/adapters/jquery'
        // },

        // 'modules/adapters/jquery': {
        //     'jquery': 'jquery'
        // },

        'plugins/json': {
            'text': 'plugins/text'
        },

        'bootstrap': {
            'jquery': 'jquery'
        }
    }
});