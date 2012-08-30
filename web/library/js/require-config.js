require.config({ 
      
    jQuery: '1.7.2',

    waitSeconds: 30,

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