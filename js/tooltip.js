

var Tooltip_mouse_x = 0;
var Tooltip_mouse_y = 0;



/**
* Tooltip Constructor
* @param {GMarker} marker
* @param {String} text
*/
function Tooltip(marker, text)
{
	this.marker_ = marker;
	this.text_ = text;
}



/**
* Tooltip: Show Method
*/	
Tooltip.prototype.show = function()
{
	
	jQuery('#tooltip2').text(this.text_);
	jQuery('#tooltip2').show();
		
	var left = Tooltip_mouse_x - (jQuery('#tooltip2').width() / 3);
	var top = Tooltip_mouse_y - 25 - jQuery('#tooltip2').height();
	
	if (left < 5)
		left = 5;
	if (top < 5)
		top = 5;
	
	jQuery('#tooltip2').css('left', left);
	jQuery('#tooltip2').css('top', top);
	
}



/**
* Tooltip: Hide Method
*/
Tooltip.prototype.hide = function()
{
	jQuery('#tooltip2').text("");
	jQuery('#tooltip2').hide();
}



/**
* jQuery Tooltip Init.
*/
jQuery(document).ready(function() {

	t = "";
	jQuery("body").append("<p id='tooltip2'>" + t + "</p>");
	jQuery('#tooltip2').hide();
	
	jQuery("body").mousemove(function(e)
	{
	
		Tooltip_mouse_x = e.pageX;
		Tooltip_mouse_y = e.pageY;
		
		var left = 5;
		var top = 5;
		
		if (jQuery('#tooltip2').css('display') != 'none')
		{
		
			var left = e.pageX - (jQuery('#tooltip2').width() / 3);
			var top = e.pageY - 25 - jQuery('#tooltip2').height();
			
			if (left < 5)
				left = 5;
			if (top < 5)
				top = 5;
			
		}
		
		jQuery('#tooltip2').css('left', left);
		jQuery('#tooltip2').css('top', top);
		
	});
	
});


