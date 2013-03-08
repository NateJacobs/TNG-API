# TNG API WordPress Plugin Tutorial

In this tutorial we will be creating a plugin to grab information about an individual person or family from the TNG database. Once we have the data we will then display it in our theme. The plugin will focus mostly on getting the data out of TNG. I’ll leave it up to you to add it to your theme. 

We will be using the following bits from WordPress and TNG to build the plugin. 

### TNG Files

* api_library.php
* api_person.php
* api_family.php
* api_checklogin.php

### WordPress Classes

* [HTTP API](http://codex.wordpress.org/HTTP_API)
* [WP Error](http://codex.wordpress.org/Class_Reference/WP_Error)

### WordPress Functions

* [wp_remote_get()](http://codex.wordpress.org/Function_API/wp_remote_get)
* [wp_remote_retrieve_response_code()](http://codex.wordpress.org/Function_API/wp_remote_retrieve_response_code)
* [wp_remote_retrieve_response_message()](http://codex.wordpress.org/Function_API/wp_remote_retrieve_response_message)
* [wp_remote_retrieve_body()](http://codex.wordpress.org/Function_API/wp_remote_retrieve_body) 
* [load_plugin_textdomain()](http://codex.wordpress.org/Function_Reference/load_plugin_textdomain)
* [wp_parse_args]()
* [build_query](http://codex.wordpress.org/Function_Reference/build_query)
* [urlencode_deep](http://codex.wordpress.org/Function_Reference/urlencode_deep)

## Planning the Plugin

1. Make a call to the TNG API with the family or person ID we are looking for
2. Check if the call returns an error
3. Get the returned data into an object
4. Display the data to the end user
5. Future possibilities

## Building the Plugin

You can download the full plugin from this GitHub repository. I would, however, recommend following along and writing the code yourself.

1. Create a folder named ``` tng-api ``` in your ``` wp-content/plugins ``` directory
2. Create a new file within the new folder named ``` tng-api.php ```

#### Create the Plugin Class

``` 
<?php

/**
 *	Plugin Name: 	Intro to TNG API
 *	Description: 	A brief tutorial on using the WordPress HTTP API to acces your TNG site API
 *	Version: 		1.0
 *	Date:			3/6/13
 *	Author:			Nate Jacobs
 *
 */
 

/** 
*	TNG API Access
*
*	
*
*	@author		Nate Jacobs
*	@date		3/6/13
*	@since		1.0
*/
class TNGAPIAccess
{
	/** 
	*	Construct Method
	*
	*	
	*
	*	@author		Nate Jacobs
	*	@date		3/6/13
	*	@since		1.0
	*
	*	@param		
	*/
	public function __construct()
	{
		add_action('init', array( $this, 'localization' ), 1 );
	}
}
```