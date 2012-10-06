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
        'plugins/tpl!templates/video-modal.tpl'
    ],
    function(
        $,
        Stapes,
        tplVideoModal
    ){

        var VideoModal = Stapes.create().extend({

            options: {
                els: null
            },

            init: function( opts ){

                var self = this;

                this.options = Stapes.util.clone(this.options);
                this.extend(this.options, opts);

                // open overlays on watch click
                $(document).on('click', this.options.els, function(e){

                    var $this = $(this)
                        ,$video = $this.parents('.video')
                        ,modal = $this.data('modal')
                        ;

                    e.preventDefault();

                    if (!modal){

                        modal = $(tplVideoModal.render({

                            title: $video.find('.video-title').text(),
                            ytid: $video.data('ytid'),
                            subtitle: $this.data('lang')

                        })).modal().on('hidden', function () {
                            
                            // detach it when closed so video stops
                            $(this).detach();
                        });

                        $this.data('modal', modal);

                    } else {

                        modal.appendTo('body').modal('show');
                    }
                });

                return this;
            }
        });

        return VideoModal;
    }
);