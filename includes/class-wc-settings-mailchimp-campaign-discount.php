<?php
/**
 * WooCommerce Mailchimp Campaign Discount Settings
 *
 * @author 		Magnigenie
 * @category 	Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (  class_exists( 'WC_Settings_Page' ) ) :

/**
 * WC_Settings_Accounts
 */
class WC_Settings_Mailchimp_Campaign_Discount extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'mailchimp_campaign_discount';
		$this->label = __( 'Mailchimp Campaign Discount', 'wcmcd' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

		if( isset($_GET['tab']) && $_GET['tab'] == $this->id )
			add_action( 'admin_footer', array( $this, 'wcmcd_add_scripts') );

		add_action( 'woocommerce_admin_field_wcmcd_wpeditor', array( $this, 'wcmcd_display_editor' ) );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wcmcd_email', array( $this, 'wcmcd_save_editor_val' ), 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wcmcd_popup_text', array( $this, 'wcmcd_save_editor_val' ), 10, 3 );

		add_action( 'woocommerce_admin_field_wcmcd_uploader', array( $this, 'wcmcd_display_uploader' ) );
		add_action( 'woocommerce_admin_field_search_products', array( $this, 'wcmcd_search_products' ) );
		add_action( 'woocommerce_admin_field_exclude_products', array( $this, 'wcmcd_exclude_products' ) );
	}


	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {

		if ( function_exists('icl_object_id') )
			$wcmcd_email_body = __( 'Email content will be sent to the users when they register on the site. <a href="#" class="wcmcd-help">Click here</a> to see the list of variables you can use for <b>Email body and Email subject.</b></br>It Looks like you are using WPML, you can create your preferred language message by <a href="#" class="wcmcd-help">Click here</a>', 'wcmcd' );
		else
			$wcmcd_email_body = __( 'Email content will be sent to the users when they register on the site. <a href="#" class="wcmcd-help">Click here</a> to see the list of variables you can use for <b>Email body and Email subject.</b>', 'wcmcd' );

		
		return apply_filters( 'woocommerce_' . $this->id . '_settings', array(

			array(	'title' => __( 'Mailchimp Campaign Discount Settings', 'wcmcd' ), 'type' => 'title','desc' => '', 'id' => 'signup_discount_title' ),
      	array(
					'title' 	=> __( 'Enable', 'wcmcd' ),
					'desc' 		=> __( 'Enable mailchimp campaign discount plugin.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_enabled',
					'default' => 'no'
				),
        array(
					'title' 	=> __( 'Disable Discount', 'wcmcd' ),
					'desc' 		=> __( 'Disable discount for mailchimp sign ups and use it for normal mailchimp signups.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_disable_discount',
					'default' => 'no'
				),
				array(
					'title' 	=> __( "Mailchimp API Key", 'wcmcd' ),
					'type' 		=> 'text',
					'desc' 		=> __( 'Enter your mailchimp api key. To find your API Key <a href="http://kb.mailchimp.com/accounts/management/about-api-keys" target="_blank">click here</a>', 'wcmcd' ),
					'id'			=> 'wcmcd_api_key',
					'default'	=> '',
					'custom_attributes' => array( 'required' => 'required' )
				),
        array(
					'title' 	=> __( 'Disply on home only', 'wcmcd' ),
					'desc' 		=> __( 'Display the popup only when the user visits the homepage.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_home',
					'default' => 'no'
				),
        array(
					'title' 	=> __( 'Add Signup Source', 'wcmcd' ),
					'desc' 		=> __( 'Add SOURCE merge tag for each signup to track the signups', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_source',
					'default' => 'yes'
				),
				array(
					'title' 	=> __( 'Signup Source', 'wcmcd' ),
					'desc' 		=> __( 'This will be the signup source which can be shown in the mailchimp admin to check from where the user has been made signup', 'wcmcd' ),
					'type' 		=> 'text',
					'id'			=> 'wcmcd_source_link',
					'default' => 'WooCommerce Mailchimp Campaign Discount',
					'css'			=> 'width:350px',
				),
        array(
					'title' 	=> __( 'Double optin', 'wcmcd' ),
					'desc' 		=> __( 'In order to use double optin feature you need add a webhook with <strong>callback url</strong> as <strong>'. site_url('?mc_discount=1') . '</strong>. If you want to know how you can setup the webhook then <a href="http://magnigenie.com/how-to-create-mailchimp-webhooks/" target="_blank">follow this link</a>.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_double_optin',
					'default' => 'no'
				),
        array(
					'title' 	=> __( 'Send welcome', 'wcmcd' ),
					'desc' 		=> __( 'Send welcome message to subscribed users.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_welcome',
					'default' => 'yes'
				),
        array(
					'title' 	=> __( 'Restrict Email', 'wcmcd' ),
					'desc' 		=> __( 'Allow discount if the purchase is made for the same email id user registered on mailchimp.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_restrict',
					'default' => 'yes'
				),
        array(
					'title' 	=> __( 'Require user to be logged in to apply coupon', 'wcmcd' ),
					'desc' 		=> __( 'If you are using restrict email then you can use this option to require users to be logged in to apply coupon.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_loggedin',
					'default' => 'yes'
				),
        array(
					'title' 	=> __( 'Test E-Mail', 'wcmcd' ),
					'desc' 		=> __( 'This email would be excluded from the internal tracking so that you can unsubscribe on mailchimp and test multiple times.', 'wcmcd' ),
					'type' 		=> 'text',
					'id'			=> 'wcmcd_test_mail',
					'default' => get_option( 'admin_email' ),
					'css'			=> 'width:300px',
				),
				
				array(
					'title' 	=> __( 'Email From Name', 'wcmcd' ),
					'desc' 		=> __( 'Enter the name which will appear on the emails.', 'wcmcd' ),
					'id' 		  => 'wcmcd_email_name',
					'type' 		=> 'text',
					'css'		  => 'width:300px',
					'default'	=> get_bloginfo('name'),
					'desc_tip'=>  true
				),
				array(
					'title' 	=> __( 'From Email', 'wcmcd' ),
					'desc' 		=> __( 'Enter the email from which the emails will be sent.', 'wcmcd' ),
					'id' 		  => 'wcmcd_email_id',
					'type' 		=> 'text',
					'css'		  => 'width:300px',
					'default'	=> get_bloginfo('admin_email'),
					'desc_tip'=>  true
				),				
        array(
					'title' 	=> __( 'Disable popup', 'wcmcd' ),
					'desc' 		=> __( 'Disable popup and instead you can use [wcmcd] shortcode. <a href="#" class="wcmcd-help">Click here</a>  to see the details', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_disable_popup',
					'default' => 'no'
				),
        array(
					'title' 	=> __( 'Button/Link trigger for popup', 'wcmcd' ),
					'desc' 		=> __( 'Use button/link click to trigger the popup. <i>When using this feature make sure your button/link has a class attribute of wcmcd-trigger.</i> Example:<code>&lt;a href="#" class="wcmcd-trigger"&gt;Open Popup&lt;/a&gt;</code>', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_btn_trigger',
					'default' => 'no'
				),
        array(
					'title' 	=> __( 'Open popup only on button/link click', 'wcmcd' ),
					'desc' 		=> __( 'Enable this option if you want the popup to appear only on button/link click. This will disable automatic popup open feature.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_only_btn',
					'default' => 'no'
				),
        array(
					'title' 	=> __( 'Exit intent', 'wcmcd' ),
					'desc' 		=> __( 'Display popup based on exit intent', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_exit_intent',
					'default' => 'no'
				),
        array(
					'title' 	=> __( 'Close popup on overlay click', 'wcmcd' ),
					'desc' 		=> __( 'Close the popup when people click outside the popup?', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_overlay_click',
					'default' => 'no'
				),
				array(
					'title' 	=> __( 'Popup cookie length(days)', 'wcmcd' ),
					'id' 		  => 'wcmcd_cookie_length',
					'type' 		=> 'number',
					'desc' 		=> __( 'Enter the value for number of days the site should remember the visitor.', 'wcmcd' ),
					'default'	=> '30',
					'css' 		=> 'width: 60px;',
					'custom_attributes' => array( 'min' => '1', 'step' => '1' ),
					'desc_tip'=>  true
				),
				array(
					'title' 	=> __( 'Popup Background Image', 'wcmcd' ),
					'id' 		  => 'wcmcd_pop_bg',
					'type' 		=> 'wcmcd_uploader',
					'default'	=> '',
					'desc_tip'=>  true
				),
				array(
					'title' 	=> __( 'Popup Background Color', 'wcmcd' ),
					'id' 		  => 'wcmcd_pop_bgcolor',
					'type' 		=> 'color',
					'default'	=> '#2b2f3e',
					'css' 		=> 'width: 125px;',
					'desc_tip'=>  true
				),
				array(
					'title' 	=> __( 'Popup close button Color', 'wcmcd' ),
					'id' 		  => 'wcmcd_close_color',
					'type' 		=> 'color',
					'default'	=> '#fff',
					'css' 		=> 'width: 125px;',
					'desc_tip'=>  true
				),
				array(
					'title' 	=> __( 'Popup Overlay Color', 'wcmcd' ),
					'id' 		  => 'wcmcd_pop_overlay',
					'type' 		=> 'color',
					'default'	=> '#2e3865',
					'css' 		=> 'width: 125px;',
					'desc_tip'=>  true
				),
				array(
					'title' 	=> __( 'Popup Terms and Condition text color', 'wcmcd' ),
					'id' 		  => 'wcmcd_terms_text_color',
					'type' 		=> 'color',
					'default'	=> '#000000',
					'css' 		=> 'width: 125px;',
					'desc_tip'=>  true
				),
				array(
					'title' 	=> __( 'Popup header text color', 'wcmcd' ),
					'id' 		  => 'wcmcd_header_color',
					'type' 		=> 'color',
					'default'	=> '#000000',
					'css' 		=> 'width: 125px;',
					'desc_tip'=>  true
				),
        array(
					'title'   => __( 'Popup overlay opacity', 'wcmcd' ),
					'desc' 	  => __( 'Enter a value for the opacity value of the popup background.', 'wcmcd' ),
					'type' 	  => 'number',
					'id'	  	=> 'wcmcd_overlay_opacity',
					'css' 	  => 'width: 125px;',
					'default' => '0.8',
					'custom_attributes' => array( 'max' => '1', 'min' => '0.2', 'step' => '0.1' )
				),
				array(
					'title' 	=> __( 'Popup Height (px)', 'wcmcd' ),
					'id' 		  => 'wcmcd_popup_height',
					'type' 		=> 'number',
					'css' 		=> 'width: 125px;',
					'default'	=> '0',
					'desc' 		=> __( 'Enter a height for the popup. Put 0 for auto height', 'wcmcd' ),
					'desc_tip'=>  false
				),
				array(
					'title' 	=> __( 'Popup Width (px)', 'wcmcd' ),
					'id' 		  => 'wcmcd_popup_width',
					'type' 		=> 'number',
					'css' 		=> 'width: 125px;',
					'default'	=> '540',
					'desc' 		=> __( 'Enter a width for the popup. Put 0 for auto width', 'wcmcd' ),
					'desc_tip'=>  false
				),
				array(
					'title' 	=> __( 'Popup Content Top Position (px)', 'wcmcd' ),
					'id' 		  => 'wcmcd_content_top',
					'type' 		=> 'number',
					'css'		  => 'width: 125px;',
					'default'	=> '0',
					'desc' 		=> __( 'Enter number of pixel for the popup content from the top. ', 'wcmcd' ),
					'desc_tip'=>  false
				),
				array(
					'title' 	=> __( 'Popup Content Left Position (px)', 'wcmcd' ),
					'id' 		  => 'wcmcd_content_left',
					'type' 		=> 'number',
					'css'		  => 'width: 125px;',
					'default'	=> '0',
					'desc' 		=> __( 'Enter number of pixel for the popup content from the left. ', 'wcmcd' ),
					'desc_tip'=>  false
				),
				array(
					'title' 	=> __( "Popup Animation effect", 'wcmcd' ),
					'type' 		=> 'select',
					'id'			=> 'wcmcd_popup_animation',
					'options' => array(
						'mfp-with-fade' 		=> __( 'Fade In', 'wcmcd' ),
						'mfp-tada' 					=> __( 'Tada', 'wcmcd' ),
						'mfp-shake' 				=> __( 'Shake', 'wcmcd' ),
						'mfp-zoom-out' 			=> __( 'Zoom Out', 'wcmcd' ),
						'mfp-zoom-in' 			=> __( 'Zoom In', 'wcmcd' ),
						'mfp-3d-unfold' 		=> __( '3D Unfold', 'wcmcd' ),
						'mfp-3d-sign' 			=> __( '3D Sign', 'wcmcd' ),
						'mfp-move-from-top' => __( 'Move From Top', 'wcmcd' ),
						'mfp-move-horizontal' => __( 'Move Horizontal', 'wcmcd' ),
						'mfp-slide-right' 	=> __( 'Slide Right', 'wcmcd' ),
						'mfp-newspaper' 		=> __( 'Newspaper', 'wcmcd' ),
          			),
					'default' => 'mfp-with-fade'
				),
        array(
					'title' 	=> __( 'Close hinge effect', 'wcmcd' ),
					'desc' 		=> __( 'Enable hinge effect when closing the modal.', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_hinge',
					'default' => 'no'
				),
        array(
					'title' 	=> __( 'Disable popup on mobile', 'wcmcd' ),
					'desc' 		=> __( 'Disable popup on mobile devices', 'wcmcd' ),
					'type' 		=> 'checkbox',
					'id'			=> 'wcmcd_disable_mobile_popup',
					'default' => 'no'
				),
				array(
					'title' 	=> __( 'Popup header text', 'wcmcd' ),
					'id' 		  => 'wcmcd_pop_header',
					'type' 		=> 'textarea',
					'css'		  => 'width: 350px;',
					'default'	=> '',
					'desc' 		=> __( 'Enter the text that would appear on the top of the popup.', 'wcmcd' ),
					'desc_tip'=>  false
				),
				array(
					'title' 	=> __( 'Popup Text', 'wcmcd' ),
					'desc' 		=> __( 'Popup text will be shown when a new user visits the site. Use <b>{wcmcd_FORM}</b> to place the form inside the popup.', 'wcmcd' ),
					'id' 		  => 'wcmcd_popup_text',
					'type' 		=> 'wcmcd_wpeditor',
					'default'	=> '<p style="text-align: center;"><span style="color: #33d5aa;">ENTER YOUR EMAIL AND GET</span></p><h1 style="text-align: center; margin: 0; font-size: 55px;"><span style="color: #cccccc;">10% OFF</span></h1>{wcmcd_FORM}<p style="text-align: center;"><span style="color: #999999;">Be the first to know about our exclusive items, New catalogs and special promotions.</span></p>',
					'desc_tip'=>  true
				),
				array(
					'title' 	=> __( 'Display popup after(seconds)', 'wcmcd' ),
					'id' 		  => 'wcmcd_dis_seconds',
					'type' 		=> 'number',
					'css' 		=> 'width: 45px;',
					'default'	=> '3',
					'desc' 		=> __( 'Enter a value for the number of seconds after which the popup will be displayed.', 'wcmcd' ),
					'desc_tip'=>  false
				),
				array(
					'title' 	=> __( 'Automatically close popup after(seconds)', 'wcmcd' ),
					'id' 		  => 'wcmcd_close_seconds',
					'type' 		=> 'number',
					'css' 		=> 'width: 45px;',
					'default'	=> '0',
					'desc' 		=> __( 'Enter a value if you want to close the popup automatically when a user successfully subscribes to your mailchimp list. Enter 0 if you don\'t want to close the popup automatically', 'wcmcd' ),
					'desc_tip'=>  false
				),
				array(
					'title' 	=> __( 'Submit button text', 'wcmcd' ),
					'desc' 		=> '',
					'id' 		  => 'wcmcd_btn_text',
					'type' 		=> 'text',
					'default'	=> __( 'SUBSCRIBE', 'wcmcd' ),
				),
				array(
					'title' 	=> __( 'Submit button color', 'wcmcd' ),
					'desc' 		=> '',
					'id' 		  => 'wcmcd_btn_color',
					'type' 		=> 'color',
					'css' 		=> 'width: 125px;',
					'default'	=> '#33d5aa',
				),
				array(
					'title' 	=> __( 'Submit button hover color', 'wcmcd' ),
					'desc' 		=> '',
					'id' 		  => 'wcmcd_btn_hover',
					'type' 		=> 'color',
					'css' 		=> 'width: 125px;',
					'default'	=> '#21b990',
				),
				array(
					'title' 	=> __( 'Submit button text color', 'wcmcd' ),
					'desc' 		=> '',
					'id' 		  => 'wcmcd_btn_txt_color',
					'type' 		=> 'color',
					'css' 		=> 'width: 125px;',
					'default'	=> '#2b2f3e',
				),
				array(
					'title' 	=> __( 'Success message text color', 'wcmcd' ),
					'desc' 		=> 'This will be the text color for the success message',
					'id' 		  => 'wcmcd_success_txt_color',
					'type' 		=> 'color',
					'css' 		=> 'width: 125px;',
					'default'	=> '#21b990',
				),
				array(
					'title' 	=> __( 'Success message background color', 'wcmcd' ),
					'desc' 		=> 'This will be the background color for the success message',
					'id' 		  => 'wcmcd_success_bg_color',
					'type' 		=> 'color',
					'css' 		=> 'width: 125px;',
					'default'	=> '#FFFFFF',
				),				
				array(
					'title' 	=> __( 'Error message text color', 'wcmcd' ),
					'desc' 		=> 'This will be the text color for the error message',
					'id' 		  => 'wcmcd_error_txt_color',
					'type' 		=> 'color',
					'css' 		=> 'width: 125px;',
					'default'	=> '#de0b0b',
				),
				array(
					'title' 	=> __( 'Error message background color', 'wcmcd' ),
					'desc' 		=> 'This will be the background color for the error message',
					'id' 		  => 'wcmcd_error_bg_color',
					'type' 		=> 'color',
					'css' 		=> 'width: 125px;',
					'default'	=> '#FFFFFF',
				),												
				array(
					'title' 	=> __( 'Form width (in px)', 'wcmcd' ),
					'desc' 		=> __( 'Enter the subscription form width. Enter 0 for auto width.', 'wcmcd' ),
					'id' 		  => 'wcmcd_form_width',
					'type' 		=> 'number',
					'css' 		=> 'width: 105px;',
					'default'	=> '500',
				),
				array(
					'title' 	=> __( 'Form alignment', 'wcmcd' ),
					'desc' 		=> '',
					'id' 		  => 'wcmcd_form_alignment',
					'type' 		=> 'select',
					'options'	=> array( 'left' => 'Left', 'right' => 'Right', 'none' => 'Center'),
					'default'	=> 'none',
				),
				array(
					'title' 	=> __( 'Success message', 'wcmcd' ),
					'id' 		  => 'wcmcd_success_msg',
					'type' 		=> 'textarea',
					'css' 		=> 'width: 350px;',
					'default'	=> __( 'Thank you for subscribing! Check your mail for coupon code!', 'wcmcd' ),
					'desc' 		=> __( 'Enter success message which will appear when user successfully subscribes to your mailchimp list. Use {COUPONCODE} variable for the generated coupon code. Remember this variable would work only in single option', 'wcmcd' ),
					'desc_tip'=>  false
				),
				array( 'type' => 'sectionend', 'id' => 'simple_wcmcd_options'),

		)); // End pages settings
	}
	
	/**
	* Output wordpress editor for email body condent.
	*
	* @param array $value array of settings variables.
	* @return null displays the editor.
	*
	*/
	public function wcmcd_display_editor( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] ); ?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<?php echo $value['desc']; ?>
				<?php wp_editor( $option_value, esc_attr( $value['id'] ) ); ?>
			</td>
		</tr>
	<?php
	}

	/**
	* Saves the content fpr wp_editor.
	*
	* @return null saves the value of the option.
	*
	*/
	public function wcmcd_save_editor_val( $value, $option, $raw_value ) {
		update_option( $option['id'], $raw_value  );
	}

	/**
	* Output wordpress file uploader.
	*
	* @param array $value array of settings variables.
	* @return null displays the editor.
	*
	*/
	public function wcmcd_display_uploader( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] ); ?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<div class="uploader">
					<input value="<?php echo $option_value; ?>" id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" type="text" />
					<input id="wcmcd_button" class="button" type="button" value="Upload" />
					<div class="wcmcd_image">
						<?php if($option_value != '') {
							echo '<img src="'.$option_value.'" style="width: 100px;" alt="">';
							} ?>
					</div>
				</div>
			</td>
		</tr>
	<?php
	}


	/**
	* Product ids
	*/
	public function wcmcd_search_products() {
		?>
		<tr valign="top" class="search-products">
			<th><?php _e( 'Products', 'woocommerce' ); ?></th>
			<td>
				<input type="hidden" class="wcmcd wc-product-search" data-multiple="true" style="width: 50%;" name="wcmcd_products" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="wcmcd_ajax_products" data-selected="<?php
					$product_ids = array_filter( array_map( 'absint', explode( ',', get_option( 'wcmcd_products' ) ) ) );
					$json_ids    = array();

					foreach ( $product_ids as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( is_object( $product ) ) {
							$json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
						}
					}
					echo esc_attr( json_encode( $json_ids ) );
				?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
			</td>
		</tr>

	<?php
	}

	/**
	* Exclude Product Ids
	*/
	public function wcmcd_exclude_products() {
		?>
		<tr valign="top" class="search-products">
			<th><?php _e( 'Exclude Products', 'woocommerce' ); ?></th>
			<td>
				<input type="hidden" class="wcmcd wc-product-search" data-multiple="true" style="width: 50%;" name="wcmcd_exclude_products" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="wcmcd_ajax_products" data-selected="<?php
					$product_ids = array_filter( array_map( 'absint', explode( ',', get_option( 'wcmcd_exclude_products' ) ) ) );
					$json_ids    = array();

					foreach ( $product_ids as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( is_object( $product ) ) {
							$json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
						}
					}

					echo esc_attr( json_encode( $json_ids ) );
				?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
			</td>
		</tr>

	<?php
	}

	/**
	* Add the required js needed for the plugin to display the list of products using ajax.
	*
	* @return null outputs the scripts on the footer.
	*
	*/
	public function wcmcd_add_scripts() {
	?>
		<script type="text/javascript">
			jQuery(function($){
			// Ajax product search box
			$( ':input.wcmcd.wc-product-search' ).each( function() {
				var select2_args = {
					allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
					placeholder: $( this ).data( 'placeholder' ),
					minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
					escapeMarkup: function( m ) {
						return m;
					},
					ajax: {
						url:            '<?php echo admin_url('admin-ajax.php'); ?>',
						dataType:    'json',
						quietMillis: 250,
						data: function( term ) {
							return {
								term:     term,
								action:   'wcmcd_ajax_products',
								security: '<?php echo wp_create_nonce( "wcmcd-search-products" ); ?>',
								exclude:  $( this ).data( 'exclude' ),
								include:  $( this ).data( 'include' ),
								limit:    $( this ).data( 'limit' )
							};
						},
						results: function( data ) {
							var terms = [];
							if ( data ) {
								$.each( data, function( id, text ) {
									terms.push( { id: id, text: text } );
								});
							}
							return {
								results: terms
							};
						},
						cache: true
					}
				};

				if ( $( this ).data( 'multiple' ) === true ) {
					select2_args.multiple = true;
					select2_args.initSelection = function( element, callback ) {
						var data     = $.parseJSON( element.attr( 'data-selected' ) );
						var selected = [];

						$( element.val().split( ',' ) ).each( function( i, val ) {
							selected.push({
								id: val,
								text: data[ val ]
							});
						});
						return callback( selected );
					};
					select2_args.formatSelection = function( data ) {
						return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
					};
				} else {
					select2_args.multiple = false;
					select2_args.initSelection = function( element, callback ) {
						var data = {
							id: element.val(),
							text: element.attr( 'data-selected' )
						};
						return callback( data );
					};
				}

				//select2_args = $.extend( select2_args, getEnhancedSelectFormatString() );

				$( this ).select2( select2_args ).addClass( 'enhanced' );
			});


				jQuery('.wcmcd-help').click(function(){
					jQuery('#contextual-help-link').click();
				});
				jQuery('#tab-panel-wcmcd_help input').click(function(){
					jQuery(this).select();
				});

				// Image uploader js
				var _custom_media = true;

					jQuery('#wcmcd_button').click(function(e) {
						_orig_send_attachment = wp.media.editor.send.attachment;
						var send_attachment_bkp = wp.media.editor.send.attachment;
						var button = jQuery(this);
						var input_file = button.parent().find('input[type="text"]');
						_custom_media = true;
						wp.media.editor.send.attachment = function(props, attachment){
							if ( _custom_media ) {
								input_file.val(attachment.url);
								button.parent().find('.wcmcd_image').html('<img src="'+attachment.url+'" width="100px;">');
							} else {
								return _orig_send_attachment.apply( this, [props, attachment] );
							};
						}
						wp.media.editor.open(button);
						return false;
					});

				jQuery('.add_media').on('click', function(){
					_custom_media = false;
				});

				jQuery('.wcmcd_cats').select2();
			});
		</script>
	<?php
	}
}
return new WC_Settings_Mailchimp_Campaign_Discount();

endif;