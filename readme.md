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

You can download the full plugin from this GitHub [repository](https://github.com/NateJacobs/TNG-API/). I would, however, recommend following along and writing the code yourself.

1. Create a folder named ``` tng-api ``` in your ``` wp-content/plugins ``` directory
2. Create a new file within the new folder named ``` tng-api.php ```
3. Create a new folder within your plugin folder named ``` languages ```

#### What is the TNG API

The TNG API, as it stands today, is simply a way to retrieve basic information about a particular person or family in your TNG database. The API does not extend beyond that. You will need to know the family or person ID and the tree they belong in to make it work. There is an authentication piece, but it does not appear to function correctly. I will be following up to make sure I understand its purpose.

To get data from TNG you will need two URLS.

* http://YOUR-TNG-URL/api_person.php?personID=INUMBER&tree=TREE
* http://YOUR-TNG-URL/api_family.php?familyID=FNUMBER&tree=TREE

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

```php

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

	/** Remote Connect **/

	/** Build URL **/
	
	/** Person Query **/
	
	/** Family Query **/
}
$GLOBALS['tng_api'] = new TNGAPIAccess();
```

There is not much to the class at this point, but you can see I have sketched out the four other methods we will need to build. I will not cover localization in this tutorial. For that you can read Otto's excellent post, [Internationalization: You’re probably doing it wrong] (http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/).

#### Make the Request

Next we will create the method responsible for actually making the API request. We will pass a url variable to it later from our person and family queries. 

```php
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
)
```
#### Build the URL

There is plenty of error testing built in to make sure we only return a valid response when there is actual data. Next, we need to create the method to build the parameters of the url. This method takes a string and an array as arguments. We use ``` wp_parse_args() ``` to take a default array containing ```id``` and ```tree``` and merge them with the array passed. The array items passed will override the default values. You could add your default tree to the ```$defaults``` array and not have to worry about passing it to the method each time.

```php
private function build_url( $type = '', $args = '' )
{
	// Is the type of URL to construct empty?
	if( empty( $type ) )
	{
		// If so, return that error
		return new WP_Error( 'no-type-specified', __( 'No type of request specified.', 'tng_api' ) );
	}
	
	// Set up the defaults for the array
	$default = array( 
		'id'	=> '',
		'tree'	=> '',
	);
	
	// Take the passed arguments and merge together with the default arguments
	$args = wp_parse_args( $args, $default );

	// Declare each item in the $args array as its own variable
	extract( $args, EXTR_SKIP );
```

Part of the same function is used to determine which url the method should return. The two if statements look at the ```$type``` string passed.

```php
	// Is the type of URL a person?
	if( $type == 'person' )
	{
		// Build a proper URL ?personID=I1&tree=TREENAME
		$params = build_query( 
			urlencode_deep( 
				array( 
					'personID'	=> 'I'.$id,
					'tree'		=> $tree
				)
			)
		);
	}
	
	// Is the type of URL a family group?
	elseif( $type == 'family' )
	{
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
```
The final step is to create the two query methods. 

#### Person Query

The first is a person query. The method accepts two strings as arguements. It then passes those variables as part of an array to the ```build_url()``` we created earlier. If an error is created during the ```build_url()``` process the error is returned and we stop working.

```php
public function person_query( $id = '', $tree = '' )
{
	// Pass the ID and tree to the build_url method
	$params = $this->build_url( 'person', array( 'id' => $id, 'tree' => $tree ) );
	
	// Was an error returned?
	if( is_wp_error( $params ) )
		return $params;
```
Once the url is built we then pass it off to the ```remote_request()``` method we defined earlier. Once again, we check for an error. Once it is determined that no error is recieved we decode the JSON returned from TNG and return an object to work with.

```php
// Make the API call
$response = $this->remote_request( 'api_person.php?'.$params );
	
// Check if the response returned an error
if( is_wp_error( $response ) )
{	
	// If so, send the error on through
	return $response;
}
		
// Return an object containing the response
return json_decode( $response );
}
```
#### Family Query

This query is the same as a person.

```php
public function family_query( $id, $tree )
{
	// Pass the ID and tree to the build_url method
	$params = $this->build_url( 'family', array( 'id' => $id, 'tree' => $tree ) );
		
	// Was an error returned?
	if( is_wp_error( $params ) )
		return $params;
	
	// Make the API call
	$response = $this->remote_request( 'api_family.php?'.$params );
	
	// Check if the response returned an error
	if( is_wp_error( $response ) )
	{
		// If so, send the error on through
		return $response;
	}
		
	// Return an object containing the response
	return json_decode( $response );
}
```

#### Working the data

This section of code is used to display the data returned from TNG. You will place this someplace within your theme files. First we need to get the reference to the plugin class.

```php
global $tng_api;
```

Next we call the person query.
```php
$person = $tng_api->person_query( '4', 'jacobs' );
```
First we check if an error is returned, if it is echo out the message.

```php
if( is_wp_error( $person ) )
{
	echo $person->get_error_message().'<br>';
}
else
{
	// Do something with the data
}
```

This returns the following object (my great grandmother)
```php 
object(stdClass)#220 (12) {
  ["id"]=>		string(2) "I4"
  ["tree"]=>		string(6) "Jacobs"
  ["firstname"]=>	string(12) "Bridget Mary"
  ["lnprefix"]=>	string(0) ""
  ["lastname"]=>	string(5) "Boyce"
  ["title"]=>		string(0) ""
  ["prefix"]=>		string(0) ""
  ["suffix"]=>		string(0) ""
  ["nickname"]=>	string(0) ""
  ["gender"]=>		string(1) "F"
  ["changedate"]=>	string(11) "24 Jul 2012"
  ["events"]=>		array(4) {
    [0]=>
    object(stdClass)#226 (5) {
      ["tag"]=>		string(4) "BIRT"
      ["type"]=>	string(0) ""
      ["date"]=>	string(11) "23 FEB 1902"
      ["place"]=>	string(45) "24 John's St, Limerick, Co. Limerick, Ireland"
      ["fact"]=>	string(0) ""
    }
    [1]=>
    object(stdClass)#229 (5) {
      ["tag"]=>		string(3) "CHR"
      ["type"]=>	string(0) ""
      ["date"]=>	string(0) ""
      ["place"]=>	string(34) "St. John's, Limerick City, Ireland"
      ["fact"]=>	string(0) ""
    }
    [2]=>
    object(stdClass)#230 (5) {
      ["tag"]=>		string(4) "DEAT"
      ["type"]=>	string(0) ""
      ["date"]=>	string(8) "AUG 1952"
      ["place"]=>	string(32) "Niagara Falls, Niagara, New York"
      ["fact"]=>	string(0) ""
    }
    [3]=>
    object(stdClass)#227 (5) {
      ["tag"]=>		string(4) "BURI"
      ["type"]=>	string(0) ""
      ["date"]=>	string(0) ""
      ["place"]=>	string(32) "Niagara Falls, Niagara, New York"
      ["fact"]=>	string(0) ""
    }
  }
}
```
The family query works the same way.
```php
$family = $tng_api->family_query( '800', 'jacobs' );
```
Check if an error is returned, if it is echo out the message.

```php
if( is_wp_error( $person ) )
{
	echo $family->get_error_message().'<br>';
}
else
{
	// Do something with the data
}
```
This returns a similary object (my grandmother's parents/siblings)
```php
object(stdClass)#231 (6) {
  ["id"]=>  		string(4) "F800"
  ["tree"]=>  		string(6) "Jacobs"
  ["father"]=>  	
  object(stdClass)#228 (12) {
    ["id"]=>    	string(5) "I1118"
    ["tree"]=>    	string(6) "Jacobs"
    ["firstname"]=>    	string(12) "Lewis Marion"
    ["lnprefix"]=>    	string(0) ""
    ["lastname"]=>    	string(10) "Montgomery"
    ["title"]=>    	string(0) ""
    ["prefix"]=>    	string(0) ""
    ["suffix"]=>    	string(0) ""
    ["nickname"]=>    	string(0) ""
    ["gender"]=>    	string(1) "M"
    ["changedate"]=>	string(19) "2012-11-23 00:00:00"
    ["events"]=>	array(2) {
      [0]=>
      object(stdClass)#232 (5) {
        ["tag"]=>        string(4) "BIRT"
        ["type"]=>       string(0) ""
        ["date"]=>       string(10) "9 APR 1877"
        ["place"]=>      string(31) "Assumption, Christian, Illinois"
        ["fact"]=>       string(0) ""
      }
      [1]=>
      object(stdClass)#233 (5) {
        ["tag"]=>       string(4) "DEAT"
        ["type"]=>      string(0) ""
        ["date"]=>      string(11) "30 MAR 1956"
        ["place"]=>     string(0) ""
        ["fact"]=>      string(0) ""
      }
    }
  }
  ["mother"]=>
  object(stdClass)#234 (12) {
    ["id"]=>		string(5) "I1119"
    ["tree"]=>		string(6) "Jacobs"
    ["firstname"]=>	string(10) "Anna Laura"
    ["lnprefix"]=>	string(0) ""
    ["lastname"]=>	string(6) "Thayer"
    ["title"]=>		string(0) ""
    ["prefix"]=>	string(0) ""
    ["suffix"]=>	string(0) ""
    ["nickname"]=>	string(0) ""
    ["gender"]=>	string(1) "F"
    ["changedate"]=>	string(19) "2012-11-25 00:00:00"
    ["events"]=>	array(2) {
      [0]=>
      object(stdClass)#235 (5) {
        ["tag"]=>       string(4) "BIRT"
        ["type"]=>      string(0) ""
        ["date"]=>      string(10) "1 OCT 1876"
        ["place"]=>     string(6) "Kansas"
        ["fact"]=>      string(0) ""
      }
      [1]=>
      object(stdClass)#236 (5) {
        ["tag"]=>       string(4) "DEAT"
        ["type"]=>      string(0) ""
        ["date"]=>      string(11) "25 FEB 1955"
        ["place"]=>     string(0) ""
        ["fact"]=>      string(0) ""
      }
    }
  }
  ["events"]=>		array(1) {
    [0]=>
    object(stdClass)#237 (5) {
      ["tag"]=>		string(4) "MARR"
      ["type"]=>	string(0) ""
      ["date"]=>	string(10) "8 JUN 1908"
      ["place"]=>	string(0) ""
      ["fact"]=>	string(0) ""
    }	
  }
  ["children"]=>	array(2) {
    [0]=>
    object(stdClass)#238 (12) {
      ["id"]=>		string(5) "I1120"
      ["tree"]=>	string(0) ""
      ["firstname"]=>	string(13) "Marietta Ruth"
      ["lnprefix"]=>	string(0) ""
      ["lastname"]=>	string(10) "Montgomery"
      ["title"]=>	string(0) ""
      ["prefix"]=>	string(0) ""
      ["suffix"]=>	string(0) ""
      ["nickname"]=>	string(0) ""
      ["gender"]=>	string(1) "F"
      ["changedate"]=>	string(0) ""
      ["events"]=>	array(2) {
        [0]=>
        object(stdClass)#239 (5) {
          ["tag"]=>     string(4) "BIRT"
          ["type"]=>    string(0) ""
          ["date"]=>	string(10) "6 JUN 1910"
          ["place"]=>	string(24) "Columbus, Franklin, Ohio"
          ["fact"]=>	string(0) ""
        }
        [1]=>
        object(stdClass)#240 (5) {
          ["tag"]=>     string(4) "DEAT"
          ["type"]=>	string(0) ""
          ["date"]=>	string(10) "8 OCT 1977"
          ["place"]=>	string(25) "Cleveland, Cuyahoga, Ohio"
          ["fact"]=>	string(0) ""
        }
      }
    }
    [1]=>
    object(stdClass)#241 (12) {
      ["id"]=>		string(4) "I650"
      ["tree"]=>	string(0) ""
      ["firstname"]=>	string(13) "Emily Frances"
      ["lnprefix"]=>	string(0) ""
      ["lastname"]=>	string(10) "Montgomery"
      ["title"]=>	string(0) ""
      ["prefix"]=>	string(0) ""
      ["suffix"]=>	string(0) ""
      ["nickname"]=>	string(0) ""
      ["gender"]=>	string(1) "F"
      ["changedate"]=>  string(0) ""
      ["events"]=>      array(2) {
        [0]=>
        object(stdClass)#242 (5) {
          ["tag"]=>     string(4) "BIRT"
          ["type"]=>	string(0) ""
          ["date"]=>	string(11) "20 APR 1919"
          ["place"]=>	string(4) "Ohio"
          ["fact"]=>	string(0) ""
        }
        [1]=>
        object(stdClass)#243 (5) {
          ["tag"]=>	string(4) "DEAT"
          ["type"]=>	string(0) ""
          ["date"]=>	string(11) "20 JUN 2004"
          ["place"]=>	string(35) "Sandwich, Barnstable, Massachusetts"
          ["fact"]=>	string(0) ""
        }
      }
    }
  }
}
```
#### Displaying the Data

You can now take these objects and loop through them with foreach statements to get the data you are looking for. For a person query if you just want the person first and last name you can 
```php 
echo $person->firstname.' '.$person->lastname;
``` 

This will display ```Bridget Mary Boyce``` in my person query. If you are looking for the DOB you will need to use a foreach loop.

```php
foreach( $person->events as $events )
{
	if( $events->tag === 'BIRT' )
		echo $events->date;
}
```

This will display ```23 FEB 1902```.

#### Future Potential

I mentioned in the beginning there is an authentication file in TNG. This is supposed to provide a simple authentication method for the API. From what I can tell, it is not in fact doing that. All you have to do is pass a parameter of ```&tngusername=USERNAME``` as part of the parameter. TNG will take that username and run a check on the database to see if the user exists. As it stands now the code does check the TNG database, but does not do any authentication. In fact, it actually sets the abilty to see living data to false even if the username passed has rights to see living person information. 

The API needs quite a bit of work before it becomes a fully functional API to your data in the TNG database. However, it is still useful if you want to keep TNG as a seperate site and simply display little bits of information about your family on your WordPress site. 

You could also create a link to the person on your TNG site from your WordPress site with the following.

```php

echo '<a href="http://YOUR-TNG-URL/getperson.php?personID='.$person->id.'&tree='.$person->tree.'">'.$person->firstname.' '.$person->lastname.'</a>';

```

There is also the potential of creating an API using the WordPress ```$wpdb``` class. I have toyed with this idea several times. You can look at three classes in my [Family Roots plugin](https://github.com/NateJacobs/Family-Roots/tree/master/inc) (tng-db.php, utilities.php and users.php) to see how I create the connection to the TNG database and make a few calls to it. 

### Feedback

I would love any feedback you might have about this tutorial. Also please let me know if you find any code errors.
