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

class NCS_Cart_Files {

    /**
	 * The downloads table name.
	 *
	 * @since    2.6
	 * @access   private
	 * @var      string    $table_name    The downloads table name.
	 */
	private static $table_name = 'ncs_downloads';

    /**
	 * The downloads page slug.
	 *
	 * @since    2.6
	 * @access   private
	 * @var      string    $slug    The downloads page slug.
	 */
    private $slug = 'file';
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
        
        $this->initialize();

	}

    public function init() {

    }

    public function table_name() {
        return self::$table_name;
    }

    public function initialize() {
        add_action( 'studiocart_activate', array($this, 'setup_download_table') );
        add_action( 'studiocart_activate', array($this, 'setup_directory') );
        add_action( 'studiocart_upgrade', array($this, 'setup_download_table') );
        add_action( 'studiocart_upgrade', array($this, 'setup_directory') );
        add_action( 'studiocart_order_created', array($this, 'attach_downloads_to_order'), 5 );
        add_action( 'sc_order_refunded', array($this, 'process_refund'), 5, 3 );
        add_filter( 'sc_account_tabs', array($this, 'account_tabs'), 1 );
        add_action( 'sc_tab_content_tab-files', array($this, 'file_tab_content'), 1 );
        add_action( 'wp', array($this, 'download_file'), 1 );
        add_action( 'edit_form_advanced', array($this, 'product_form_callback') );
        add_action( 'save_post_sc_order', array($this, 'update_order_downloads'), 99, 2 );
        add_action( 'admin_init', array($this, 'maybe_revoke_access') );
        add_action( 'admin_notices', array($this, 'revoke_notice') );
        add_action( 'init', array($this, 'download_page_rewrites'), 1, 0 );
        add_action( 'sc_email_after_order_table', array($this, 'email_download_links') );
        add_action( 'sc_receipt_after_order_details', array($this, 'receipt_download_links') );
        add_filter( '_sc_option_list', array($this, 'login_to_download_setting') );
        add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
        add_filter( '_sc_option_list', array($this, 'download_slug_setting') );
        add_action( 'add_option_sc_download_slug', array($this, 'flush_permalinks'), 10, 2 );
        add_action( 'update_option_sc_download_slug', array($this, 'flush_permalinks'), 10, 2 );

        add_shortcode('studiocart-order-downloads', array($this, 'downloads_shortcode'));

        add_filter('sc_product_setting_tabs', [$this, 'files_tab']);
        add_filter('sc_product_field_groups', array($this, 'file_group'));
        add_filter("sc_product_setting_tab_files_fields", [$this, 'file_fields']);

    }

    function flush_permalinks( $old_value, $new_value ) {
        flush_rewrite_rules();
    }

    public function download_slug_setting($options) {
        $options['settings']['download_slug'] = array(
                'type'          => 'text',
                'label'         => esc_html__( 'Download URL slug', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => 'sc_download_slug',
                    'value' 		=> 'file',
                    'description' 	=> esc_html__( 'URL base for secure download links', 'ncs-cart' ),
                ),
        );

        return $options;
    }
    
    public function upload_dir( $pathdata ) {
		if (isset( $_POST['type'] ) && $_POST['type'] == 'sc_upload') { 
			if ( empty( $pathdata['subdir'] ) ) {
				$pathdata['path']   = $pathdata['path'] . '/sc-uploads';
				$pathdata['url']    = $pathdata['url'] . '/sc-uploads';
				$pathdata['subdir'] = '/sc-uploads';
			} else {
				$new_subdir = '/sc-uploads' . $pathdata['subdir'];
				$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
				$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
				$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
			}
		}
		return $pathdata;
	}

    public function login_to_download_setting($options) {
        $options['settings']['login_download'] = array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Logged in user downloads only', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => 'sc_login_to_download',
                    'value' 		=> '',
                    'description' 	=> esc_html__( 'Require users to log in before downloading files', 'ncs-cart' ),
                ),
        );
        return $options;
    }

    function files_tab($tabs) {
        $tabs['files'] = __('Files','ncs-cart');
        return $tabs;
    }

    public function file_group($groups) {
        $groups[] = 'files';
        return $groups;
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
                        'file-upload' =>array(
                            'class'		    => 'select file_url required',
                            'id'			=> 'file_url',
                            'label'		    => __('File URL','ncs-cart'),
                            'placeholder'	=> '',
                            'type'		    => 'file-upload',
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
                )
            ),
        );
    }

    public function download_page_rewrites() {  
        $this->slug = apply_filters('sc_download_slug', $this->slug);
        add_rewrite_tag('%sc-download%', '(s+)');
        add_rewrite_rule("{$this->slug}/([a-z0-9-]+)[/]?$", 'index.php?&sc-download=$matches[1]', 'top' );
    }

    public function revoke_notice() {
        if (isset($_GET['sc-revoked'])) {
            $msg = sanitize_text_field($_GET['sc-revoked']);
            $class = 'error';

            switch($msg) {
                case 'error':
                    $msg = __('Unable to revoke download access, please try again.', 'ncs-cart');
                    break;
                case 'download-not-found':
                    $msg = __('Unable to find the download associated with this order, maybe access was already revoked?', 'ncs-cart');
                    break;
                default:
                    $msg = sprintf(__('Access to file "%s" has been revoked.', 'ncs-cart'), $msg);
                    $class = 'success';
                    break;
            }
            ?>
            <div class="notice notice-<?php echo $class; ?> is-dismissible">
                <p><?php echo $msg; ?></p>
            </div>
            <?php
        }
    }

    public function maybe_revoke_access() {
        $current_user = wp_get_current_user();
        if (isset($_GET['sc-revoke'], $_GET['post'], $_GET['dl']) && $_GET['sc-revoke'] && wp_verify_nonce($_GET['_wpnonce'])) {
            $key = sanitize_text_field($_GET['dl']);
            $redirect = get_edit_post_link(intval($_GET['post']), 'edit');
            if($download = $this->get_download_by_key($key, array('status'=>'all'))) {
                if($this->revoke_access(intval($_GET['sc-revoke']))) {
                    sc_log_entry(intval($_GET['post']), sprintf(__('Access to file "%s" revoked by %s', 'ncs-cart'), $download->name, $current_user->user_login));
                    $redirect .= '&sc-revoked='.urlencode($download->name);
                } else {
                    $redirect .= '&sc-revoked=error';
                }
            } else {
                $redirect .= '&sc-revoked=download-not-found';
            }

            sc_redirect($redirect);
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
                        <th id="Remaining" class="line_cost"><?php esc_html_e( 'Downloads Remaining', 'ncs-cart' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="order_line_items">
                    <?php foreach($files as $download): ?>
                        <tr>
                            <td><a href="<?php echo $download->path; ?>" target="_blank"><?php echo $download->name; ?></a></td>
                            <td><?php echo count($download->downloads); ?></td>
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
    
    public function update_order_downloads($post_id, $post){
		global $wpdb;
        
        // leave if not on the post edit screen
		if ($post->post_type != 'sc_order' || !is_admin() || !isset($_POST['original_publish']) || !isset($_POST['sc_process_downloads'])){
			return;
		}
        
        if ( wp_is_post_revision( $post_id ) || $post->post_status == 'auto-draft' ){
            return;
        }
        
        remove_action('save_post_sc_order',[$this,'update_order_downloads'],1);

        if (!$files = $this->get_order_downloads($post_id, array('status'=>'all'))) {
            return;
        }

        foreach($files as $download) {
            $changes = array();

            $_expires = (isset($_POST['expires'][$download->download_id]) && $_POST['expires'][$download->download_id]) ? date_i18n('Y-m-d H:i:s', strtotime($_POST['expires'][$download->download_id])) : '';
            $_remaining = (isset($_POST['remaining'][$download->download_id]) && $_POST['remaining'][$download->download_id] !== '') ? intval($_POST['remaining'][$download->download_id]) : 'unlimited';
            
            if(!$_expires && $download->download_expires) {
                $changes['download_expires'] = NULL;
            } else if($_expires && $_expires != $download->download_expires) {
                $changes['download_expires'] = $_expires;
            }

            if($_remaining != $download->downloads_remaining) {
                $changes['downloads_remaining'] = $_remaining;
            }

            if(!empty($changes)) {
                $wpdb->update( $wpdb->prefix.self::$table_name, $changes, array( 'download_id' => $download->download_id ), array( '%s', '%s' ), array( '%d' ) );
            }
        }
        
        add_action('save_post_sc_order',[$this,'update_order_downloads'],1);

    }

    public function download_file() {
        if ( get_query_var('sc-download') && file_exists( plugin_dir_path( __FILE__ ) . 'download.php' ) ) {
            require_once( plugin_dir_path( __FILE__ ). '/download.php' );
			exit();
        } 
    }

    public function get_product_files($id) {
        return get_post_meta($id, '_sc_files', true);
    }

    public function get_product_file($prod_id, $file_id) {
        if($files = $this->get_product_files($prod_id)) {
            foreach($files as $file) {
                if($file['file_id'] == $file_id) {
                    return $file;
                }
            }
        }
        return false;
    }

    public function account_tabs($tabs){
        
        $user_id = get_current_user_id();
          
        if (!$user_id) {
            return $tabs;
        }

        if ($orders = sc_get_user_orders($user_id)) {
            foreach($orders as $order) {
                if ($this->get_order_downloads($order['ID'])) {
                    $tab = array(
                        'id' => 'tab-files',
                        'title' => apply_filters('sc_download_tab_name', __('Downloads', 'ncs-cart')),
                    );
                    array_splice( $tabs, 3, 0, [$tab] ); 
                    return $tabs;
                }
            }
        }

        return $tabs;
    }

    public function get_order_downloads($order_id=0, $args=array()) {

        global $wpdb;

        if(!$order_id) {
            return false;
        }

        $defaults = array(
            'status' => 'active',
        );
        $args = wp_parse_args( $args, $defaults );

        $status = get_post_meta($order_id, '_sc_status', true);
        if($args['status']!='all' && !in_array($status, ['paid', 'complete'])) {
            return false;
        }

        $now = sc_localize_dt()->format('Y-m-d H:i:s');
        
        $table_name = self::$table_name;

        $query = "SELECT * FROM {$wpdb->prefix}{$table_name} WHERE order_id = %s";

        if($args['status'] == 'active'){
            $query .= " AND (download_expires IS NULL OR download_expires > STR_TO_DATE('{$now}', '%%Y-%%m-%%d %%H:%%i:%%s'))"; 
            $query .= " AND (downloads_remaining = 'unlimited' OR downloads IS NULL OR downloads_remaining > 0)"; 
        }

        if(isset($args['product_id'] )) {
            $query .= " AND product_id = " . intval($args['product_id']);  
        }

        if ($order_downloads = $wpdb->get_results($wpdb->prepare($query,$order_id))) {
            $downloads = array();
            foreach ($order_downloads as $download) {
                $show_hidden = ($args['status']=='all') ? true : false;
                if ($download = $this->setup_download($download, $show_hidden)) {
                    $downloads[] = $download;
                }
            }
            return $downloads;
        }

        return false;
    }

    public static function log_download($file) {

        global $wpdb;

        if(!$file->download_id) {
            return false;
        }

        $file->downloads[time()] = $_SERVER['REMOTE_ADDR'];

        $args = array(
            'downloads' => maybe_serialize($file->downloads),
            'downloads_remaining' => $file->downloads_remaining
        );

        $args['downloads_remaining'] = (is_numeric($args['downloads_remaining'])) ? $args['downloads_remaining'] - 1 : 'unlimited'; 
        
        $updated = $wpdb->update( $wpdb->prefix.self::$table_name, $args, array( 'download_id' => $file->download_id ), array( '%s', '%s' ), array( '%d' ) );
        if ( false === $updated ) {
            return false;
        } 
        
        return true;
    }

    function get_download_by_key($key=0, $args=array()) {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
        );
        $args = wp_parse_args( $args, $defaults );

        $table_name = self::$table_name;

        $now = sc_localize_dt()->format('Y-m-d H:i:s');

        $query = "SELECT * FROM {$wpdb->prefix}{$table_name} WHERE order_key = %s";
        if($args['status'] == 'active') {
            $query .= " AND (download_expires IS NULL OR download_expires > STR_TO_DATE('{$now}', '%%Y-%%m-%%d %%H:%%i:%%s'))"; 
            $query .= " AND (downloads_remaining = 'unlimited' OR downloads IS NULL OR downloads_remaining > 0)"; 
        }
        $query .= " LIMIT 1";

        if($res = $wpdb->get_results($wpdb->prepare($query,$key))) {
            return $this->setup_download($res[0]);
        }
        
        return false;
    }

    function revoke_access($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix.self::$table_name,array( 'download_id' => $id ), array( '%d' ));
    }

    function setup_download($download, $show_hidden=false) {
        $files = $this->get_product_files($download->product_id);

        $download->name = esc_html__('Sorry, this file is no longer available.','ncs-cart');
        $download->url = false;
        $download->path = false;
        $download->product_name = false;

        if (isset($download->downloads) && $download->downloads) {
            $download->downloads = maybe_unserialize($download->downloads);
        } else {
            $download->downloads = [];
        }

        $download->downloads_remaining = ($download->downloads_remaining == 'unlimited') ? '&infin;' : $download->downloads_remaining;
        $download->download_expires = $download->download_expires ?? false;
        $download->expires = (!isset($download->download_expires) || !$download->download_expires) ? '--' : date_i18n(get_option( 'date_format' ), strtotime($download->download_expires));
        
        if (is_countable($files)) {
            foreach($files as $file) {
                if($file['file_id'] == $download->file_id) {
                    if(isset($file['file_hide']) && !$show_hidden) {
                        break;
                    }
                    $download->file_redirect = $file['file_redirect'] ?? false;
                    $download->name = $file['file_name'];
                    $download->path = $file['file_url'];
                    $download->url = site_url("/{$this->slug}/{$download->order_key}");
                    $download->product_name = sc_get_public_product_name($download->product_id);
                    break;
                }
            }
        }

        $download = apply_filters('sc_download', $download);

        if(!$download->url) {
            return false;
        }

        return $download;
    }

    function email_download_links($order) {
        $id = $order->id ?? $order->ID;
        if ($files = $this->get_order_downloads($id)): ?>
            <table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                <tbody>
                    <tr>
                    <td style="overflow-wrap:break-word;word-break:break-word;padding:25px 0px 20px;font-family:'Lato',sans-serif;" align="left">
                        
                        <div style="color: #303030; line-height: 120%; text-align: left; word-wrap: break-word;">
                            <p style="font-size: 14px; line-height: 120%;"><span style="font-size: 14px; line-height: 16.8px;">
                            <span style="line-height: 16.8px; font-size: 14px;"><strong><?php esc_attr_e('Downloads','ncs-cart'); ?></strong><br />
                                
                                <?php foreach($files as $download) : ?>
                                    <a href="<?php echo $download->url; ?>" target="_blank"><?php echo $download->name; ?></a><br>
                                <?php endforeach; ?>
                                </span></span>
                            </p>
                        </div>

                    </td>
                    </tr>
                </tbody>
            </table>
        <?php endif;
    }

    function downloads_shortcode($attr){

        $default_id = isset($_GET['sc-order'] ) ? intval($_GET['sc-order'] ) : false;

        // Parse shortcode attributes
        $defaults = array( 'id' => $default_id, 'full' => true, 'show-title' => true);
        $attr = shortcode_atts( $defaults, $attr );

        if(!$attr['id']) {
            return;
        }

        if($attr['full']) {
            $items = sc_get_item_list(intval($attr['id']));
            $ids = array();
            foreach($items['items'] as $item) {
                if(isset($item['order_id']) && !in_array($item['order_id'], $ids)) {
                    $ids[] = $item['order_id'];
                }
            }
        } else {
            $ids = (array) $attr['id'];
        }

        $return = '';

        foreach ($ids as $id) {
            if ($files = $this->get_order_downloads($id)) {
                foreach($files as $download) {
                    $return .= '<li><a href="'. $download->url .'" target="_blank">'. $download->name .'</a></li>';
                }
            }
        }

        if($return) {
            if($attr['show-title']) {
                return sprintf('<div class="sc-download-list"><h3>%s</h3><ul>%s<ul></div>', esc_html__('Downloads', 'ncs-cart'), $return);
            } else {
                return sprintf('<div class="sc-download-list"><ul>%s<ul></div>', $return);
            }
        }
    }

    function receipt_download_links($items) {
        $ids = array();
        $all_files = array();

        foreach($items['items'] as $item) {
            if(isset($item['order_id']) && !in_array($item['order_id'], $ids)) {
                if ($files = $this->get_order_downloads($item['order_id'])) {
                    $ids[] = $item['order_id'];
                    $all_files = array_merge($all_files, $files);
                }     
            }
        }

        if($all_files) : ?>
            <strong><?php esc_attr_e('Your Downloads','ncs-cart'); ?></strong>
            <div class="sc-order-table">
                <?php foreach($all_files as $download) : ?>
                    <div class="item">
                        <a href="<?php echo $download->url; ?>" target="_blank"><?php echo $download->name; ?></a><br>
                    </div>
                    <div class="item"></div>
                <?php endforeach; ?>
            </div>
        <?php endif;
    }

    function file_tab_content() {
        $user_id = get_current_user_id();
          
        if(!$user_id) {
            return false;
        }

        $downloads = array();

        $orders = sc_get_user_orders($user_id);
        foreach($orders as $order) {
            if ($order_downloads = $this->get_order_downloads($order['ID'])) {
                $downloads = array_merge($downloads, $order_downloads);
            }
        }
            
        if($downloads) : ?>
            <table class="ncs-account-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'ncs-cart'); ?></th>
                        <th><?php _e('Expires', 'ncs-cart'); ?></th>
                        <th><?php _e('Downloads Remaining', 'ncs-cart'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($downloads as $download): ?>
                    <tr>
                        <td>
                            <a href="<?php echo $download->url; ?>" target="_blank"><?php echo $download->name; ?></a>
                        </td>
                        <td><?php echo $download->expires; ?></td>
                        <td><?php echo $download->downloads_remaining; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif;
    }

    public function process_refund($status, $order_data, $order_type='main') {
        global $wpdb;
        $args['downloads_remaining'] = '0';
        $args['download_expires'] = sc_localize_dt()->format('Y-m-d H:i:s');
        $downloads = $this->get_order_downloads($order_data['id'], array('status'=>'all'));
        if (is_countable($downloads)) {
            foreach($downloads as $download) {
                if($download->product_id == $order_data['product_id']) {
                    $wpdb->update( $wpdb->prefix.self::$table_name, $args, array( 'download_id' => $download->download_id ), array( '%s', '%s' ), array( '%d' ) );
                }
            }
        }
    }

    public function attach_downloads_to_order($order) {
        
        global $wpdb;

        $items = $order->get_items();

        foreach($items as $item) {

            $product_id = $item->product_id;
            $order_ids = [$item->price_id,$item->item_type,$order->order_type];
            $files = $this->get_product_files($product_id);
            
            if (is_countable($files)) {
                foreach($files as $file) {

                    //payment plan
                    $file_plan_ids = (isset($file['file_plan'])) ? (array) $file['file_plan'] : array();                    

                    if ( sc_match_plan($file_plan_ids, $order_ids) ) { 
                        $args = array(
                            'file_id' => $file['file_id'],
                            'order_id' => $order->id,
                            'order_key' => uniqid(),
                            'product_id' => $product_id,
                            'download_expires' => NULL,
                            'downloads_remaining' => $file['file_limit'] ?? 'unlimited',
                        );

                        $args = apply_filters('sc_file_download_db_args', $args, $file, $order);

                        $wpdb->insert( $wpdb->prefix . self::$table_name, $args );
                        $file_id = $wpdb->insert_id;                
                        do_action( 'sc_after_downloads_attached_to_order', $file_id, $args );
                    }
                }
            }
        }
    }

    public function setup_directory() {

		// Install files and folders for uploading files and prevent hotlinking
		$upload_dir = wp_upload_dir();

		$htaccess_content = '# Apache 2.4 and up
		<IfModule mod_authz_core.c>
		Require all denied
		</IfModule>

		# Apache 2.3 and down
		<IfModule !mod_authz_core.c>
		Order Allow,Deny
		Deny from all
		</IfModule>';

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/sc-uploads',
				'file'    => '.htaccess',
				'content' => $htaccess_content,
			),
			array(
				'base'    => $upload_dir['basedir'] . '/sc-uploads',
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

    public function get_table_name() {
        return self::$table_name; 
    }

    public function setup_download_table(){
		global $wpdb;

   		$ncs_tax = $wpdb->prefix . self::$table_name; 
		$charset_collate = $wpdb->get_charset_collate();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS $ncs_tax (
			download_id bigint(20) NOT NULL AUTO_INCREMENT,
			file_id varchar(20) NOT NULL,
			order_id bigint(20) NOT NULL,
			order_key varchar(20) UNIQUE NOT NULL,
			product_id bigint(20) NOT NULL,
			access_granted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			download_expires datetime,
			downloads_remaining varchar(20),
			downloads varchar(2000),
			PRIMARY KEY (download_id)
		  ) $charset_collate;";
		dbDelta( $sql );
	}
}