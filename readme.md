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
3. Create a new folder within your plugin folder named ``` languages ```

#### What is the TNG API

The TNG API, as it stands today, is simply a way to retrieve basic information about a particular person or family in your TNG database. The API does not extend beyond that. You will need to know the family or person ID and the tree they belong in to make it work. There is an authentication piece, but it does not appear to function correctly. I will be following up to make sure I understand its purpose.

To get data from TNG you will need two URLS.

* http://YOUR-TNG-URL/api_person.php?personID=I1&tree=TREE
* http://YOUR-TNG-URL/api_family.php?familyID=F1&tree=TREE

Those two files, combined with api_library.php process your request and return a JSON encoded response. [JSON](http://en.wikipedia.org/wiki/JSON) is a text-based system for conveying structured data. It is human readable and easy for machines to process. TNG returns JSON similar to this for a person query.

```json
{
  "id": "I1",
  "tree": "tree",
  "firstname": "firstname",
  "lnprefix": "",
  "lastname": "lastname",
}
```

We will turn this JSON output into an object to manipulate later.

#### Stub out the Plugin Class

``` php

<?php

/**
 *	Plugin Name: 	Intro to TNG API
 *	Description: 	A brief tutorial on using the WordPress HTTP API to acces your TNG site API
 *	Version: 		1.0
 *	Date:			3/6/13
 *	Author:			Nate Jacobs
 *
 */
 
class TNGAPIAccess
{
	/** Constructor **/
	public function __construct()
	{
		add_action('init', array( $this, 'localization' ), 1 );
	}
	
	/** Localization, i.e. translation **/
	public function localization() 
	{
  		load_plugin_textdomain( 'tng_api', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
	}

	/** Remote Request **/

	/** Build URL **/
	
	/** Person Query **/
	
	/** Family Query **/
}
$GLOBALS['tng_api'] = new TNGAPIAccess();
```

There is not much to the class at this point, but you can see I have sketched out the four other methods we will need to build. I will not cover localization in this tutorial. For that you can read Otto's excellent post, [Internationalization: You’re probably doing it wrong] (http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/).

#### Make the Request

Next we will create the method responsible for actually making the API request. We will pass a url variable to it later from our person and family queries. 

``` php
/** 
	*	Remote Connect
	*
	*	Make the connection to TNG
	*
	*	@author		Nate Jacobs
	*	@date		3/6/13
	*	@since		1.0
	*
	*	@param		array $url
	*/
	private function remote_request( $url )
	{
		// Get the url from the TNG plugin
		// If you don't have the TNG plugin installed simply change this line to
		// $this->tng_url = 'http://YOUR-TNG-URL';
		$this->tng_url = substr( get_option( 'mbtng_url_to_admin' ), 0, -9 );

		// Make the request to the tng url
		$response = wp_remote_get( $this->tng_url.$url );
		
		// Get the response code
		// e.g. 200
		$response_code = wp_remote_retrieve_response_code( $response );
		// Get the response message
		// e.g. OK
		$response_message = wp_remote_retrieve_response_message( $response );
		// Get the response body
		// e.g. JSON string
		$response_body = wp_remote_retrieve_body( $response );
		
		// Get the next five characters in the string after the first 2
		$error = substr( $response_body, 2, 5);

		// Is the response code not a 200 and the message is not empty?
		if( 200 != $response_code && ! empty( $response_message ) )
		{
			// Return a WP_Error class
			return new WP_Error( $response_code, __( 'Don\'t Panic! Something went wrong and TNG didn\'t reply.', 'tng_api' ) );
		}
		// Is the response code not 200?
		elseif( 200 != $response_code )
		{
			// Return a WP_Error class
			return new WP_Error( $response_code, __( 'Unknown error occurred', 'tng_api' ) );
		}
		// Is the $error variable the same as the string: error?
		elseif( strcmp( $error, 'error' ) === 0 )
		{
			// If so, return a WP_Error class
			// Nothing was found
			return new WP_Error( $response_code, sprintf( __( '%s', 'tng_api' ), substr( $response_body, 10, -2 ) ) );
		}
		// Okay, everything worked out
		else
		{	
			// Return the results
			return $response_body;
		}

```

There is plenty of error testing built in to make sure we only return a valid response when there is actual data.
