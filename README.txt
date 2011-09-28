=== WP Geo  ===
Contributors: husobj
Donate link: http://www.wpgeo.com/donate
Tags: maps, map, geo, geocoding, google, location, georss
Requires at least: 2.9
Tested up to: 3.2.1
Stable tag: 3.2.6

Add location maps to your posts and pages.

== Description ==

**NOTE: You will need to re-add your widgets after upgrading to 3.2.5!**

When editing a post or page, you will be able to set a physical location for that post and easily embed a Google map into your post. You can select the location by:

1. Clicking on the map of the world to position the point.
2. Searching for a location, town, city or address.
3. Entering the latitude and longitude. 


The WP Geo location selector is styled to fit seamlessly into the latest version of the WordPress admin.

More information can be found at http://www.wpgeo.com/.

= Features =

* Custom marker title setting per post.
* Custom zoom and map type per post.
* Supports custom post types.
* Widget zoom option.
* Default Map Location setting.
* Load maps from GeoRSS or KML data.
* Geo Meta Tags
* Markers links to posts
* Settings for default controls
* Custom Markers
* Sidebar Widget
* GeoRSS points in feeds.
* Set default map zoom level.
* Show post maps on category and archive pages.
* Set default width and height for maps
* Shortcode [wp_geo_map] to insert map within your post
* Select your preferred map type
* Select wether to show your map at the top or bottom of posts (or not at all)
* Set a location by clicking on a map or
* Set a location by searching for a location, town, city or address or
* Set a location by entering the latitude and longitude

== Installation ==
1. Download the archive file and uncompress it.
2. Put the "wp_geo" folder in "wp-content/plugins"
3. Enable in WordPress by visiting the "Plugins" menu and activating it.
4. Go to the Settings page in the admin and enter your Google API Key and customise the settings.

(you can sign up for a Google API Key at http://code.google.com/apis/maps/signup.html)

WP Geo will appear on the edit post and edit page screens.
If you set a location, a Google map will automatically appear on your post or page (if your settings are set to).

You can add a map you your category pages to which will display the locations of any posts within that category.
Simply enter <?php $wpgeo->categoryMap(); ?> into your category template where you would like the map to appear.

Please note that from version 2.2 you should access any WPGeo methods using the $wpgeo instance, not using a static class such as <?php WPGeo::categoryMap(); ?>.
Being able to access methods in this way will be phased out in future versions so please change your code now if you need to.

= Upgrading =

If upgrading from a previous version of the plugin:

1. If you are not performing an automatic upgrade, deactivate and reactivate the plugin to ensure any new features are correctly installed.
2. Visit the settings page after installing the plugin to customise any new options.

== Screenshots ==

1. Example of a post with a map.
2. Admin panel shown when editing a post or page.
3. Admin Settings
4. Widget Settings

== Changelog ==

= WP Geo 3.2.6 =

* Added support for custom post type in Recent Locations widget.
* Added Static Map (get_)wpgeo_post_static_map template tags. Props Jurriaan Persyn.
* Added Dutch translation by Davey IJzermans.
* Fixed custom post type settings. Checkboxes are now only shown disabled if support is explicitly added using add_post_type_support().
* Fixed widget_is_active() function.
* Fixed path and compatibility for MultiSite installations.

= WP Geo 3.2.5 =

NOTE: You will need to re-add your widgets after upgrading!

* Widgets updated to use Widget API - you can now add multiple widgets.
* Added recent locations widget.
* Added 'wpgeo_base_country_code' filter so you can default admin post map search to a base country.
* Added option to show maps in taxonomy archives.
* Fixed admin map not showing after publish if "Save map centre point for this post" checked but no marker added.
* Fixed some deprecated functions and undeclared variables.
* Fixed feeds - incorrect database table references.
* Fixed dashboard - uses SimplePie.
* Prevent post been saved when pressing enter in WP Geo post fields.
* Check that co-ordinates have been set in get_wpgeo_post_map().

= WP Geo 3.2.4 =

* Added option to allow saving of zoom level, map type and centre point to be checked by default.
* Added option to choose custom marker per post.
* Don't output script tags if theres no script.
* Added options to show map on excerpts and author archives.
* Default output for the [wpgeo_map_link] shortcode. Props RavanH.

= WP Geo 3.2.3 =

* If markers image files aren't in uploads folder, default to using the ones in the plugin folder.
* Fix for post settings being overwritten when using quick edit.
* Added extra attributes to 'wpgeo_mashup' shortcode.
* Added extra style fix for background image colours.
* Updated Italian language files.

= WP Geo 3.2.2 =

* Added [wpgeo_mashup] shortcode. props RavanH.
* Added align attribute to shortcode.
* Try to fix themes with image background colours.
* get_wpgeo_map() now accepts width and height arguments.

= WP Geo 3.2.1 =

* Fix for category map markers all displaying the same tooltip title.
* Fixed 'wpgeo_markers' filter. Was being run to early before other plugin and theme functions.php had a chance to do anything.
* Add styles to try to override max-width images in themes which can cause map tiles to render incorrectly.
* Added settings link to plugins page.
* Escaped post title for use in JavaScript in get_wpgeo_map() and bumped minimum WordPress version up to 2.8. props RavanH.
* Fixed longitude and latitude shortcodes - they were the wrong way round.
* German language files updated.

= WP Geo 3.2 =

* Fix for tooltip not working in WordPress 3.0.
* Fix to allow maps to be shown on tag archive pages by Lee Willis.
* Fix for default_map_control setting not being saved correctly.
* Ensure default map location is populated, otherwise map does not display when editing a post in the admin.
* Don't try to show maps in feeds.
* Make sure includes are only included once.
* Added support for custom post types in WordPress 3.0.
* Added wpgeo_title() and get_wpgeo_title() template tags.
* Added wpgeo_post_map() and get_wpgeo_post_map() template tags.
* Added wpgeo_check_version() and wpgeo_check_db_version() for checking WP Geo version.
* Added 'wpgeo_markers' filter in preparation for being able to add new marker icons.
* Added 'wpgeo_marker_icon' filter. Allows you to override a marker icon based on post data and context.
* Added Simplified Chinese language.
* Language files updated.

= WP Geo 3.1.4 =

* Now uses new Google 3D map control.
* Adding text colour style to tooltip text in case default text colour is white.
* Prevent map from aligning marker centre on zoom. Added a link to centre manually instead.
* Moved a lot of JavaScript for the post edit page to external files.
* Code formatting clean-up, added code comments, files restructured.
* Added WPGeo_Marker class in preparation for better marker management.
* Added option to save post map centre point.
* Added wpgeo_map() and get_wpgeo_map() functions to display custom maps based on a WordPress query.
* Added 'wpgeo_init' action hook.
* Added 'wpgeo_the_content_map' filter.
* Added 'wpgeo_edit_post_map_fields' filter.
* Added 'wpgeo_point_title' filter.
* Added 'wpgeo_show_post_map' filter.
* Language files updated.

= WP Geo 3.1.3 =

* Oops, loads of bugs... Fix and release as 3.1.4

= WP Geo 3.1.2 =

* Fixed strange activation bug caused by includes/template.php (renaming the file seem to fix it).

= WP Geo 3.1.1 =

* Updated Danish, French and German translations.
* Google Maps locale is set based on WordPress locale.
* Added template tags wpgeo_longitude() and wpgeo_latitude().

= WP Geo 3.1 =

* Added custom marker title setting per post.
* Added settings for custom zoom and map type per post.
* Added widget zoom option.
* Added Default Map Location setting.
* Croatian language added.
* Bulgarian language added.
* Show warning if the marker images folder has not been created.
* Fix marker tooltip text to be compatible with qTranslate plugin.
* Added filter hook to override 'wpgeo_google_api_key'.

= WP Geo 3.0.9.2 =

* Belorussian language added.
* Added 'wpgeo_map_js_preoverlays' hook for developers to add javascript to maps.

= WP Geo 3.0.9.1 =

* Fix for GUnload() and GBrowserIsCompatible() being called when not available/required.
* Russian language added.

= WP Geo 3.0.9 =

* Added width and height attributes to shortcode.
* Added width and height attributes to category map.
* Danish language updated.

= WP Geo 3.0.8.1 =

* Fixed Google Javascript API loading via proxy issue.
* Tooltip.js filename now all lowercase.
* Added Changelog tab to read me file.

= WP Geo 3.0.8 =

* Additional Geo Feed control.
* Load maps from GeoRSS or KML data.
* Danish language added.
* Languages updated.

= WP Geo 3.0.7.1 =

* Firefox scrolling bug fixed.
* Added longitude and latitude shortcodes.
* Marker on maps in admin now update as you manually change longitude and latitude.
* Added setting to show maps on search result page.

= WP Geo 3.0.7 =

* Added map button in rich text editor.
* Added setting to turn on/off polylines.
* Added setting to set colour of polylines.
* Added setting to override polylines in Widget.
* Using v2.118 of Google Maps to prevent Javascript errors.
* Added WP Geo news feed widget on admin dashboard.
* Admin panels re-implemented using WordPress API.
* Widget map never zooms in more than default zoom setting.

= WP Geo 3.0.6.2 =

* 'Show Maps On' setting now works correctly when widget is active.
* Fixes paths if WordPress is installed in a subdirectory.

= WP Geo 3.0.6.1 =

* Include files removed (fix)

= WP Geo 3.0.6 =

* Marker Tooltip improved (can now but style via css)
* Spanish language added.

= WP Geo 3.0.5 =

* Added way to escape shortcode [wp_geo_map escape="true"]
* CSS Max-width image fixed added.
* Italian language added.
* German language updated.

= WP Geo 3.0.4 =

* Added French language support.

= WP Geo 3.0.3 =

* Added Geo Meta Tags on single post pages.
* Fixed issue when geo data was deleted in quick/bulk edit mode or when scheduled post when live.
* Fixed domain check to work with blogs in a subfolder of a domain.

= WP Geo 3.0.2 =

* Add language support.
* Various bug fixes.

= WP Geo 3.0.1 =

* Markers link to posts.
* Map scale and corner map settings now fixed.

= WP Geo 3.0 =

* Added more default control settings.
* Added custom marker images. 
* Added sidebar Widget.
* Improvements to Javascript loading including addition of external Javascript files.
* Loads jQuery to aid future plugin developments.
* No longer functions as a static class.

= WP Geo 2.1.2 =

* Added capability for feeds including georss points - for more information see http://www.georss.org. 

= WP Geo 2.1.1 =

* Adds external CSS stylesheet to fix image background colours on certain themes.
* Added 'wp_geo_map' class to map divs so they can be styled.

= WP Geo 2.1 =

* Added setting for default map zoom.
* Map in admin now defaults to preferred map type.
* Added screenshots.

= WP Geo 2.0 =

* Added options to display posts maps on category and archive pages.

= WP Geo 1.3 =

* Added options to set default width and height for maps.

= WP Geo 1.2 =

* Added [wp_geo_map] Shortcode to add map within post content.

= WP Geo 1.1 =

* Added option to set map type.
* Added option to set wether maps appear at the top or bottom of posts.

== Languages ==

WP Geo is currently available in the following languages:

* Belorussian (by Ilyuha)
* Bulgarian (by Roman Rachkov)
* Chinese, Simplified (by Steen Chow)
* Croatian (by Andrija Papec)
* English (default)
* Danish (by Georg)
* French (by Alain Messin)
* German (by Ivan Graf)
* Italian (by Diego Pierotto)
* Russian (by Fat Cower)
* Spanish (by Alberto)
