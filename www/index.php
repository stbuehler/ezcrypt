<?php
	include_once dirname( __FILE__ ) . '/inc/config.inc.php';
	include_once dirname( __FILE__ ) . '/inc/paste.class.php';
	include_once dirname( __FILE__ ) . '/inc/templates.class.php';
	
	// This may be required if a user is dealing with a file that is so large that is takes more than 30 seconds
	set_time_limit( 0 );
	
	$conf = get_config(); // load up our config
	
	$template = new Template();
	$template->assign( 'meta_title', 'EZCrypt' );
	$pastes = new Paste();
	$template->assign( 'pastes', $pastes );
	
	// we are looking for a paste
	if( !empty( $_GET['id'] ) )
	{
		$display_id = $_GET['id'];
		
		$paste = $pastes->get( $display_id );
		
		// detect if any errors came through
		switch( $paste )
		{
			case EZCRYPT_DOES_NOT_EXIST:
			case EZCRYPT_HAS_EXPIRED:
			case EZCRYPT_MISSING_DATA:
				$template->assign( 'meta_title', 'EZCrypt - Paste does not exist' );
				$template->assign( 'paste_id', $display_id );
				die( $template->render( 'nonexistant.tpl' ) );
				break;
		}
		
		// validate paste, check if password has been set
		$validated = $pastes->validate_password( $_POST['p'] );
		
		switch( $validated )
		{
			case EZCRYPT_PASSWORD_SUCCESS:
				// correct, send user the required data
				$output = array(
					'data' => str_replace( array( "\r", "\n" ), '', $paste['data'] ),
					'syntax' => $paste['syntax'],
				);
				die( json_encode( $output ) );
				break;
			case EZCRYPT_PASSWORD_FAILED:
				// incorrect, give the json response an error
				header( 'HTTP/1.1 403 Forbidden' );
				die( 'incorrect password, you shouldn\'t be looking at this anyways!' );
				break;
			case EZCRYPT_PASSWORD_REQUIRED:
				// prompt user for password
				$template->assign( 'paste', '' );
				$template->assign( 'syntax', '' );
				$template->assign( 'password', true );
				break;
			case EZCRYPT_MISSING_DATA:
				$template->assign( 'meta_title', 'EZCrypt - Paste does not exist' );
				$template->assign( 'paste_id', $display_id );
				die( $template->render( 'nonexistant.tpl' ) );
				break;
			case EZCRYPT_NO_PASSWORD:
				// no password, show paste
				$template->assign( 'paste', $paste['data'] );
				$template->assign( 'syntax', $paste['syntax'] );
				break;
		}
		
		$template->assign( 'meta_title', 'EZCrypt - Paste' );
		die( $template->render( 'paste.tpl' ) );
	}
	elseif( !empty( $_POST ) )
	{
		// new post submission
		$paste = $pastes->add( $_POST['data'], $_POST['syn'], $_POST['ttl'], $_POST['p'] );
		
		// return our new ID to the user
		$output = array(
			'id' => alphaID( $paste, false ),
		);
		
		die( json_encode( $output ) );
	}
	
	// new paste
	$template->render( 'new.tpl' );