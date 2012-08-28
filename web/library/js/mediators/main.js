define(
    [
        'jquery',
        'stapes',
        'modules/language-search',
        'modules/toggle-ctrl'
    ],
    function(
        $,
        Stapes,
        languageSearch,
        toggleCtrl
    ){
        'use strict';

        var mediator = Stapes.create().extend({

            init: function(){

                var self = this
                    ,wrap
                    ;

                toggleCtrl().init({
                    el: '#negate-ctrl'
                }).on({
                    'change:state': function(state){
                        self.set('negate', state);
                    }
                });

                toggleCtrl().init({
                    el: '#scope-ctrl'
                }).on({
                    'change:state': function(state){
                        self.set('scope', state);
                    }
                });

                self.on({
                    'change:negate': self.filterVids,
                    'change:scope': self.filterVids
                }, self);

                $(function(){

                    wrap = $('#language-search-wrap');
                    self.set('all-langs-url', wrap.attr('data-all-langs'));


                    languageSearch.create().init({
                        
                        el: '#lang-search'

                    }).on({

                        'change:languages': function( langs ){

                            self.set('languages', langs);
                            self.filterVids();
                        }
                    });
                });
            },

            filterVids: function(){

                var langs = this.get('languages') || []
                    ,url = langs.length? 
                            '/languages/' + this.get('negate') + '/' + this.get('scope') + '/' + langs.join('~') 
                            : this.get('all-langs-url')
                    ;

                $.ajax({
                    url: url,
                    complete: function( xhr ){

                        var el = $('.video-list');
                        el.after(xhr.responseText);
                        el.remove();
                    }
                })
            }

        });

        mediator.init();
    }
);