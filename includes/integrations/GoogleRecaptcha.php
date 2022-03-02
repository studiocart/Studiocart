<?php

namespace Studiocart;

if (!defined('ABSPATH'))
	exit;

class GoogleRecaptcha {

    /**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
    
	private $service_name;
	private $service_label;
	private $version_type;
	private $site_secret;
    private $score;
	private $iscaptchakey;
	private $grecaptcha;
	private $grecaptcha_type;

	public function __construct() {
        $this->service_name = 'googlerecaptcha'; 
        $this->service_label = 'Google reCAPTCHA';
        
        
        $this->iscaptchakey = false;
        $this->grecaptcha_type = get_option("_sc_googlerecaptchav2_captcha_type");
        $grecaptchav2 = get_option("_sc_googlerecaptchav2_site_key");
        $grecaptchav3 = get_option("_sc_googlerecaptchav3_site_key");
        
        if (!empty($grecaptchav3)) {
            $this->iscaptchakey = true;
            $this->grecaptcha = $grecaptchav3;
            $this->version_type = 'v3';
            $this->site_secret = get_option('_sc_googlerecaptchav3_site_secret');
            
            if ($score = get_option('_sc_'.$this->service_name.'_v3rating')) {
                $this->score = $score;
            } else {
                $this->score = '0.5';
            }
            
        } else if(!empty($grecaptchav2)) {
            $this->iscaptchakey = true;
            $this->grecaptcha = $grecaptchav2;
            $this->version_type = 'v2';			
            $this->site_secret = get_option('_sc_googlerecaptchav2_site_secret');
        }
                
        add_filter('_sc_integrations_tab_section', array($this, 'settings_section'), 10, 1);
        add_filter('_sc_integrations_option_list', array($this, 'service_settings'));
        
        if ($this->iscaptchakey !== false) {
            add_action('plugins_loaded', array($this, 'init'));
        }
    }  

    public function init() {        
		add_action('sc_before_buy_button', array($this, 'gen_recaptcha_html'), 10, 1);
        add_action('sc_before_create_main_order', array($this, 'google_recaptcha_validation'));
    }
    
    public function google_recaptcha_validation() {
        $site_secret = $this->site_secret;
        $recaptcha = sanitize_text_field($_POST['g-recaptcha-response']);
        $res = $this->reCaptcha($recaptcha, $site_secret);
        if(!empty($res['error-codes'][0])){
            $errorcodes = $res['error-codes'];
            
            switch ($errorcodes[0]) {
                case 'missing-input-secret':
                    $error = esc_html__('Captcha error: The secret parameter is missing', 'ncs-cart' );
                    break;
                case 'invalid-input-secret':
                    $error = esc_html__('Captcha error: The secret parameter is invalid', 'ncs-cart' );
                    break;
                case 'missing-input-response':
                    $error = esc_html__('Captcha error: The response parameter is missing', 'ncs-cart' );
                    break;
                case 'invalid-input-response':
                    $error = esc_html__('Captcha error: The response parameter is invalid', 'ncs-cart' );
                    break;
                case 'bad-request':
                    $error = esc_html__('Captcha error: The request is invalid', 'ncs-cart' );
                    break;
                case 'timeout-or-duplicate':
                    $error = esc_html__('Captcha error: The response is a duplicate or the service has timed out', 'ncs-cart' );
                    break;
                default:
                    $error = esc_html__('Something went wrong, please try again', 'ncs-cart' );
                    break;
            }
            echo json_encode(array('error' => $error));
            exit; 
        }
        
        if ($this->version_type == 'v3' && $res["score"] < $this->score) {
            echo json_encode(array('error' => esc_html__('Something went wrong, please try again!', 'ncs-cart' )));
            exit; 
        }
    }

    public function reCaptcha($recaptcha, $gsecretk){
        $ip = $_SERVER['REMOTE_ADDR'];
        $postvars = array("secret"=> $gsecretk, "response" => $recaptcha, "remoteip" => $ip);
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
        $data = curl_exec($ch);
        curl_close($ch);
        return json_decode($data, true);
    }

    public function settings_section($intigrations) {
        
        $intigrations[$this->service_name] = $this->service_label;
        $intigrations[$this->service_name.'-v2'] = $this->service_label.' v2 Settings';
        $intigrations[$this->service_name.'-v3'] = $this->service_label.' v3 Settings';
        // add_settings_section(
		// 	$sc_name . '-' .$this->service_name,
		// 	apply_filters( $sc_name . 'section-title-'.$this->service_name, esc_html__( $this->service_label, 'ncs-cart' ) ),
		// 	array( $sc, 'section_integrations_settings' ),
		// 	$sc_name
		// );
        
        // add_settings_section(
		// 	$sc_name . '-' .$this->service_name.'-v2',
		// 	apply_filters( $sc_name . 'section-title-'.$this->service_name.'-v2', esc_html__( 'v2 Settings', 'ncs-cart' ) ),
		// 	array( $sc, 'section_integrations_settings' ),
		// 	$sc_name
		// );
        
        // add_settings_section(
		// 	$sc_name . '-' .$this->service_name.'-v3',
		// 	apply_filters( $sc_name . 'section-title-'.$this->service_name.'-v3', sprintf(esc_html__( '%s v3 Settings', 'ncs-cart' ), $this->service_label )),
		// 	array( $sc, 'section_integrations_settings' ),
		// 	$sc_name
		// );
        return $intigrations;
    }     

    public function service_settings($options) {
        $options[$this->service_name.'-v2'] = array(	
			$this->service_name.'-v2captcha_type' => array(
				'class' 		=> 'wide-fat',
				'type'          => 'select',
				'label'         => esc_html__( 'Captcha Type', 'ncs-cart' ),
				'settings'      => array(
					'id'            => '_sc_'.$this->service_name.'v2_captcha_type', 
					'value'         => '',
					'selections'    => [
						'norobo' => esc_html__('Checkbox', 'ncs-cart' ),
						'invisible' => esc_html__('Invisible', 'ncs-cart' )
					],					
					'description'   => '',
				),
                'tab'=>'integrations'
			),
            
			$this->service_name.'-v2site_key' => array(
				'type'          => 'text',
				'label'         => esc_html__( 'Site Key', 'ncs-cart' ),
				'settings'      => array(
					'id'            => '_sc_'.$this->service_name.'v2_site_key',
					'value'         => '',
					'description'   => '',
				),
                'tab'=>'integrations'
			),		

			$this->service_name.'-v2site_secret' => array(
				'type'          => 'text',
				'label'         => esc_html__( 'Site Secret', 'ncs-cart' ),
				'settings'      => array(
					'id'            => '_sc_'.$this->service_name.'v2_site_secret', 
					'value'         => '',
					'description'   => '',
				),
                'tab'=>'integrations'
			),
		);
        
        $options[$this->service_name.'-v3'] = array(
			$this->service_name.'-v3site_key' => array(
				'type'          => 'text',
				'label'         => esc_html__( 'Site Key', 'ncs-cart' ),
				'settings'      => array(
					'id'            => '_sc_'.$this->service_name.'v3_site_key',
					'value'         => '',
					'description'   => '',
				),
                'tab'=>'integrations'
			),		
			
			$this->service_name.'-v3site_secret' => array(
				'type'          => 'text',
				'label'         => esc_html__( 'Site Secret', 'ncs-cart' ),
				'settings'      => array(
					'id'            => '_sc_'.$this->service_name.'v3_site_secret',
					'value'         => '',
					'description'   => '',
				),
                'tab'=>'integrations'
			),
						
			$this->service_name.'-v3ratings' => array(
				'class' 		=> 'wide-fat',
				'type'          => 'select',
				'label'         => esc_html__( 'Minimum Score', 'ncs-cart' ),
				'settings'      => array(
					'id'            => '_sc_'.$this->service_name.'_v3rating', 
					'value'         => '0.5',
					'selections'	=> [
						'0.0' => esc_html__('0.0 (Not Recommended)', 'ncs-cart' ),
						'0.1' => esc_html__('0.1 (Bots)', 'ncs-cart' ),
						'0.2' => esc_html__('0.2 (Spam)', 'ncs-cart' ),
						'0.3' => esc_html__('0.3 (Likely human)', 'ncs-cart' ),
						'0.4' => esc_html__('0.4 (Most probably human)', 'ncs-cart' ),
						'0.5' => esc_html__('0.5 (Recommended)', 'ncs-cart' ),
						'0.6' => esc_html__('0.6 (OK)', 'ncs-cart' ),						
						'0.7' => esc_html__('0.7 (Good)', 'ncs-cart' ),						
						'0.8' => esc_html__('0.8 (Better)', 'ncs-cart' ),						
						'0.9' => esc_html__('0.9 (Best)', 'ncs-cart' ),						
						'1.0' => esc_html__('1.0 (Most Recommended)', 'ncs-cart' ),						
					],
					'description'   => '',
				), 
                'tab'=>'integrations'
			),
		);
        return $options;
    }
    
    public function add_service($options) {
        $options[$this->service_name] = $this->service_label;
        return $options;
    }

	public function add_front_scripts(){
        if($this->version_type == "v3"){
            $src = "https://www.google.com/recaptcha/api.js?render=". esc_html( $this->grecaptcha );
        } else if($this->version_type == "v2"){ 
            $src = "https://www.google.com/recaptcha/api.js";
        }
        echo '<script async src="'.$src.'"></script>';
	}
    
	public function gen_recaptcha_html($scp){
        ?>
        <div class="row">   
            <div class="form-group col-sm-12">
                <?php     
                if ($this->version_type == 'v2') { 
                    if($this->grecaptcha_type == 'norobo'){ ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_html($this->grecaptcha); ?>"></div>
                        <?php
                    } else { ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_html($this->grecaptcha); ?>" data-size="invisible"></div>
                        <?php 
                    }
                } else { ?>
                    <input class="sc-grtoken" type="hidden" name="g-recaptcha-response" data-sitekey="<?php echo esc_html($this->grecaptcha); ?>"/>
                    <?php 
                }
                ?> 
            </div>  
        </div>  
        <?php 
        add_action('wp_footer', array($this, 'add_front_scripts'));
	}
}