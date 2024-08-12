<?php

function woo_jb_get_jubelio_token() {

    $token = get_transient( 'jb_tk' );
    
    if ( empty( $token ) ) :

        $email = get_option('_jb_u');
        $password = get_option('_jb_p');

        if ( $email && $password ) :

            $url 	= 'https://api2.jubelio.com/login';
            $data 	= [
                'email' 	=> $email,
                'password' 	=> $password,
            ];

            $response = woo_jb_request_post( $url, $data );
            // do_action( 'inspect', [ 'token_response', $response ] );
            $result   = json_decode( $response, true );

            $token = '';
            if ( isset( $result['token'] ) ) :
                $token = $result['token'];
                $expiration = MINUTE_IN_SECONDS * 30;
                set_transient( 'jb_tk', $token, $expiration );
            endif;

        else:

            // do_action( 'inspect', [ 'token_response', 'email_password_empty' ] );

        endif;
        
    endif;

    return $token;

}