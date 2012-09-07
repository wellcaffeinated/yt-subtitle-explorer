define(
    [
        'jquery',
        'stapes',
        'modules/language-search',
        'modules/toggle-ctrl',
        'plugins/tpl!templates/video-modal.tpl',
        'bootstrap'
    ],
    function(
        $,
        Stapes,
        languageSearch,
        toggleCtrl,
        tplModal
    ){
        'use strict';

        var mediator = Stapes.create().extend({

            init: function(){

                var self = this
                    ,wrap
                    ,scopeCtrl
                    ,negateCtrl
                    ;

                // open overlays on watch click
                $(document).on('click', '.video .watch-btn', function(e){

                    var $this = $(this)
                        ,$video = $this.parents('.video')
                        ;

                    e.preventDefault();

                    $(tplModal.render({

                        title: $video.find('.video-title').text(),
                        ytid: $video.data('ytid')

                    })).modal().on('hidden', function () {
                        
                        // destroy it when closed so video stops
                        $(this).remove();
                    });

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
                    ,url = langs.length? 
                            '/videos/languages/' + this.get('ctrl.negate').get('state') + '/' + this.get('ctrl.scope').get('state') + '/' + langs.join('~') 
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