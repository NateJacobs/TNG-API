<?php

/*
Plugin Name: Intro to TNG API
Description: A brief tutorial on using the WordPress HTTP API to acces your TNG site API
Version: 1.1
Author: Nate Jacobs
Author URI: http://natejacobs.com
License: GPL2 or later
Text Domain: tng-api
Domain Path: /languages
GitHub Plugin URI: NateJacobs/TNG-API
*/

/* Copyright 2014 Nate Jacobs (nate@natejacobs.org)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/** 
 *	TNG API Access
 * 
 *	@author		Nate Jacobs
 *	@date		3/6/13
 *	@since		1.0
 */
class TNGAPIAccess {
	/** 
	 *	Get the ball rolling
	 *
	 *	@author		Nate Jacobs
	 *	@date		3/6/13
	 *	@since		1.0
	 */
	public function __construct() {
		add_action('init', array($this, 'localization'), 1);
	}

	/** 
	 *	Declare text domain to use in translation.
	 *
	 *	@author		Nate Jacobs
	 *	@date		3/6/13
	 *	@since		1.0
	 */
	public function localization() {
  		load_plugin_textdomain('tng-api', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}
	
	/** 
	 *	Make the connection to TNG
	 *
	 *	@author		Nate Jacobs
	 *	@date		3/6/13
	 *	@since		1.0
	 *
	 *	@param		array $url
	 */
	private function remote_request($url){
		// Get the url from the TNG plugin
		$this->tng_url = substr(get_option('mbtng_url_to_admin'), 0, -9);

		// Make the request to the tng url
		$response = wp_remote_get($this->tng_url.$url);
		
		// Get the response code
		// e.g. 200
		$response_code = wp_remote_retrieve_response_code($response);
		// Get the response message
		// e.g. OK
		$response_message = wp_remote_retrieve_response_message($response);
		// Get the response body
		// e.g. JSON string
		$response_body = wp_remote_retrieve_body($response);
		
		// Get the next five characters in the string after the first 2
		$error = substr($response_body, 2, 5);

		// Is the response code not a 200 and the message is not empty?
		if(200 != $response_code && ! empty($response_message)) {
			// Return a WP_Error class
			return new WP_Error($response_code, __('Don\'t Panic! Something went wrong and TNG didn\'t reply.', 'tng-api'));
		} elseif( 200 != $response_code ) {
			// Is the response code not 200?
			// Return a WP_Error class
			return new WP_Error($response_code, __('Unknown error occurred', 'tng-api'));
		} elseif(strcmp($error, 'error') === 0) {
			// Is the $error variable the same as the string: error?
			// If so, return a WP_Error class
			// Nothing was found
			return new WP_Error($response_code, sprintf(__('%s', 'tng-api'), substr($response_body, 10, -2)));
		} else {
			// Okay, everything worked out
			// Return the results
			return $response_body;
		}
	}

	/** 
	 *	Take an array and build the correctly encoded URL string
	 *
	 *	@author		Nate Jacobs
	 *	@date		3/6/13
	 *	@since		1.0
	 *
	 *	@param		string	$type	The type of request.
	 *	@param		array	$args	The array of arguments to add to the url.
	 */
	private function build_url($type = '', $args = '') {
		// Is the type of URL to construct empty?
		if(empty($type)) {
			// If so, return that error
			return new WP_Error('no-type-specified', __('No type of request specified.', 'tng-api'));
		}
		
		// Set up the defaults for the array
		$default = array( 
			'id'	 => '',
			'tree' => '',
		);
		
		// Take the passed arguments and merge together with the default arguments
		$args = wp_parse_args($args, $default);
		
		// Declare each item in the $args array as its own variable
		extract($args, EXTR_SKIP);

		// Is the type of URL a person?
		if($type == 'person') {
			// Build a proper URL ?personID=I1&tree=TREENAME
			$params = build_query( 
				urlencode_deep( 
					array( 
						'personID'	=> 'I'.$id,
						'tree'		=> $tree
					)
				)
			);
		} elseif($type == 'family') {
			// Is the type of URL a family?
			// Build a proper URL ?familyID=I1&tree=TREENAME
			$params = build_query( 
				urlencode_deep( 
					array( 
						'familyID'	=> 'F'.$id,
						'tree'		=> $tree
					)
				)
			);
		}
		
		// Return the parameters
		return $params;
	}
	
	/** 
	 *	Used to get information about a specific person
	 *
	 *	@author		Nate Jacobs
	 *	@date		3/6/13
	 *	@since		1.0
	 *
	 *	@param		int	$id person ID
	 * 	@param		string $tree family tree the person belongs to
	 */
	public function person_query($id, $tree) {
		// Pass the ID and tree to the build_url method
		$params = $this->build_url('person', array('id' => $id, 'tree' => $tree));
		
		// Was an error returned?
		if(is_wp_error($params)) {
			return $params;
		}
		
		// Make the API call
		$response = $this->remote_request('api_person.php?'.$params);
		
		// Check if the response returned an error
		if(is_wp_error($response)) {
			// If so, send the error on through
			return $response;
		}
		
		// Return an object containing the response
		return json_decode($response);
	}
	
	/** 
	 *	Used to get information about a family group
	 *
	 *	@author		Nate Jacobs
	 *	@date		3/6/13
	 *	@since		1.0
	 *
	 *	@param		int	$id family ID
	 *	@param		string $tree family tree the family belongs to
	 *
	 *	@return		object
	 */
	public function family_query($id, $tree) {
		// Pass the ID and tree to the build_url method
		$params = $this->build_url('family', array('id' => $id, 'tree' => $tree));
		
		// Was an error returned?
		if(is_wp_error($params)) {
			return $params;
		}
		
		// Make the API call
		$response = $this->remote_request('api_family.php?'.$params);
		
		// Check if the response returned an error
		if(is_wp_error($response)) {
			// If so, send the error on through
			return $response;
		}
		
		// Return an object containing the response
		return json_decode($response);
	}
}

$GLOBALS['tng-api'] = new TNGAPIAccess();