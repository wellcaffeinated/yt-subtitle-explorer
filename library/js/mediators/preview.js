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
        'popcorn'
    ],
    function(
        $,
        Stapes,
        popcorn
    ){
        'use strict';

        var mediator = Stapes.create().extend({

            init: function(){

                $(function(){

                    var el = $('#video-preview')
                        ,file = el.attr('data-subtitle-file')
                        ,pop = popcorn.youtube('#video-preview', el.attr('data-video-url')).autoplay(false)
                        ;

                    pop.on('loadedmetadata', function(){

                        switch ( el.attr('data-subtitle-format') ){

                            case 'srt':
                                pop.parseSRT( file );
                                break;

                            case 'sbv':
                                pop.parseSBV( file );
                                break;

                            case 'txt':

                                if ($('textarea.caption-content:first').val().match('-->')){

                                    pop.parseSRT( file );

                                } else {

                                    pop.parseSBV( file );
                                }

                                break;

                            default:
                                pop.subtitle({
                                    start: 0,
                                    end: pop.duration(),
                                    text: "[Caption format not supported.]",
                                });
                        }
                    });
                });
            }

        });

        mediator.init();
    }
);