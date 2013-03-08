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

	/** 
	 *	Localization
	 *
	 *	Declare text domain to use in translation.
	 *
	 *	@author		Nate Jacobs
	 *	@date		3/6/13
	 *	@since		1.0
	 *
	 *	@param		null
	 */
	public function localization() 
	{
  		load_plugin_textdomain( 'tng_api', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
	}
	
	/** 
	*	Remote Connect
	*
	*	
	*
	*	@author		Nate Jacobs
	*	@date		3/6/13
	*	@since		1.0
	*
	*	@param		
	*/
	private function remote_request( $url )
	{
		$this->tng_url = substr( get_option( 'mbtng_url_to_admin' ), 0, -9 );

		$response = wp_remote_get( $this->tng_url.$url );
// catch if body is error

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body = wp_remote_retrieve_body( $response );

		$error = substr( $response_body, 2, 5);

		if( 200 != $response_code && ! empty( $response_message ) )
		{
			return new WP_Error( $response_code, __( 'Don\'t Panic! Something went wrong and TNG didn\'t reply.', 'tng_api' ) );
		}
		elseif( 200 != $response_code )
		{
			return new WP_Error( $response_code, __( 'Unknown error occurred', 'tng_api' ) );
		}
		elseif( strcmp( $error, 'error' ) === 0 )
		{
			return new WP_Error( $response_code, sprintf( __( '%s', 'tng_api' ), substr( $response_body, 10, -2 ) ) );
		}
		else
		{
			return $response_body;
		}

	}

	/** 
	*	Build URL
	*
	*	
	*
	*	@author		Nate Jacobs
	*	@date		3/6/13
	*	@since		1.0
	*
	*	@param		
	*/
	public function build_url( $type = '', $args = '' )
	{
		if( empty( $type ) )
		{
			return new WP_Error( 'no-type-specified', __( 'No type of request specified.', 'tng_api' ) );
		}
		
		$default = array( 
			'id'	=> '',
			'tree'	=> '',
		);
		
		$args = wp_parse_args( $args, $default );
		
		extract( $args, EXTR_SKIP );

		if( $type == 'person' )
		{
			$params = build_query( 
				urlencode_deep( 
					array( 
						'personID'	=> 'I'.$id,
						'tree'		=> $tree
					)
				)
			);
		}
		elseif( $type == 'family' )
		{
			$params = build_query( 
				urlencode_deep( 
					array( 
						'familyID'	=> 'F'.$id,
						'tree'		=> $tree
					)
				)
			);
		}
		
		return $params;

	}
	
	/** 
	*	Person Query
	*
	*	
	*
	*	@author		Nate Jacobs
	*	@date		3/6/13
	*	@since		1.0
	*
	*	@param		int	$id person ID
	*	@param		string $tree family tree the person belongs to
	*/
	public function person_query( $id, $tree )
	{
		$params = $this->build_url( 'person', array( 'id' => $id, 'tree' => $tree ) );
		$response = $this->remote_request( 'api_person.php?'.$params );
		
		if( is_wp_error( $response ) )
		{
			return $response;
		}
		
		return json_decode( $response );
	}
	
	/** 
	*	Family Query
	*
	*	
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
	public function family_query( $id, $tree )
	{
		$params = $this->build_url( 'family', array( 'id' => $id, 'tree' => $tree ) );
		$response = $this->remote_request( 'api_family.php?'.$params );
		
		if( is_wp_error( $response ) )
		{
			return $response;
		}
		
		return json_decode( $response );
	}
}

$GLOBALS['tng_api'] = new TNGAPIAccess();