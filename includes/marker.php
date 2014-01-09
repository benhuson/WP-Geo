<?php

/**
 * Marker Class
 */
class WPGeo_Marker {

	// Reference
	var $id          = null;
	var $name        = null;
	var $description = null;

	// Marker Properties
	var $width   = 20;
	var $height  = 34;
	var $anchorX = 10;
	var $anchorY = 34;
	var $image   = null;
	var $shadow  = null;

	/**
	 * Constructor
	 */
	function WPGeo_Marker( $id, $name, $description, $width, $height, $anchorX, $anchorY, $image, $shadow = null ) {
		$this->set_id( $id );
		$this->set_name( $name );
		$this->set_description( $description );
		$this->set_size( $width, $height );
		$this->set_anchor( $anchorX, $anchorY );
		$this->set_image( $image );
		$this->set_shadow( $shadow );
	}

	/**
	 * Set the marker's ID.
	 *
	 * @param  string  $id  ID.
	 */
	function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Set the marker's name.
	 *
	 * @param  string  $name  Marker name.
	 */
	function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Set the marker's description.
	 *
	 * @param  string  $description  Description.
	 */
	function set_description( $description ) {
		$this->description = $description;
	}

	/**
	 * Set Size
	 * Sets the marker's width and height dimensions.
	 *
	 * @param  int  $width   Width.
	 * @param  int  $height  Height.
	 */
	function set_size( $width, $height ) {
		$this->width  = $width;
		$this->height = $height;
	}

	/**
	 * Set Anchor
	 * Sets the marker's anchor coordinates.
	 *
	 * @param  int  $x
	 * @param  int  $y
	 */
	function set_anchor( $x, $y ) {
		$this->anchorX = $x;
		$this->anchorY = $y;
	}

	/**
	 * Set the marker's image file.
	 *
	 * @param  string  $image  Image URL.
	 */
	function set_image( $image ) {
		$this->image = $image;
	}

	/**
	 * Set Shadow
	 * Sets the marker's shadow image file.
	 *
	 * @param  string  $shadow  Shadow image URL.
	 */
	function set_shadow( $shadow ) {
		$this->shadow = $shadow;
	}

	/**
	 * Get the JavaScript that defines a marker.
	 *
	 * @return  string  JavaScript.
	 */
	function get_javascript() {
		global $wpgeo;
		$icon = apply_filters( $wpgeo->get_api_string( 'wpgeo_api_%s_markericon' ), '', $this );
		if ( ! empty( $icon ) ) {
			return "var wpgeo_icon_" . $this->id . " = " . $icon . ";";
		}
		return $icon;
	}		

	/**
	 * Get Admin Display
	 * Gets the HTML to display the marker in the admin.
	 *
	 * @return  string  HTML.
	 */
	function get_admin_display() {
		return '<tr valign="top">
				<th scope="row">' . $this->name . '</th>
				<td>
					<p style="margin:0px; background-image:url(' . $this->shadow . '); background-repeat:no-repeat;"><img src="' . $this->image . '"></p>
					<p style="margin:10px 0 0 0;">' . $this->description . '<br />
						{ width:' . $this->width . ', height:' . $this->height . ', anchorX:' . $this->anchorX . ', anchorY:' . $this->anchorY . ' }
					</p>
				</td>
			</tr>';
	}

}
