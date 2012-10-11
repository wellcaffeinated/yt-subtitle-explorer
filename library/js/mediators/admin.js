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
        'modules/video-modal',
        'modules/broadcast-modal',
        'plugins/tpl!templates/caption-modal.tpl',
        'plugins/tpl!templates/modal-caption-reject.tpl',
        'bootstrap'
    ],
    function(
        $,
        Stapes,
        videoModal,
        broadcastModal,
        tplCaptionModal,
        tplModalCaptionReject
    ){
        'use strict';

        var mediator = Stapes.create().extend({

            init: function(){

                var self = this
                    ;

                videoModal.init({
                    'els': '.video .ctrl-watch'
                });

                broadcastModal.init({
                    'els': '.ctrl-broadcast'
                });

                /**
                 * Admin controls
                 */
                $(document)
                    .on('click', '.admin .captions .ctrl-view', function(e){

                        e.preventDefault();
                        
                        var $this = $(this)
                            ,href = $this.attr('href')
                            ,btnApprove = $this.siblings('.ctrl-approve')
                            ,approveHref = btnApprove.length? $this.parents('form').attr('action') + '?' + btnApprove.attr('name') + '=' + encodeURIComponent(btnApprove.attr('value')) : false
                            ;

                        self.showCaptionModal(href, {

                            title: 'Caption',
                            approveHref: approveHref
                        });

                    })
                    .on('change', '.ctrl-select-all', function(e){

                        var $this = $(this)
                            ,checked = $this.is(':checked')
                            ,checkboxes = $this.parents('.select-root').find('.ctrl-select')
                            ;

                        e.preventDefault();

                        if (checked){

                            checkboxes.attr('checked', 'checked');

                        } else {

                            checkboxes.removeAttr('checked');
                        }

                    })
                    .on('click', '.admin .captions .ctrl-reject', function(e){

                        e.preventDefault();

                        var $this = $(this)
                            ,reasons = $('#caption-rejection-reasons').html()
                            ,trailingSpace = /\s\n/
                            ,consecutiveNewline = /\n\n/
                            ;

                        reasons = reasons.replace(/^[\n\r]|[\n\r](?!\w)/gi,'');
                        reasons = reasons? reasons.split('\n') : false;

                        $(tplModalCaptionReject.render({

                            action: $this.attr('href'),
                            path: $this.data('path'),
                            reasons: reasons

                        })).modal().on('hidden', function () {
                            
                            $(this).remove();
                        });

                    })
                    .on('change', '.caption-reject-modal .rejection-reasons', function(e){

                        var $this = $(this);
                        $this.siblings('.other-reason').toggle($this.val() === 'other');
                    })
                    ;
            },

            showCaptionModal: function(url, params){

                $.ajax({

                    url: url

                }).done(function( content ){

                    $(tplCaptionModal.render(
                        $.extend({
                        
                            content: content

                        }, params)
                    )).modal().on('hidden', function () {
                        
                        $(this).remove();
                    });
                });      
            }

        });

        mediator.init();
    }
);