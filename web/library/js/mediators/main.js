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
                    ,scopeCtrl
                    ,negateCtrl
                    ;

                negateCtrl = toggleCtrl().init({
                    el: '#negate-ctrl'
                });

                scopeCtrl = toggleCtrl().init({
                    el: '#scope-ctrl'
                });

                self.set({
                    'ctrl.negate': negateCtrl,
                    'ctrl.scope': scopeCtrl
                });
                
                $(function(){

                    wrap = $('#language-search-wrap');
                    self.set('all-langs-url', wrap.attr('data-all-langs'));

                    languageSearch.create().init({
                        
                        el: $('#lang-search input')[0]

                    }).on({

                        'change:languages': function( langs ){

                            self.set('languages', langs);
                            self.filterVids();
                        }
                    });

                    negateCtrl.on({
                        'change:state': self.filterVids
                    }, self);

                    scopeCtrl.on({
                        'change:state': self.filterVids
                    }, self);
                });
            },

            filterVids: function(){

                var langs = this.get('languages') || []
                    ,url = langs.length? 
                            '/languages/' + this.get('ctrl.negate').get('state') + '/' + this.get('ctrl.scope').get('state') + '/' + langs.join('~') 
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