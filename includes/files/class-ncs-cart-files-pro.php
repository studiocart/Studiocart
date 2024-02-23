<?php

/**
 * The file download specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.1
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/files
 */

class NCS_Cart_Files_Pro extends NCS_Cart_Files {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
        
        $this->initialize();
        $this->initialize_pro();

	}

    public function initialize_pro() {

        add_filter('_sc_option_list', array($this, 'download_expire_hours_setting') );
        add_filter('sc_product_setting_tab_files_fields', [$this, 'file_fields'], 99);
        add_filter('sc_file_download_db_args', [$this, 'maybe_add_temp_expire'], 10, 3);
        add_action('sc_order_complete', array($this, 'update_expire_on_order_complete'), 10, 3 );

        add_action('edit_form_advanced', array($this, 'product_form_callback') );

        add_action('sc_before_show_download', array($this, 'check_download_hash'));

        if(get_option('sc_download_expire_hours')) {
            add_filter('sc_download', array($this, 'get_expiring_link'));
        }
    }

    public function maybe_add_temp_expire($args, $file, $order) {
        if (isset($file['file_expire']) && $order->status == 'pending-payment') {
            $args['download_expires'] = '1111-11-11 11:11:11';
        } else {
            $args = $this->add_expire($args, $file);
        }
        return $args;
    }

    public function add_expire($args, $file) {
        if (isset($file['file_expire'])) {
            $args['download_expires'] = sc_localize_dt($file['file_expire'])->format('Y-m-d H:i:s');
        }
        return $args;
    }

    public function update_expire_on_order_complete($status, $order, $order_type) {

        global $wpdb;

        if (!$files = $this->get_order_downloads($order['id'], array('status'=>'all'))) {
            return;
        }

        if ($order_type == 'bump') {
            return;
        }

        foreach($files as $download) {
            
            if($download->download_expires == '1111-11-11 11:11:11') {

                $file=$this->get_product_file($download->product_id, $download->file_id);

                if(!$file) {
                    return;
                }
                
                $changes = $this->add_expire(array(), $file);

                if(!empty($changes)) {
                    $wpdb->update( $wpdb->prefix.$this->get_table_name(), $changes, array( 'download_id' => $download->download_id ), array( '%s'), array( '%d' ) );
                }
            }
        }
    }

    public function product_form_callback($post) {        
        global $sc_currency_symbol;
        
        if ( 'sc_order' !== $post->post_type || !isset($_GET['post']) ){
            return;
        }

        if(get_post_meta($post->ID, '_sc_status', true) == 'pending-payment') {
            return;
        }
        
        if (!$files = $this->get_order_downloads($post->ID, array('status'=>'all'))) {
            return;
        }
        
        ?>
        <div class="sc-product-info sc-product-table meta-box-sortables ui-sortable">
            <table cellpadding="0" cellspacing="0" class="sc_order_items" style="width: 100%">
                <thead>
                    <tr>
                        <th id="Files" class="item"><?php esc_html_e( 'File Name', 'ncs-cart' ); ?></th>
                        <th id="Expires" class="line_cost"><?php esc_html_e( 'Downloads', 'ncs-cart' ); ?></th>
                        <th id="Expires" class="line_cost"><?php esc_html_e( 'Expires', 'ncs-cart' ); ?></th>
                        <th id="Remaining" class="line_cost"><?php esc_html_e( 'Downloads Remaining', 'ncs-cart' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="order_line_items">
                    <?php foreach($files as $download): ?>
                        <tr>
                            <td><a href="<?php echo $download->path; ?>" target="_blank"><?php echo $download->name; ?></a></td>
                            <td><?php echo count($download->downloads); ?></td>
                            <td><input class="widefat" id="expires[<?php echo $download->download_id; ?>]" name="expires[<?php echo $download->download_id; ?>]" placeholder="" type="datetime-local" autocomplete="new-password" autocorrect="off" autocapitalize="none" value="<?php echo $download->download_expires; ?>" data-lpignore="true"></td>
                            <td><input class="widefat" id="remaining[<?php echo $download->download_id; ?>]" name="remaining[<?php echo $download->download_id; ?>]" placeholder="Unlimited" type="number" step=1 autocomplete="new-password" autocorrect="off" autocapitalize="none" value="<?php echo $download->downloads_remaining; ?>" data-lpignore="true"></td>
                            <td>
                                <a class="button button-primary" href="#" onclick="copyKey('<?php echo $download->url; ?>', this)"><?php _e('Copy URL', 'ncs-cart'); ?></a>
                                <a class="button" href="<?php echo wp_nonce_url(get_edit_post_link()); ?>&sc-revoke=<?php echo $download->download_id; ?>&dl=<?php echo $download->order_key; ?>" onclick="return confirm('<?php _e("Are you sure? This action can\'t be undone.", "ncs-cart"); ?>')"><?php _e('Revoke Access', 'ncs-cart'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <input type="hidden" name="sc_process_downloads" value="1"/>
        <script>
            const copyKey = (str, el) => {
                  if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
                    jQuery(el).html('Copied!');
                    navigator.clipboard.writeText(str);
                    return false;
                  }
                  return Promise.reject('The Clipboard API is not available.');
                };
        </script>
        <?php
    }

    public function download_expire_hours_setting($options) {
        $options['settings']['download_expire'] = array(
                'type'          => 'text',
                'label'         => esc_html__( 'Auto-expire download links', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => 'sc_download_expire_hours',
                    'value' 		=> '',
                    'description' 	=> esc_html__( 'The number of hours a download link will be valid or leave blank to never expire.', 'ncs-cart' ),
                ),
        );

        return $options;
    }

    function file_fields($fields) {
        return array(            
            array(
                'class'         => 'repeater',
                'id'            => '_sc_files',
                'label-add'		=> __('+ Add New','ncs-cart'),
                'label-edit'    => __('Edit File','ncs-cart'),
                'label-header'  => __('File','ncs-cart'),
                'label-remove'  => __('Remove File','ncs-cart'),
                'title-field'	=> 'name',
                'type'		    => 'repeater',
                'value'         => '',
                'class_size'    => '',
                'fields'        => array(
                    array(
                        'text' =>array(
                            'class'         => 'widefat sc-unique',
                            'description'	=> '',
                            'id'			=> 'file_id',
                            'label'		    => __('File ID','ncs-cart'),
                            'placeholder'	=> '',
                            'type'		    => 'text',
                            'value'		    => '',
                            'class_size'    => 'hide',
                        )),  
                    array(
                        'secure-file-upload' =>array(
                            'class'		    => 'select file_url required',
                            'id'			=> 'file_url',
                            'label'		    => __('File URL','ncs-cart'),
                            'placeholder'	=> '',
                            'type'		    => 'secure-file-upload',
                            'field-type'	=> 'url',
                            'value'		    => '',
                            'class_size'    => '',
                            'label-remove'		=> __('Clear','ncs-cart'),
                            'label-upload'		=> __('Upload File','ncs-cart'),
                    )),
                    array(
                    'text' =>array(
                        'class'		    => 'select file_name required repeater-title',
                        'id'			=> 'file_name',
                        'label'		    => __('File Name','ncs-cart'),
                        'placeholder'	=> '',
                        'type'		    => 'text',
                        'value'		    => '',
                        'class_size'    => 'one-half',
                    )),
                    array(
                        'select' =>array(
                            'class'		    => 'sc-selectize multiple',
                            'description'	=> __('Give access only if the order is for a specific payment plan (or purchase type) for this product. Leave blank to give access any time this product is ordered.','ncs-cart'),
                            'id'			=> 'file_plan',
                            'label'		    => __('Restrict by payment plan / purchase type','ncs-cart'),
                            'placeholder'	=> __('Any','ncs-cart'),
                            'type'		    => 'select',
                            'value'		    => '',
                            'class_size'    => 'one-half',
                            'selections'    => NCS_Cart_Product_Metaboxes::get_payment_plans(),
                            'conditional_logic' => array (
                                    array(
                                        'field' => 'services',
                                        'value' => '', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                                        'compare' => '!=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                                    )
                                )
                        )),
                    array(
                        'text' =>array(
                            'class'		=> 'widefat',
                            'description'	=> '',
                            'id'			=> 'file_limit',
                            'label'		=> __('Download limit','ncs-cart'),
                            'placeholder'	=> '&infin;',
                            'type'		=> 'number',
                            'value'		=> '',
                            'class_size'		=> 'one-half',
                            'conditional_logic' => array(
                                array(
                                    'field' => '_sc_manage_stock',
                                    'value' => true,
                                )
                            )
                        )
                    ),
                    array(
                        'text' =>array(
                            'class'		=> 'widefat',
                            'description'	=> __('Enter a length of time in plain english (e.g., 3 days), or leave blank to never expire.','ncs-cart'),
                            'id'			=> 'file_expire',
                            'label'		=> __('Expire download after','ncs-cart'),
                            'placeholder'	=> __('Never','ncs-cart'),
                            'type'		=> 'text',
                            'value'		=> '',
                            'class_size'=> 'one-half'
                        ),
                    ),
                    array(
                        'checkbox' =>array(
                            'class'		=> '',
                            'description'	=> __("Don't force download." ,'ncs-cart'),
                            'id'			=> 'file_redirect',
                            'label'		=> __('Redirect to file' ,'ncs-cart'),
                            'placeholder'	=> '',
                            'type'		=> 'checkbox',
                            'value'		=> '',
                            'class_size'=> '',
                            'conditional_logic' => '',
                        )
                    ),
                    array(
                        'checkbox' =>array(
                            'class'		=> 'file_hide',
                            'description'	=> __("Hide this download in customer accounts and order emails." ,'ncs-cart'),
                            'id'			=> 'file_hide',
                            'label'		=> __('Hidden' ,'ncs-cart'),
                            'placeholder'	=> '',
                            'type'		=> 'checkbox',
                            'value'		=> '',
                            'class_size'=> '',
                            'conditional_logic' => '',
                        )
                    ),
                )
            ),
        );
    }

    function check_download_hash($download) {

        if (intval(get_option('sc_download_expire_hours')) && 
            (time() > $_GET['expires_at'] || !$this->hash_is_valid($download, $_GET['expires_at'], $_GET['s']))
        ) {
            esc_html_e('Sorry, this download link has expired.','ncs-cart');
            exit;
        }
    }

    function get_expiring_link($download) {
        
        if(!$download->url) {
            return $download;
        }

        if(!(intval(get_option('sc_download_expire_hours')))) {
            return $download;
        }

        $params = $this->compute_hash($download);
        $params['s'] = urlencode($params['s']);

        $download->url = add_query_arg($params, $download->url);
        return $download;
    }function hash_is_valid($download, $expire_at, $verify) {
        $params = $this->compute_hash($download, $expire_at);
        return hash_equals($verify,$params['s']);
    }

    function compute_hash($download, $expire_at=false) {
        $hours = intval(get_option('sc_download_expire_hours'));
        if(!$expire_at) {
            $expire_at = time() + ($hours*60*60);
        }
        $salt = $expire_at.$download->file_id.$download->order_id;
        $secret = get_option( '_sc_api_key' );

        return array(
            'expires_at' => $expire_at,
            's' => base64_encode(hash_hmac('sha256', $salt, $secret, true))
        );
    }

}

new NCS_Cart_Files_Pro();