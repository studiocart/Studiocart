<?php

/**
 * Class for general reusable functions.
 *
 * @link       https://ncstudio.co
 * @since      2.3
 *
 * @package    NCS_Helper
 * @author     N.Creative Studio <info@ncstudio.co>
 */

class NCS_Helper extends NCS_Order_Helper{

    /**
	 * The single instance of the class.
	 *
	 * @var NCS_Helper
	 * @since 2.3.0
	 */

	protected static $_instance = null;

    /**
     * NCS_Helper Instance
     */

    public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    /**
     * Prepare Exception data to be logged
     */

    public function logException($exception,$line,$file){
       
        $log['type'] = get_class($exception) ;
        $log['line'] = $line;
        $log['file'] = stripslashes( dirname($file).'/'.basename($file). '.php');
        $log['message'] = $exception->getError()->message;
       
        $this->NCSLogger($log);
        if ( wp_doing_ajax() ) {
            $this->sendErrorResponse();
        }
    }


   /**
    * Save Exception Logs
    */

    public function NCSLogger( $message, $file = 'debug' ) { 

        // If the message is array, json_encode.
        if ( is_array( $message ) ) { 
            $message = json_encode( $message ); 
        } 
    
        // Write the log file.
        $file  = NCS_CART_BASE_DIR . $file . '.log';
        $file  = fopen( $file, 'a' );
        $bytes = fwrite( $file, current_time( 'mysql' ) . "::" . $message . "\n" ); 
        fclose( $file ); 
    
        return $bytes;
    }

    /**
     * Send response when there is any exception
     */

    public function sendErrorResponse($message = '', $data = [] ){
		
		if(empty($message)){
			$message = 'Please try again later or contact support.';
		}

		wp_send_json(array('error'=> $message,'data'=> $data));
    }

    /**
     * Include template part
     */

    public function renderTemplate($part,$args = []){
        $template = get_stylesheet_directory() . '/studiocart/'.$part.'.php';
        if(!file_exists($template)){
            $template = NCS_CART_BASE_DIR . 'public/templates/'.$part.'.php';
        }
  
        include( $template );
    } 
    

    /**
     * Function to print data for debugging 
     */
    
    public function dump($data){
		echo '<pre/>';
		print_r($data);
		exit;
	}

}
?>