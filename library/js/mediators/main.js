/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
define(
    [
        'jquery',
        'stapes',
        'modules/language-search',
        'modules/toggle-ctrl',
        'modules/video-modal',
        'bootstrap'
    ],
    function(
        $,
        Stapes,
        languageSearch,
        toggleCtrl,
        videoModal
    ){
        'use strict';

        var mediator = Stapes.create().extend({

            init: function(){

                var self = this
                    ,wrap
                    ,scopeCtrl
                    ,negateCtrl
                    ;

                videoModal.init({
                    'els': '.video .ctrl-watch'
                });

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

                    // the language selector on the caption upload page
                    languageSearch.create().init({
                        
                        el: $('#cap-upload-lang input')[0],
                        tokenLimit: 1,
                        theme: null

                    }).on({

                        'change:languages': function( langs ){

                            this.get('el').val(langs[0]);
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
                    ,url = this.get('all-langs-url') + (
                            langs.length? 
                                '/' + this.get('ctrl.negate').get('state') + '/' + this.get('ctrl.scope').get('state') + '/' + langs.join('~') 
                                : ''
                            )
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