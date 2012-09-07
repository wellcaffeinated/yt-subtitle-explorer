define(
	[
		'jquery',
		'stapes',
		'util/jquery.tokeninput'
	],
	function(
		$,
		Stapes,
		_ti //null
	){

		var LanguageSearch = Stapes.create().extend({

			options: {
				el: null,
                theme: 'facebook',
                tokenLimit: null
			},

			init: function( opts ){

				var self = this;

                this.options = Stapes.util.clone(this.options);
				this.extend(this.options, opts);

				$(function(){

                    var el = $(self.options.el);
                    if (!el.length) return;
                    self.set('el', el);
                });

                self.on('create:el', function( el ){

                    el.tokenInput(
                        el.attr('data-autocomplete-url'),
                        {
                            theme: self.options.theme,
                            hintText: 'Type in a language to search...',
                            preventDuplicates: true,
                            propertyToSearch: 'lang_code',
                            tokenValue: 'lang_code',
                            tokenLimit: self.options.tokenLimit,
                            onResult: function( r ){
                            	return Stapes.util.map(r, function(l){

                            		l.id = l.lang_code;
                            		return l;
                            	});
                            },
                            onAdd: function( item ){
                                self.set('languages', Stapes.util.map(el.tokenInput("get"), function( l ){
                                	return l.lang_code;
                                }));
                            },
                            onDelete: function( item ){
								self.set('languages', Stapes.util.map(el.tokenInput("get"), function( l ){
                                	return l.lang_code;
                                }));
                            },
                            tokenFormatter: function( item ){
                                return '<li>'+item.lang_translated+' - '+item.lang_original+'</li>';
                            },
                            resultsFormatter: function( item ){
                                return '<li>'+item.lang_translated+' - '+item.lang_original+'</li>';
                            }

                        }
                    );
                });

                return this;
			}
		});

		return LanguageSearch;
	}
);