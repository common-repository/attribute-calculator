<?php


include_once('AttributeCalculator_LifeCycle.php');

class AttributeCalculator_Plugin extends AttributeCalculator_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'multiplyBy' => array(__('Slug of the attribute to multiply by. (typically length)', 'uwcpc')),
            'isEnabled' => array(__('1', 'uwcpc'))
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'Attribute Calculator';
    }

    protected function getMainPluginFileName() {
        return 'attribute-calculator.php';
    }

    protected function installDatabaseTables() {

    }

    protected function unInstallDatabaseTables() {

    }

    public function upgrade() {
    }

    public function addActionsAndFilters() {

               // Add options administration page
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));
   		
		// Get custom field value, calculate new item price, save it as custom cart item data
		add_filter('woocommerce_add_cart_item_data', array(&$this, 'add_custom_field_data'), 20, 3);
		
		// Set the new calculated cart item price
		add_action('woocommerce_before_calculate_totals', array(&$this, 'extra_price_add_custom_price'), 20, 1);		
		add_filter('woocommerce_cart_item_price', array(&$this, 'display_cart_items_custom_price_details'), 20, 3);
		
		// load calculator Js script if we are on the product page	
		add_action('wp_enqueue_scripts', array(&$this, 'calculate_length_change_script'));
		
		add_action('admin_enqueue_scripts', array(&$this, 'attcalc_admin_scripts'));
		
		add_action('add_meta_boxes', [&$this, 'attcalc_create_metabox']);

    }	
	public function attcalc_admin_scripts(){
		wp_register_script('attcalc-admin', plugin_dir_url(__FILE__) . '/js/calcadmin.js', array('jquery'), '', true );
	}
	public function attcalc_create_metabox(){
		if(is_admin()){		
			add_meta_box('attcalc-control-panel', 'Attribute Calculator Settings', [&$this, 'attcalc_control_panel'], 'product', 'normal', 'high');
		}		
	}
	public function attcalc_control_panel(){
		echo 'raaaaaaaaaaaaaaaaaaaaaaawr';
	}
	public function calculate_length_change_script(){
		global $post_type;
		wp_register_script('frontcalc', plugin_dir_url(__FILE__) . '/js/calculate.js', array('jquery'), '', true );
		
		if($this->getOption('isEnabled') == '1' && $post_type == 'product'){
			global $woocommerce;
			$multiplyBy = $this->getOption('multiplyBy');
			$variError = __('There is no variation named: ', 'uwcpc') . $this->getOption('multiplyBy');
			$theCurrency = get_woocommerce_currency_symbol();
			wp_localize_script('frontcalc', 'theVal', array('multiplyBy' =>$multiplyBy, 'theError'=>$variError, 'theCurrency'=>$theCurrency));
			wp_enqueue_script('frontcalc');
		}
		
	}

	function display_custom_item_data($cart_item_data, $cart_item) {
	
		return $cart_item_data;
	}
	function display_cart_items_custom_price_details($product_price, $cart_item, $cart_item_key) {
		
		$theMulti = 'attribute_pa_';
		(""!=($this->getOption('multiplyBy'))?$theMulti.=$this->getOption('multiplyBy'):$theMulti.='length');
		
		$daLen = $cart_item['variation'][$theMulti];
		if (isset($cart_item['custom_data']['base_price']) && isset($cart_item['custom_data']['new_price']) ) {
				$product = $cart_item['data'];
				$base_price = $cart_item['custom_data']['base_price'];
				$product_price = wc_price(wc_get_price_to_display($product, array('price' => $base_price))).
				'<br>';

				if (isset($cart_item['variation'][$theMulti])) {						
					$product_price.= 'x ' . $daLen . ' = ' . wc_price($cart_item['line_subtotal']);
				}
		}
		return $product_price;
	}
	function extra_price_add_custom_price($cart) {
		if (is_admin() && !defined('DOING_AJAX'))
				return;

		foreach($cart-> get_cart() as $cart_item) {
				if (isset($cart_item['custom_data']['new_price']))
						$cart_item['data']->set_price((float) $cart_item['custom_data']['new_price']);
		}
	}
	function add_custom_field_data($cart_item_data, $product_id, $variation_id) {
		$theMulti = 'attribute_pa_';
		(""!=($this->getOption('multiplyBy'))?$theMulti.=$this->getOption('multiplyBy'):$theMulti.='length');
		
		if (isset($_POST[$theMulti]) ) {
		
				//$cPrice = substr($_POST[$theMulti], 0, strpos($_POST[$theMulti], '-'));
				$cPrice = $_POST[$theMulti];
				$_product_id = $variation_id > 0 ? $variation_id : $product_id;

				$product = wc_get_product($_product_id); // The WC_Product Object
				$base_price = (float) $product->get_regular_price(); // Get the product regular price
				
				echo 'post:'. $_POST[$theMulti];
				echo '</br>cprice:'.$cPrice;
				echo '</br>baseprice:'.$base_price;

				$cart_item_data['custom_data']['base_price'] = $base_price;
				$cart_item_data['custom_data']['new_price'] = $base_price * $cPrice;
		}

		// Make each cart item unique
		if (isset($cart_item['variation'][$theMulti]) ) {
				$cart_item_data['custom_data']['unique_key'] = md5(microtime().rand());
		}

		return $cart_item_data;
	}
	
	    public function settingsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'attribute-calculator'));
        }
		wp_enqueue_script('attcalc-admin');
		wp_enqueue_style('attcalc-admin-style', plugins_url('/css/admin-style.css', __FILE__));
        $optionMetaData = $this->getOptionMetaData();

        // Save Posted Options
        if ($optionMetaData != null) {
            foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                if (isset($_POST[$aOptionKey])) {
                    $this->updateOption($aOptionKey, $_POST[$aOptionKey]);
                }
            }
        }

        // HTML for the page
        $settingsGroup = get_class($this) . '-settings-group';
        ?>
        <div class="wrap">
            <h2><?php _e('System Settings', 'attribute-calculator'); ?></h2>
            <table cellspacing="1" cellpadding="2"><tbody>
            <tr><td><?php _e('System', 'attribute-calculator'); ?></td><td><?php echo php_uname(); ?></td></tr>
            <tr><td><?php _e('PHP Version', 'attribute-calculator'); ?></td>
                <td><?php echo phpversion(); ?>
                <?php
                if (version_compare('5.2', phpversion()) > 0) {
                    echo '&nbsp;&nbsp;&nbsp;<span style="background-color: #ffcc00;">';
                    _e('(WARNING: This plugin may not work properly with versions earlier than PHP 5.2)', 'attribute-calculator');
                    echo '</span>';
                }
                ?>
                </td>
            </tr>
            <tr><td><?php _e('MySQL Version', 'attribute-calculator'); ?></td>
                <td><?php echo $this->getMySqlVersion() ?>
                    <?php
                    echo '&nbsp;&nbsp;&nbsp;<span style="background-color: #ffcc00;">';
                    if (version_compare('5.0', $this->getMySqlVersion()) > 0) {
                        _e('(WARNING: This plugin may not work properly with versions earlier than MySQL 5.0)', 'attribute-calculator');
                    }
                    echo '</span>';
                    ?>
                </td>
            </tr>
            </tbody></table>

            <h2><?php echo $this->getPluginDisplayName(); echo ' '; _e('Settings', 'attribute-calculator'); ?></h2>

            <form method="post" action="">
            <?php settings_fields($settingsGroup); ?>
              
                <table class="plugin-options-table"><tbody>
                <?php
                if ($optionMetaData != null) {
                    foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                        $displayText = is_array($aOptionMeta) ? $aOptionMeta[0] : $aOptionMeta;
						($displayText==1 || $displayText==2) ? $displayText = "Enable Globally" : null ;
						?>
							<tr valign="top">
								<th scope="row"><p>--<label for="<?php echo $aOptionKey ?>"><?php echo $displayText ?></label></p></th>
								<td>
								<?php $this->createFormControl($aOptionKey, $aOptionMeta, $this->getOption($aOptionKey)); ?>
								</td>
							</tr>
						<?php
						
                    }
                }
                ?>
                </tbody></table>
                <p class="submit">
                    <input type="submit" class="button-primary"
                           value="<?php _e('Save Changes', 'attribute-calculator') ?>"/>
                </p>
            </form>
        </div>
        <?php

    }


}
