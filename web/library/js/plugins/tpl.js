/**
 * Templating plugin that uses mustache.
 *
 * will return an api that provides raw text and render method.
 *
 * usage:
 * 		require(['plugins/tpl!templates/path/to/template.tpl'], function(tpl){
 * 			tpl.render({
 *				some: 'data'
 *			});
 * 		});
 */
define(
	[
		
		'plugins/text',
		
		'util/hogan'
	],
	
	function( text, templateEngine ){
		
		var cache = {};
		
		// cache and call callback
		function finishLoad( name, content, onLoad, config ) {
				
			cache[ name ] = content;
			
			onLoad( content );
		}
		
		
		function load( id, req, callback, config ){
			
			// do we have this cached?
			if ( id in cache ){
				
				callback( cache[id] );
				
			} else {
				// no cache. get file using text plugin
				
				text.get( req.toUrl( id ),
					
					function( text ){
						
						var content = templateEngine.compile( text );
					
						finishLoad( id, content, callback, config );
					}
				);
			}
		}
		
		
		// for build process. write an optimized version of template... not sure if it works yet.
		function write(pluginName, moduleName, write, config) {

			if ( moduleName in cache ) {
				
				var text = cache[ moduleName ].text,
					fn = templateEngine.compile( text, { asString: true } )
					;
				
				write.asModule( pluginName + '!' + moduleName,
								'define(["util/hogan-render"], function(h){ '+
									'var t = "";'+ //"'+ text.replace(/"/g,'\\"').replace(/\n/g,'') + '";' +
									'return new h.Template( '+fn+' );'+
								'});\n' );
			}
		}
	
		return {
			
			load: load,
			
			write: write
		};
	}
);