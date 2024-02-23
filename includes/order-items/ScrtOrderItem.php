<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class ScrtOrderItem {

    /**
	 * The order items table name.
	 *
	 * @since    2.6
	 * @access   public
	 * @var      string    $table_name    The order items table name.
	 */
	public $table_name = 'ncs_order_items';

    /**
	 * The order items meta table name.
	 *
	 * @since    2.6
	 * @access   public
	 * @var      string    $table_name    The order items meta table name.
	 */
	public $meta_table_name = 'ncs_order_itemmeta';

    protected $attrs, $cols, $meta, $defaults;

    public function __construct($obj = null) {
        $this->initialize(
            array(
                //'id'              => 0,
                'order_id'          => 0,
                'product_id'        => 0,
                'price_id'          => '',
                'item_type'         => '',
                'product_name'      => '',
                'price_name'        => '',
                'total_amount'      => 0.00,
                'tax_amount'        => 0.00,
            ),
            // common meta
            array(
                'unit_price'        => 0.00,
                'quantity'          => 0,
                'subtotal'          => 0.00,
                'discount_amount'   => 0.00,
                'shipping_amount'   => 0.00,
                'sign_up_fee'       => 0.00,
                'trial_days'        => 0,
                'tax_rate'          => '',
                'tax_desc'          => '',
                'purchase_note'     => '',
            ),
            $obj
        );
    }
    
    public function initialize($defaults, $meta, $obj=null) {
        $meta = apply_filters('sc_order_item_meta',$meta);
        $this->defaults = $defaults;
        $this->attrs = array_merge(array_keys($defaults), array_keys($meta));      
        $this->cols = array_keys($defaults);
        $this->meta = array_keys($meta);
        $this->id = 0;
        if(is_numeric($obj) && $obj > 0) {
            
            if ($res = $this->get_item($obj)) {
                $this->id = $obj;
            } else {
                $this->id = false;
                return;
            }

            foreach ($defaults as $key => $val) {
                $this->$key = $res->$key ?? $val;
            }

            foreach ($meta as $key => $val) {
                if(!$this->$key = $this->get_meta($key)) {
                    $this->$key = $val;
                }
            }
        } else {
            foreach ([$defaults, $meta] as $set) {
                foreach ($set as $key => $value) {
                    $this->$key = $value;
                }
            }
        } 
    }

    public function store() {
        if ($this->id) {
            $this->update();
        } else {
            $this->create();
        }
        return $this->id;
    }

    public function create() {

        global $wpdb, $sc_debug_logger;

        $args = array();
        foreach($this->cols as $col) {
            $args[$col] = $this->$col;
        }

        $sc_debug_logger->log_debug("Creating order items: ".print_r($args, true));

        $wpdb->insert( $wpdb->prefix . $this->table_name, $args );

        if($this->id = $wpdb->insert_id) {
            $sc_debug_logger->log_debug("order item added to db");
            foreach($this->meta as $key) {
                if(isset($this->$key) && $this->$key){
                    $this->update_meta($key, $this->$key);
                }
            }
        } else {
            $sc_debug_logger->log_debug("wpdb last query: ".print_r($wpdb->last_query, true));
            $sc_debug_logger->log_debug("wpdb last result: ".print_r($wpdb->last_result, true));
            $sc_debug_logger->log_debug("wpdb last error: ".print_r($wpdb->last_error, true));
        }
    }
        
    public function update() {
        global $wpdb;

        $args = array();
        foreach($this->cols as $col) {
            $args[$col] = $this->$col;
        }

        $updated = $wpdb->update( $wpdb->prefix . $this->table_name, $args, array( 'order_item_id' => $this->id ) );

        if(false !== $updated) {
            foreach($this->meta as $key) {
                if(isset($this->$key) && $this->$key){
                    $this->update_meta($key, $this->$key);
                }
            }
        } 
    }

    public function get_meta($meta_key='', $single=true) {
        
        if(!$this->id) {
            return false;
        }

        return get_metadata( 'ncs_order_item', $this->id, $meta_key, $single );
    }

    public function update_meta($meta_key, $value='') {
        
        if(!$this->id) {
            return false;
        }

        return update_metadata( 'ncs_order_item', $this->id, $meta_key, $value );
    }

    public function add_meta($meta_key, $value='') {
        
        if(!$this->id) {
            return false;
        }

        return add_metadata( 'ncs_order_item', $this->id, $meta_key, $value );
    }

    public function delete_meta($meta_key='') {
        
        if(!$this->id) {
            return false;
        }

        return delete_metadata( 'ncs_order_item', $this->id, $meta_key );
    }

    public static function get_order_items($order_id) {
        global $wpdb;

        if(!$order_id) {
            return false;
        }

        $items = array();

        $obj = new ScrtOrderItem();

        $table_name = $obj->table_name;

        $query = "SELECT order_item_id FROM {$wpdb->prefix}{$table_name} WHERE order_id = %d";

        $results = $wpdb->get_results($wpdb->prepare($query,$order_id));
        if(is_countable($results)) {
            foreach($results as $res) {
                $item = new ScrtOrderItem($res->order_item_id);
                if($item->id) {
                    $items[] = $item;
                }   
            }
        }
        return $items;

    }

    public function delete_item() {

        global $wpdb;

        if(!$this->id) {
            return false;
        }

        $keys = $this->get_meta();
        foreach($keys as $key=>$val)  {
            $this->delete_meta($key);
        }

        return $wpdb->delete($wpdb->prefix.$this->table_name, array( 'order_item_id' => $this->id ), array( '%d' ));

    }

    private function get_item($id=0) {

        global $wpdb;

        if(!$id) {
            return false;
        }

        $table_name = $this->table_name;

        $query = "SELECT * FROM {$wpdb->prefix}{$table_name} WHERE order_item_id = %d";

        return $wpdb->get_results($wpdb->prepare($query,$id))[0];
    }

    public function get_data() {

        if($this->id===false) {
            return false;
        }
        
        $data = array('id'=>$this->id);
        foreach ($this->attrs as $key) {
            $data[$key] = $this->$key;
        }

        return $data;
    }
}