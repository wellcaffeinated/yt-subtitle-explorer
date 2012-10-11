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
        'stapes'
    ],
    function(
        $,
        Stapes,
        tplGenericModal
    ){

        var BroadcastModal = Stapes.create().extend({

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
                        ,modal = $this.data('modal')
                        ;

                    e.preventDefault();

                    if (!modal){

                        $.ajax({

                            url: $this.attr('href')

                        }).done(function( content ){

                            modal = $(content).modal().on('hidden', function () {
                                
                                var $this = $(this);

                                $this.find('form')[0].reset();
                                $this.find('.alert-error').hide();
                                $this.detach();
                            });

                            $this.data('modal', modal);
                        });

                    } else {

                        modal.appendTo('body').modal('show');
                    }
                });

                $(document).on('submit', '.broadcast-modal form', function(e){

                    e.preventDefault();

                    var $form = $(this)
                        ,$btn = $form.find('.ctrl-send')
                        ,$err = $form.find('.alert-error')
                        ,url = $form.attr('action')
                        ,method = $form.attr('method')
                        ;

                    $btn.button('loading');
                    $err.hide();

                    $.ajax({

                        url: url,
                        type: method,
                        data: $form.serialize(),
                        dataType: 'json'

                    }).done(function ( data ){

                        $btn.button('reset');
                        $form[0].reset();
                        $form.find('.ctrl-cancel').trigger('click'); // close

                    }).fail(function ( xhr, status, err ){

                        var data
                            ,msg
                            ;

                        $btn.button('reset');

                        if (status !== 'parsererror'){
                            try {

                                data = $.parseJSON(xhr.responseText);

                            } catch (e){
                                
                                data = {};
                            }
                        } else {

                            data = {};
                        }

                        msg = data.error? data.error : 'Problem sending email';

                        $err.html(msg).show();
                        
                    });


                });

                return this;
            }
        });

        return BroadcastModal;
    }
);