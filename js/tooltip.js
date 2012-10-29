
var Tooltip_mouse_x = 0;
var Tooltip_mouse_y = 0;

/**
 * Tooltip Constructor
 * @param {GMarker} marker
 * @param {String} text
 */
function Tooltip(marker, text) {
	this.marker_ = marker;
	this.text_ = text;
}
	
(function($){
	
	/**
	 * Tooltip: Show Method
	 */	
	Tooltip.prototype.show = function() {
		$('#tooltip2').text(this.text_).show();
			
		var left = Tooltip_mouse_x - ($('#tooltip2').width() / 3);
		var top = Tooltip_mouse_y - 25 - $('#tooltip2').height();
		
		if (left < 5)
			left = 5;
		if (top < 5)
			top = 5;
		
		$('#tooltip2').css('top', top).css('left', left);
	}
	
	/**
	 * Tooltip: Hide Method
	 */
	Tooltip.prototype.hide = function() {
		$('#tooltip2').text('').hide();
	}
	
	/**
	 * jQuery Tooltip Init.
	 */
	$(document).ready(function() {
		$("body").append('<p id="tooltip2"></p>');
		$('#tooltip2').hide();
		$("body").mousemove(function(e) {
			var left = 5;
			var top = 5;
			
			Tooltip_mouse_x = e.pageX;
			Tooltip_mouse_y = e.pageY;
			
			if ($('#tooltip2').css('display') != 'none') {
				left = e.pageX - ($('#tooltip2').width() / 3);
				top = e.pageY - 25 - $('#tooltip2').height();
				
				if (left < 5)
					left = 5;
				if (top < 5)
					top = 5;
			}
			$('#tooltip2').css('top', top).css('left', left);
		});
		
	});

})(jQuery);
