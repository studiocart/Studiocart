<?php

/*
 * Logs debug data to a debug file in the "logs" folder. Example usage below:
 *
 * global $sc_debug_logger;
 * $sc_debug_logger->log_debug("Some debug message");
 *
 * OR
 *
 * NCS_Cart_Debug_Logger::log_debug_st("Some debug message");
 */

class NCS_Cart_Debug_Logger {

	protected static $instance;
	protected $log_folder_path;
	protected $default_log_file = 'log.txt';
	protected $overwrite        = false;
	var $debug_enabled          = false;
	var $debug_status           = array( 'SUCCESS', 'STATUS', 'NOTICE', 'WARNING', 'FAILURE', 'CRITICAL' );
	var $section_break_marker   = "\n----------------------------------------------------------\n\n";
	var $log_reset_marker       = "-------- Log File Reset --------\n";

	function __construct() {
		self::$instance        = $this;
		$this->log_folder_path = NCS_CART_BASE_DIR . 'logs';
		//Check config and if debug is enabled then set the enabled flag to true
		if ( $enabled = get_option( '_sc_enable_debug' ) ) {//Debugging is enabled
			$this->debug_enabled = true;
		}
		$this->init_default_log_file();

        add_action('init', array($this, 'view_log_request'));
        add_filter( '_sc_option_list', array($this, 'enable_debug_log_setting'), 999 );

	}

    public function enable_debug_log_setting($options) {
        $options['settings']['debugging'] = array(
            'type'          => 'checkbox',
            'label'         => esc_html__( 'Enable debug logging', 'ncs-cart' ),
            'settings'      => array(
                'id'            => '_sc_enable_debug',
                'value' 		=> '',
                'note' 	=> sprintf(
                    '<a href="%s" rel="noopener noreferrer" target="_blank">%s</a> &nbsp; <a href="%s" rel="noopener noreferrer">%s</a>', 
                    esc_attr( wp_nonce_url( 'admin.php?page=sc-admin&sc_view_log=1', 'sc_view_debug_log', 'sc_view_debug_log_nonce' ) ),
                    __('view log', 'ncs-cart'), 
                    esc_attr( wp_nonce_url( 'admin.php?page=sc-admin&sc_reset_log=1', 'sc_reset_debug_log', 'sc_reset_debug_log_nonce' ) ),
                    __('delete log', 'ncs-cart')
                ),
            ),
        );

        return $options;
    }

    public function view_log_request() {
        //View debug log
		if ( ! empty( $_REQUEST['sc_view_log'] ) ) {
			check_admin_referer( 'sc_view_debug_log', 'sc_view_debug_log_nonce' );
			$this->view_log();
		}
    }

	public static function get_instance() {
		return empty( self::$instance ) ? new self() : self::$instance;
	}

	function init_default_log_file() {
		if ( !$file = get_option( '_sc_log_file' ) ) {
			$this->default_log_file      = uniqid() . '-log.txt';
			$file = $this->default_log_file;
			update_option( '_sc_log_file', $file );
			$this->reset_log_file();
		} else {
			$this->default_log_file = $file;
		}
	}

	function get_debug_timestamp() {
		return '[' . date( 'm/d/Y g:i A' ) . '] - ';
	}

	function get_debug_status( $level ) {
		$size = count( $this->debug_status );
		if ( $level >= $size ) {
			return 'UNKNOWN';
		} else {
			return $this->debug_status[ $level ];
		}
	}

	function view_log( $file_name = '' ) {
		if ( empty( $file_name ) ) {
			$file_name = $this->default_log_file;
		}
		$log_file = $this->log_folder_path . '/' . $file_name;
		if ( ! file_exists( $log_file ) ) {
			echo 'Log file is empty';
			exit();
		}
		$logfile = fopen( $log_file, 'rb' );
		if ( ! $logfile ) {
			wp_die( 'Can\'t open log file.' );
		}
		header( 'Content-Type: text/plain' );
		fpassthru( $logfile );
		exit();
	}

	function get_section_break( $section_break ) {
		if ( $section_break ) {
			return $this->section_break_marker;
		}
		return '';
	}

	function reset_log_file( $file_name = '' ) {
		if ( empty( $file_name ) ) {
			$file_name = $this->default_log_file;
		}
		$content = $this->get_debug_timestamp() . $this->log_reset_marker;

		$this->overwrite = true;
		$this->append_to_file( $content, $file_name );
	}

	function append_to_file( $content, $file_name ) {
		if ( empty( $file_name ) ) {
			$file_name = $this->default_log_file;
		}
		$debug_log_file = $this->log_folder_path . '/' . $file_name;
		$f_opts         = $this->overwrite ? 'w' : 'a';
		$fp             = fopen( $debug_log_file, $f_opts );
		fwrite( $fp, $content );
		fclose( $fp );
	}

	function log_debug( $message, $level = 1, $section_break = false, $file_name = '' ) {
		if ( ! $this->debug_enabled ) {
			return;
		}
		$content  = $this->get_debug_timestamp();//Timestamp
		$content .= $this->get_debug_status( $level );//Debug status
		$content .= ' : ';
		$content .= $message . "\n";
		$content .= $this->get_section_break( $section_break );
		$this->append_to_file( $content, $file_name );
	}

	static function log_debug_st( $message, $level = 1, $section_break = false, $file_name = '' ) {
		if ( $enabled = get_option( '_sc_enable_debug' ) ) {//Debugging is disabled
			return;
		}
		self::get_instance();
		$content        = '[' . date( 'm/d/Y g:i A' ) . '] - STATUS : ' . $message . "\n";
		$debug_log_file = self::get_instance()->log_folder_path . '/' . self::get_instance()->default_log_file;
		$fp             = fopen( $debug_log_file, 'a' );
		fwrite( $fp, $content );
		fclose( $fp );
	}

}
