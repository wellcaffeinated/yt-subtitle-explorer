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

                var filterSelect
                    ,wrap
                ;

                $(function(){
                    filterSelect = $('#filterSelect');
                    wrap = $('#language-search-wrap');
                });

                languageSearch.create().init({
                    
                    el: '#lang-search'

                }).on({

                    'change:languages': function( langs ){

                        var url = langs.length? filterSelect.val() + '/' + langs.join('~') : wrap.attr('data-all-langs');

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
            }
        });

        mediator.init();
    }
);