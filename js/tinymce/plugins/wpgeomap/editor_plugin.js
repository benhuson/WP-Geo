/**
 * $Id: editor_plugin.js 001 2009-03-25
 *
 * @author Ben Huson
 * @copyright Copyright 2004-2008, Ben Huson, All rights reserved.
 */

(function() {
	tinymce.create('tinymce.plugins.WPGeoMap', {
		
		// Init.
		init : function(ed, url) {
			var t = this;

			t.editor = ed;

			// Register commands
			ed.addCommand('mceWPGeoMap', function() {
				ed.execCommand('mceInsertContent', false, '[wp_geo_map]');
			});

			// Register buttons
			ed.addButton('wpgeomap', {
				title : 'WP Geo Map',
				cmd : 'mceWPGeoMap',
				image : url + '/img/button.png'
			});

		},
		
		// Get Info
		getInfo : function() {
			return {
				longname : 'WP Geo Map',
				author : 'Ben Huson',
				authorurl : 'http://www.benhuson.co.uk',
				infourl : 'http://www.wpgeo.com',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}

	});

	// Register plugin
	tinymce.PluginManager.add('wpgeomap', tinymce.plugins.WPGeoMap);
	
})();