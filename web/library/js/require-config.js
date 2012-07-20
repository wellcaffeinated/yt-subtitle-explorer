require.config({ 
      
    jQuery: '1.7.2',

    waitSeconds: 30,
    
    paths: {
        'jquery': 'libs/jquery',
        'stapes': 'libs/stapes'
    },

    map: {

        '*': {
            'jquery': 'modules/adapters/jquery'
        },

        'plugins/json': {
            'text': 'plugins/text'
        },
        
        'modules/adapters/jquery': {
            'jquery': 'jquery'
        }
    }
});