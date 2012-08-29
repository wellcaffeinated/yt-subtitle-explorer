define(
	[
		'jquery',
		'stapes'
	],
	function(
		$,
		Stapes
	){

		'use strict';

        var ToggleCtrl = Stapes.create().extend({

            // default options
            options: {

            	el: ''

            },

            init: function( opts ){

                var self = this
                	,options = {}
                	;

                // prevent double initializations
                if (self.inited) return self;
                self.inited = true;

                // extend default options
                self.extend( options, self.options );
                self.options = options;
                self.extend( self.options, opts );

                self.initEvents();

                $(function(){

                	var el = $(self.options.el);
                	
                	self.set({
	                	el: el,
	                	state: el.find('.btn.active').data('val') 
	                });

	                self.emit('ready');
	            });

                return self;
            },

            initEvents: function(){

            	var self = this;

            	self.on({
            		'create:el': function(el){

            			el.on('click', '.btn', function(e){

            				e.preventDefault();

            				var btn = $(this);

            				self.set('state', btn.data('val'));
            			});
            		},

            		'change:state': function( state ){

            			var el = self.get('el');

            			if (el){

            				el.find('.btn').each(function(){

            					var btn = $(this);

            					btn.toggleClass('active', btn.data('val') === state);
            				});
            			}
            		}
            	});
            }

        });

        // Factory function to return new stapes instances
        return function(){

            // Create a "sub-module"
            return ToggleCtrl.create();
        };
	}
);