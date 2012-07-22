define(
    [
        'jquery',
        'stapes',
        'modules/language-search'
    ],
    function(
        $,
        Stapes,
        languageSearch
    ){
        'use strict';

        var mediator = Stapes.create().extend({

            init: function(){

                var self = this
                    ,wrap
                    ;

                $(function(){
                    var filterSelect = $('#filter-select :radio').on('change', function(){
                        self.filterVids();
                    });
                    self.set('vid-filter', filterSelect);

                    var filterNegate = $('#filter-negate :radio').on('change', function(){
                        self.filterVids();
                    });
                    self.set('vid-negate', filterNegate);

                    wrap = $('#language-search-wrap');
                    self.set('all-langs-url', wrap.attr('data-all-langs'));
                });

                languageSearch.create().init({
                    
                    el: '#lang-search'

                }).on({

                    'change:languages': function( langs ){

                        self.set('languages', langs);
                        self.filterVids();
                    }
                });
            },

            filterVids: function(){

                var langs = this.get('languages') || []
                    ,url = langs.length? 
                            '/languages/' + this.get('vid-negate').filter(':checked').attr('data-val') + '/' + this.get('vid-filter').filter(':checked').attr('data-val') + '/' + langs.join('~') 
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