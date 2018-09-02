<?php

class WooCommerce_Mailchimp_Campaign_Discount {

  /**
  * Bootstraps the class and hooks required actions & filters.
  *
  */
  private $wcmcd_enabled;
  
  public function __construct() {
    
    $this->wcmcd_enabled = get_option( 'wcmcd_enabled' );


    //Check if woocommerce plugin is installed.
    add_action( 'admin_notices', array( $this, 'check_required_plugins' ) );

    //Add setting link for the admin settings
    add_filter( "plugin_action_links_".WCMCD_BASE, array( $this, 'wcmcd_settings_link' ) );

    //Add backend settings
    add_filter( 'woocommerce_get_settings_pages', array( $this, 'wcmcd_settings_class' ) );

    //Add shortcode support on the widgets
    add_filter( 'widget_text', 'do_shortcode' );

    //Add help tab for displaying the use for the variables in email
    add_action( "current_screen", array( $this, 'add_tabs' ), 50 );

    add_action( 'init', array($this, 'campaign_post_type'), 0 );

    //Add coupon metabox
    add_action( 'add_meta_boxes', array( $this, 'add_coupons_metabox' ) );

    //Add coupon metabox
    add_action( 'add_meta_boxes', array( $this, 'add_campaign_fields' ) );

    //Add shortcode metabox
    add_action( 'add_meta_boxes', array( $this, 'add_coupon_shortcode_metabox' ) );

    //Save coupon metabox
    add_action('save_post', array($this, 'save_coupon_meta_box'));

    //Save coupon metabox
    add_action('save_post', array($this, 'save_campaign_fields_meta_box'));

    //Save shortcode metabox
    add_action('save_post', array($this, 'save_coupon_shortcode_metabox'));

    add_shortcode( 'wcmcd', array($this, 'campaign_shortcode_attr'));

    //Add shortcode for mailchimp discount.
    //add_shortcode( 'wc_mailchimp_campaign_discount', array( $this, 'wcmcd_shortcode' ) );

    add_action( 'admin_enqueue_scripts',  array( $this, 'wcmcd_enque_admin_scripts' ) );


    if( $this->wcmcd_enabled == 'yes' ) {
    
      //Add css and js files for the popup
      add_action( 'wp_enqueue_scripts',  array( $this, 'wcmcd_enque_scripts' ) );

      //show popup in the store frontend
      $cookie =  ( isset( $_COOKIE['wcmcd'] ) && $_COOKIE['wcmcd'] == 'yes' ) ? 'yes' : 'no';
      
      // if( get_option( 'wcmcd_disable_popup' ) != 'yes' && $cookie == 'no' || get_option( 'wcmcd_btn_trigger' ) == 'yes' )
      //   add_action( 'wp_footer', array( $this, 'wcmcd_display_popup') );

      if( get_option('wcmcd_restrict') == 'yes' && get_option('wcmcd_loggedin') == 'yes' )
        add_filter('woocommerce_coupon_is_valid', array( $this,'validate_coupon' ), 10, 2);

      //Mailchimp user registration.
      add_action( 'wp_ajax_wcmcd_subscribe', array( $this, 'wcmcd_subscribe' ) );
      add_action( 'wp_ajax_nopriv_wcmcd_subscribe', array( $this, 'wcmcd_subscribe' ) );
    }

    //add_shortcode( 'wcmcd', array( $this, 'wcmcd_lang_func' ) );

    add_action( 'wp_ajax_wcmcd_ajax_products', array( $this, 'wcmcd_ajax_products' ) );

    add_action( 'wp_enqueue_scripts',  array( $this, 'wcmcd_enque_scripts_front' ) );

  }

  /*
  * Creating a function to create our campaign post type
  */
   
  function campaign_post_type() {
   
    // Set UI labels for Custom Post Type
    $labels = array(
      'name'                => _x( 'Campaigns', 'Post Type General Name', 'wcmcd' ),
      'singular_name'       => _x( 'Campaign', 'Post Type Singular Name', 'wcmcd' ),
      'menu_name'           => __( 'Campaigns', 'wcmcd' ),
      'all_items'           => __( 'All Campaigns', 'wcmcd' ),
      'view_item'           => __( 'View Campaign', 'wcmcd' ),
      'add_new_item'        => __( 'Add New Campaign', 'wcmcd' ),
      'add_new'             => __( 'Add New Campaign', 'wcmcd' ),
      'edit_item'           => __( 'Edit Campaign', 'wcmcd' ),
      'update_item'         => __( 'Update Campaign', 'wcmcd' ),
      'search_items'        => __( 'Search Campaign', 'wcmcd' ),
      'not_found'           => __( 'Not Found', 'wcmcd' ),
      'not_found_in_trash'  => __( 'Not found in Trash', 'wcmcd' ),
    );
       
    // Set other options for Custom Post Type campaign
    $args = array(
      'label'               => __( 'campaign', 'wcmcd' ),
      'description'         => __( 'Offer based on Campaigns', 'wcmcd' ),
      'labels'              => $labels,
      // Features this CPT supports in Post Editor
      'supports'            => array( 'title', 'editor',  'revisions' ),
      /* A hierarchical CPT is like Pages and can have
      * Parent and child items. A non-hierarchical CPT
      * is like Posts.
      */ 
      'hierarchical'        => false,
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => true,
      'show_in_nav_menus'   => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => 5,
      'can_export'          => true,
      'has_archive'         => true,
      'exclude_from_search' => false,
      'publicly_queryable'  => true,
      'capability_type'     => 'page',
    );
       
    // Registering your Custom Post Type
    register_post_type( 'campaign', $args );
  }

  //Add metabox for campaign shortcode
  public function add_coupon_shortcode_metabox() {
    add_meta_box("coupon-shortcode-box", "Campaign Shortcode", array($this, "coupon_shortcode_meta_box_markup"), "campaign", "side", "default", null);
  }

  public function coupon_shortcode_meta_box_markup($post) { ?>
    <input type="text" name="wcmcd_shortcode" id = "wcmcd_shortcode" value = "[wcmcd id=<?php echo $post->ID; ?>]" readonly>
   <?php 
  }

  // save coupon shortcode metabox value
  public function save_coupon_shortcode_metabox($post_id) {
    if(isset($_POST["wcmcd_shortcode"])) {
      $wcmcd_shortcode = sanitize_text_field( $_POST["wcmcd_shortcode"] );
      update_post_meta($post_id, "wcmcd_shortcode", $wcmcd_shortcode);
    }
  }

  // add post box to post page
  public function add_campaign_fields() {
    add_meta_box("campaign-fields-box", "Campaign Display Fields", array($this, "campaign_fields_markup"), "campaign", "normal", "high", null);
  }

  // add post box to post page
  public function add_coupons_metabox() {
    add_meta_box("coupon-meta-box", "Coupon Settings", array($this, "coupon_meta_box_markup"), "campaign", "normal", "high", null);
  }




  public function campaign_fields_markup($post) {
    wp_nonce_field(basename(__FILE__), "meta-box-nonce");

    $list_Id = get_post_meta($post->ID,'wcmcd_list_id', true);

    $wcmcd_display_fields = get_post_meta($post->ID, 'wcmcd_display_fields', true);

    $wcmcd_campaign_fields = get_post_meta($post->ID, "wcmcd_campaign_fields", true);

    $wcmcd_enable_terms_condition = get_post_meta($post->ID, 'wcmcd_enable_terms_condition', true);
    $wcmcd_enable_redirect = get_post_meta($post->ID, 'wcmcd_enable_redirect', true);
    $wcmcd_redirect_url = get_post_meta($post->ID, 'wcmcd_redirect_url', true);
    $wcmcd_redirect_after_time = get_post_meta($post->ID, 'wcmcd_redirect_after_time', true);
    $wcmcd_terms_condition_text = get_post_meta($post->ID, 'wcmcd_terms_condition_text', true);
    $wcmcd_terms_condition_error_msg = get_post_meta($post->ID, 'wcmcd_terms_condition_error_msg', true);
    $field_types = array('Select Type', 'Text', 'Checkbox', 'Select', 'Radio');
    ?>
    <div>
      <table class="form-table">
        <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_list_id"><?php _e( 'Mailchimp list id', 'wcmc' ); ?></label>
            </th>
            <td class="forminp forminp-text">
              <input  name="wcmcd_list_id" id="wcmcd_list_id" type="text" style="width:100%" placeholder="Enter List ID " value = "<?php echo $list_Id; ?>" /> 
              <span class="description">Enter the mailchimp list id you want to use for subscription. To find your List id <a href="http://kb.mailchimp.com/lists/managing-subscribers/find-your-list-id" target="_blank">click here</a></span>
            </td>
          </tr>
        <tr valign="top">
          <th scope="row" class="titledesc">
            <label for="wcmcd_display_fields"><?php _e( 'Display Default Fields', 'wcmcd' ); ?></label>
          </th>
          <td class="forminp forminp-select">
            <label><input <?php checked( $wcmcd_display_fields, "email" );?> type="radio" name="wcmcd_display_fields" value="email"><?php _e( 'Email', 'wcmcd' ); ?></label><br/><br/>
            <label><input <?php checked( $wcmcd_display_fields, "email_name" );?> type="radio" name="wcmcd_display_fields" value="email_name"><?php _e( 'First name and Email', 'wcmcd' ); ?></label><br/><br/>
            <label><input <?php checked( $wcmcd_display_fields, "email_name_all" );?> type="radio" name="wcmcd_display_fields" value="email_name_all"><?php _e( 'First name, Last name and Email', 'wcmcd' ); ?></label>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row" class="titledesc">
            <label for="wcmcd_custom_fields"><?php _e( 'Add Custom Fields', 'wcmcd' ); ?></label>
          </th>
          <td class="forminp custom-field-wrap forminp-text">
            
          <div class="wcmcd-hidden-custom-fields">
            <div class="wcmcd-Text-field-wrapper wcmcd-custom-field-wrap">
              <input class="wcmcd-custom-field-placeholder" style="width:47%; padding:7px 4px; margin-top: -1px;" type="text" name="" placeholder="<?php _e( 'Enter Custom Field Placeholder', 'wcmcd' ); ?>">
            </div>
          </div>


            <div class="wcmcd-custom-fields-wrapper">
              <label style="display:inline-block; margin-top:10px;width:40%;"><?php _e( 'Select Field Type', 'wcmcd' ); ?>
                <select style="width:90%" class="wcmcd-custom-field-type" name="wcmcd-custom-field-type">
                  <?php if( is_array($field_types) && !empty($field_types) ) : 
                    foreach( $field_types as $field_type ) :
                      ?>
                      <option value="<?php echo $field_type; ?>"><?php echo $field_type; ?></option>
                    <?php
                    endforeach;
                    endif; ?>
                </select>
              </label>

              <input class="wcmcd-custom-field-name" style="width:57%; padding:7px 4px; margin-top: -1px; margin-right:10px;" type="text" name="" placeholder="<?php _e( 'Enter Custom Field Label', 'wcmcd' ); ?>">

              <br/><br/>
              <input style type="button" class="wcmcd-add-custom-field button button-primary button-large right" name="" value="<?php _e( 'Add New Field', 'wcmcd' ); ?>">

            </div>

            <div class="wcmcd-custom-fields-list">
              <ul>
                <?php 
                if( isset($wcmcd_campaign_fields['name']) && !empty($wcmcd_campaign_fields['name']) ) :

                  foreach( $wcmcd_campaign_fields['name'] as $key => $wcmcd_campaign_field ) :
                    $placeholder = isset($wcmcd_campaign_fields['placeholder'][$key]) ? $wcmcd_campaign_fields['placeholder'][$key] : '';
                    $validation = isset($wcmcd_campaign_fields['validation'][$key]) ? $wcmcd_campaign_fields['validation'][$key] : '';
                    ?>
                    <li>
                      <div class="wcmcd-remove-btn"></div>
                      <div class="wcmcd-toggle-btn"></div>
                      <h4><?php echo $wcmcd_campaign_field; ?></h4>
                      <!-- <input type="button" class="wcmcd-remove-row button button-primary button-large" value="Remove Field"> -->
                      <input type="hidden" name="wcmcd_campaign_fields[name][]" value="<?php echo $wcmcd_campaign_field; ?>">
                      <input type="hidden" name="wcmcd_campaign_fields[placeholder][]" value="<?php echo $placeholder; ?>">
                      <input type="hidden" name="wcmcd_campaign_fields[validation][]" value="<?php echo $validation; ?>">
                    </li>
                    <?php
                  endforeach;
                endif; ?>
              </ul>
            </div>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row" class="titledesc">
            <label for="wcmcd_enable_terms_condition"><?php _e( 'Enable Terms and Conditions', 'wcmcd' ); ?></label>
          </th>
          <td class="forminp forminp-text">
            <input <?php checked( $wcmcd_enable_terms_condition, "yes" );?> name="wcmcd_enable_terms_condition" id="wcmcd_enable_terms_condition" type="checkbox" value="yes" />
          </td>
        </tr>
        <tr valign="top">
          <th scope="row" class="titledesc">
            <label for="wcmcd_enable_redirect"><?php _e( 'Enable Redirect After Signup', 'wcmcd' ); ?></label>
          </th>
          <td class="forminp forminp-text">
            <input <?php checked( $wcmcd_enable_redirect, "yes" );?> name="wcmcd_enable_redirect" id="wcmcd_enable_redirect" type="checkbox" value= "yes" />
          </td>
        </tr>
        <tr valign="top">
          <th scope="row" class="titledesc">
            <label for="wcmcd_redirect_url"><?php _e( 'Redirect Url', 'wcmcd' ); ?></label>
          </th>
          <td class="forminp forminp-text">
            <input style="width:100%" name="wcmcd_redirect_url" id="wcmcd_redirect_url" type="text" value = "<?php echo $wcmcd_redirect_url; ?>" />
          </td>
        </tr>
        <tr valign="top">
          <th scope="row" class="titledesc">
            <label for="wcmcd_redirect_after_time"><?php _e( 'Redirect After Seconds', 'wcmcd' ); ?></label>
          </th>
          <td class="forminp forminp-text">
            <input name="wcmcd_redirect_after_time" id="wcmcd_redirect_after_time" type="number" value = "<?php echo $wcmcd_redirect_after_time; ?>" />
          </td>
        </tr>
        <tr valign="top">
          <th scope="row" class="titledesc">
            <label for="wcmcd_terms_condition_text"><?php _e( 'Terms and Condition Text', 'wcmcd' ); ?></label>
          </th>
          <td class="forminp forminp-text">
            <textarea name="wcmcd_terms_condition_text" rows="6" cols="60"><?php echo $wcmcd_terms_condition_text; ?></textarea>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row" class="titledesc">
            <label for="wcmcd_terms_condition_error_msg"><?php _e( 'Terms and Condition Validation Message', 'wcmcd' ); ?></label>
          </th>
          <td class="forminp forminp-text">
            <input style="width:100%" type="text" name="wcmcd_terms_condition_error_msg" id="wcmcd_terms_condition_error_msg" value="<?php echo $wcmcd_terms_condition_error_msg; ?>">
          </td>
        </tr>
      </table>
    </div>
    <?php
  }

  public function coupon_meta_box_markup($post) {
    wp_nonce_field(basename(__FILE__), "meta-box-nonce");
    
    $discount_types = wc_get_coupon_types();

    $product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );

    $selected_discount_type = get_post_meta($post->ID, 'wcmcd_dis_type', true);
    $prefix = get_post_meta($post->ID, 'wcmcd_prefix', true);
    $code_length = get_post_meta($post->ID, 'wcmcd_code_length', true);
    $amount = get_post_meta($post->ID, 'wcmcd_amount', true);
    $shipping = get_post_meta($post->ID, 'wcmcd_shipping', true);
    $sale_ = get_post_meta($post->ID, 'wcmcd_sale', true);
    $product_ids = get_post_meta($post->ID, 'wcmcd_products', true);
    $exclude_product_ids = get_post_meta($post->ID, 'wcmcd_exclude_products', true);
    $category = get_post_meta($post->ID, 'wcmcd_categories', false);
    $excluded_category = get_post_meta($post->ID, 'wcmcd_exclude_categories', false);
    $days = get_post_meta($post->ID, 'wcmcd_days', true);
    $date_format = get_post_meta($post->ID, 'wcmcd_date_format', true);
    $min_purchase = get_post_meta($post->ID, 'wcmcd_min_purchase', true);
    $max_purchase = get_post_meta($post->ID, 'wcmcd_max_purchase', true);
    
    $video_type = get_post_meta($post->ID, 'wcmcd_video_type', true);
    $email_sub = get_post_meta($post->ID, 'wcmcd_email_sub', true);
    $wcmcd_email_value = get_post_meta($post->ID, 'wcmcd_email', true);

    if ($wcmcd_email_value == '') {
      $wcmcd_email_value = '<p>Hi There,</p><p>Thanks for signing up for our Newsletter. As a registration bonus we present you with a 10% of discount on all your orders. The coupon code to redeem the discount is <h3>{COUPONCODE}</h3></p><p>The coupon will expire on {COUPONEXPIRY} so make sure to get the benefits while you still have time.</p><p>Regards</p>';
    }

    $args = array(
      'post_type'  => 'product',
      'posts_per_page' => -1,
      'status'      => 'publish',
      'orderby' => 'title',
      'order' => 'ASC'              
    );

    $loop = new WP_Query( $args );

     ?>
      <div>
        <table class="form-table">
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_dis_type"><?php _e( 'Discount Type', 'wcmcd' ); ?></label>
            </th>
            <td class="forminp forminp-select">
              <select name="wcmcd_dis_type" id="wcmcd_dis_type">
                <?php
                  foreach ( $discount_types as $key => $discount_type ) : ?>
                    <option <?php selected( $selected_discount_type, $key ); ?> value="<?php echo $key; ?>"><?php echo $discount_type; ?></option>
                  <?php endforeach;
                ?>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_prefix"><?php _e( 'Coupon prefix', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="Enter a coupon prefix which would be added before the actual generated coupon code. Leave empty for no prefix."></span>
            </th>
            <td class="forminp forminp-text">
              <input name="wcmcd_prefix" id="wcmcd_prefix" type="text" value = "<?php echo $prefix; ?>" />
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_code_length"><?php _e( 'Coupon code length', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="Enter a length for the coupon code. Note: the prefix is not counted in coupon code length."></span>
            </th>
            <td class="forminp forminp-number">
              <input name="wcmcd_code_length" id="wcmcd_code_length" type="number" value = "<?php echo $code_length; ?>" />
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_amount"><?php _e( 'Discount Amount', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="Enter a coupon discount amount"></span>
            </th>
            <td class="forminp forminp-text">
              <input name="wcmcd_amount" id="wcmcd_amount" type="text" value = "<?php echo $amount; ?>" />
            </td>
          </tr>
          <tr valign="top" class="">
            <th scope="row" class="titledesc"><?php _e( 'Allow free shipping', 'wcmcd' ); ?></th>
            <td class="forminp forminp-checkbox">
              <fieldset>
                <legend class="screen-reader-text"><span><?php _e( 'Allow free shipping', 'wcmcd' ); ?></span></legend>
                <label for="wcmcd_shipping">
                  <input name="wcmcd_shipping" id="wcmcd_shipping" type="checkbox" value = "<?php echo ($shipping == 1 ? '1' : '0') ?>" <?php echo ($shipping == 1 ? 'checked' : '') ?> />  Check this box if the coupon grants free shipping. The <a href="http://localhost/demo_a/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;section=WC_Shipping_Free_Shipping">free shipping method</a> must be enabled with the "must use coupon" setting.             
                </label>
              </fieldset>
            </td>
          </tr>
          <tr valign="top" class="">
            <th scope="row" class="titledesc"><?php _e( 'Exclude on sale items', 'wcmcd' ); ?></th>
            <td class="forminp forminp-checkbox">
              <fieldset>
                <legend class="screen-reader-text"><span><?php _e( 'Exclude on sale items', 'wcmcd' ); ?></span></legend>
                <label for="wcmcd_sale">
                  <input name="wcmcd_sale" id="wcmcd_sale" type="checkbox" value = "<?php echo ($sale_ == 1 ? '1' : '0') ?>" <?php echo ($sale_ == 1 ? 'checked' : '') ?> /> <?php _e('Check this box if the coupon should not apply to items on sale. Per-item coupons will only work if the item is not on sale. Per-cart coupons will only work if there are no sale items in the cart.', 'wcmcd'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
          <tr valign="top" class="search-products">
            <th><?php _e( 'Products', 'wcmcd' ); ?></th>
            <td class="forminp forminp-multiselect">
              <select name="wcmcd_products[]" id="wcmcd_products" style="width:300px" class="wcmcd_products" multiple="multiple" >
                <?php
                  while ( $loop->have_posts() ) : $loop->the_post();
                    global $product;
                    if (in_array($product->id, $product_ids)) {
                      echo '<option value="'.$product->id.'" selected> '.$product->name.'</option>';
                    }else{
                      echo '<option value="'.$product->id.'" > '.$product->name.'</option>';
                    }
                  endwhile;
                  wp_reset_postdata();
                ?>
              </select>              
            </td>
          </tr>

          <tr valign="top" class="search-products">
            <th><?php _e( 'Exclude Products', 'wcmcd' ); ?></th>
            <td class="forminp forminp-multiselect">
              <select name="wcmcd_exclude_products[]" id="wcmcd_exclude_products" style="width:300px" class="wcmcd_products" multiple="multiple" >
                <?php
                  while ( $loop->have_posts() ) : $loop->the_post();
                    global $product;
                    if (in_array($product->id, $exclude_product_ids)) {
                      echo '<option value="'.$product->id.'" selected> '.$product->name.'</option>';
                    }else{
                      echo '<option value="'.$product->id.'" > '.$product->name.'</option>';
                    }
                  endwhile;
                  wp_reset_postdata();
                ?>
              </select>
            </td>
          </tr>

          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_categories"><?php _e( 'Categories', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="A product must be in this category for the coupon to remain valid or, for &quot;Product Discounts&quot;, products in these categories will be discounted."></span>
            </th>
            <td class="forminp forminp-multiselect">
              <select name="wcmcd_categories[]" id="wcmcd_categories" style="width:300px" class="wcmcd_cats" multiple="multiple" >
              <?php
                foreach ($product_categories as $key => $product_cats ) {
                  if ( in_array($product_cats->term_taxonomy_id, $category[0]) ) {
                    echo '<option value="'.$product_cats->term_taxonomy_id.'" selected > '.$product_cats->name.'</option>';
                  }
                  else {
                    echo '<option value="'.$product_cats->term_taxonomy_id.'" > '.$product_cats->name.'</option>';
                  }
                }
              ?>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_exclude_categories"><?php _e( 'Exclude Categories', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="Product must not be in this category for the coupon to remain valid or, for &quot;Product Discounts&quot;, products in these categories will not be discounted."></span>
            </th>
            <td class="forminp forminp-multiselect">
              <select name="wcmcd_exclude_categories[]" id="wcmcd_exclude_categories" style="width:300px" class="wcmcd_cats" multiple="multiple" >
                <?php
                  foreach ($product_categories as $key => $product_cats ) {
                    if (in_array($product_cats->term_taxonomy_id, $excluded_category[0])) {
                      echo '<option value="'.$product_cats->term_taxonomy_id.'" selected > '.$product_cats->name.'</option>';
                    }else{
                      echo '<option value="'.$product_cats->term_taxonomy_id.'" > '.$product_cats->name.'</option>';
                    }
                  }
                ?>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_days"><?php _e( 'Coupon Validity (in days)', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="Enter number of days the coupon will active from the date of registration of the user. Leave blank for no limit."></span>
            </th>
            <td class="forminp forminp-number">
              <input name="wcmcd_days" id="wcmcd_days" type="number" style="width:100px" value = "<?php echo $days; ?>" />
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_date_format"><?php _e( 'Coupon expiry date format', 'wcmcd' ); ?></label>
            </th>
            <td class="forminp forminp-text">
              <input name="wcmcd_date_format" id="wcmcd_date_format" type="text" style="width:100px" placeholder="jS F Y" value = "<?php echo $date_format; ?>" /> 
              <span class="description">Enter the date format for the coupon expiry date which would be mailed to the user. <a href="http://php.net/manual/en/function.date.php" target="_blank">Click here</a> to know about the available types</span>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_min_purchase"><?php _e( 'Minimum Purchase', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="Minimum purchase subtotal in order to be able to use the coupon. Leave blank for no limit"></span>
            </th>
            <td class="forminp forminp-text">
              <input name="wcmcd_min_purchase" id="wcmcd_min_purchase" type="text" value = "<?php echo $min_purchase; ?>" />
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_max_purchase"><?php _e( 'Maximum Purchase', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="Maximum purchase subtotal in order to be able to use the coupon. Leave blank for no limit"></span>
            </th>
            <td class="forminp forminp-text">
              <input name="wcmcd_max_purchase" id="wcmcd_max_purchase" type="text" value = "<?php echo $max_purchase; ?>" />
            </td>
          </tr>
          
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_email_sub"><?php _e( 'Email Subject', 'wcmcd' ); ?></label>
              <span class="woocommerce-help-tip" data-tip="This will be email subject for the emails that will be sent to the users."></span>
            </th>
            <td class="forminp forminp-text">
              <input style="width: 100%" type = "text" name="wcmcd_email_sub" id="wcmcd_email_sub" value = "<?php echo $email_sub ?>" />
            </td>
          </tr>
          <tr valign="top">
            <th scope="row" class="titledesc">
              <label for="wcmcd_email"><?php _e( 'Email Body', 'wcmcd' ); ?></label>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title( 'wcmcd_wpeditor' ) ?>">
              <?php wp_editor( $wcmcd_email_value, 'wcmcd_email' ); ?>
            </td>
          </tr>
        </table>
      </div>
     <?php  
   }


  public function save_campaign_fields_meta_box($post_id) {

    if ( !isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)) )
      return $post_id;

    if( !current_user_can("edit_post", $post_id) )
      return $post_id;

    if( defined("DOING_AUTOSAVE") && DOING_AUTOSAVE )
      return $post_id;

    $list_Id = isset( $_POST["wcmcd_list_id"] ) ? sanitize_text_field($_POST["wcmcd_list_id"]) : '';

    $default_fields = isset($_POST["wcmcd_display_fields"]) ? sanitize_text_field($_POST["wcmcd_display_fields"]) : "";

    $enable_terms_condition = isset($_POST["wcmcd_enable_terms_condition"]) ? sanitize_text_field($_POST["wcmcd_enable_terms_condition"]) : "";

    $campaign_custom_fields = isset($_POST["wcmcd_campaign_fields"]) ? $_POST["wcmcd_campaign_fields"] : "";

    $enable_redirect = isset($_POST["wcmcd_enable_redirect"]) ? sanitize_text_field($_POST["wcmcd_enable_redirect"]) : "";

    $redirect_url = isset($_POST["wcmcd_redirect_url"]) ? sanitize_text_field($_POST["wcmcd_redirect_url"]) : "";

    $redirect_after_time = isset($_POST["wcmcd_redirect_after_time"]) ? sanitize_text_field($_POST["wcmcd_redirect_after_time"]) : "";

    $terms_condition_text = isset($_POST["wcmcd_terms_condition_text"]) ? sanitize_text_field($_POST["wcmcd_terms_condition_text"]) : "";

    $terms_validation_message = isset($_POST["wcmcd_terms_condition_error_msg"]) ? sanitize_text_field($_POST["wcmcd_terms_condition_error_msg"]) : "";

    update_post_meta($post_id, "wcmcd_list_id", $list_Id);
    update_post_meta($post_id, "wcmcd_display_fields", $default_fields);
    update_post_meta($post_id, "wcmcd_campaign_fields", $campaign_custom_fields);
    update_post_meta($post_id, "wcmcd_enable_terms_condition", $enable_terms_condition);
    update_post_meta($post_id, "wcmcd_enable_redirect", $enable_redirect);
    update_post_meta($post_id, "wcmcd_redirect_url", $redirect_url);
    update_post_meta($post_id, "wcmcd_redirect_after_time", $redirect_after_time);
    update_post_meta($post_id, "wcmcd_terms_condition_text", $terms_condition_text);
    update_post_meta($post_id, "wcmcd_terms_condition_error_msg", $terms_validation_message);


      
   }

  public function save_coupon_meta_box($post_id) {
    if( !isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)) )
      return $post_id;

    if( !current_user_can("edit_post", $post_id) )
      return $post_id;

    if( defined("DOING_AUTOSAVE") && DOING_AUTOSAVE )
      return $post_id;

     
    $dis_type = isset( $_POST["wcmcd_dis_type"] ) ? sanitize_text_field($_POST["wcmcd_dis_type"]) : '';

    $prefix = isset( $_POST["wcmcd_prefix"] ) ? sanitize_text_field($_POST["wcmcd_prefix"]) : '';

    $code_length = isset( $_POST["wcmcd_code_length"] ) ? sanitize_text_field($_POST["wcmcd_code_length"]) : '';

    $amount = isset( $_POST["wcmcd_amount"] ) ? sanitize_text_field($_POST["wcmcd_amount"]) : '';

    $shipping = isset( $_POST["wcmcd_shipping"] ) ? sanitize_text_field($_POST["wcmcd_shipping"]) : 0;
    
    $sale = isset( $_POST["wcmcd_sale"] ) ? sanitize_text_field($_POST["wcmcd_sale"]) : 0;
    
    $product_ids = isset( $_POST["wcmcd_products"] ) ? $_POST["wcmcd_products"] : '';
    
    $exclude_product_ids = isset( $_POST["wcmcd_exclude_products"] ) ? $_POST["wcmcd_exclude_products"] : '';

    $category = isset( $_POST["wcmcd_categories"] ) ? $_POST["wcmcd_categories"] : '';

    $excluded_category = isset( $_POST["wcmcd_exclude_categories"] ) ? $_POST["wcmcd_exclude_categories"] : '';

    $days = isset( $_POST["wcmcd_days"] ) ? sanitize_text_field($_POST["wcmcd_days"]) : '';

    $date_format = isset( $_POST["wcmcd_date_format"] ) ? sanitize_text_field($_POST["wcmcd_date_format"]) : '';

    $min_purchase = isset( $_POST["wcmcd_min_purchase"] ) ? sanitize_text_field($_POST["wcmcd_min_purchase"]) : '';

    $max_purchase = isset( $_POST["wcmcd_max_purchase"] ) ? sanitize_text_field($_POST["wcmcd_max_purchase"]) : '';

    $email_sub = isset( $_POST["wcmcd_email_sub"] ) ? sanitize_text_field($_POST["wcmcd_email_sub"]) : '';

    $wcmcd_email_value = isset( $_POST["wcmcd_email"] ) ? sanitize_text_field($_POST["wcmcd_email"]) : '';

    update_post_meta( $post_id, "wcmcd_dis_type", $dis_type );
    update_post_meta( $post_id, "wcmcd_prefix", $prefix );
    update_post_meta( $post_id, "wcmcd_code_length", $code_length);
    update_post_meta($post_id, "wcmcd_amount", $amount);
    update_post_meta($post_id, "wcmcd_shipping", $shipping);
    update_post_meta($post_id, "wcmcd_sale", $sale);
    update_post_meta($post_id, "wcmcd_products", $product_ids);
    update_post_meta($post_id, "wcmcd_exclude_products", $exclude_product_ids);
    update_post_meta($post_id, "wcmcd_categories", $category);
    update_post_meta($post_id, "wcmcd_exclude_categories", $excluded_category);
    update_post_meta($post_id, "wcmcd_days", $days);
    update_post_meta($post_id, "wcmcd_date_format", $date_format);
    update_post_meta($post_id, "wcmcd_min_purchase", $min_purchase);
    update_post_meta($post_id, "wcmcd_max_purchase", $max_purchase);
    update_post_meta($post_id, "wcmcd_email_sub", $email_sub);
    update_post_meta($post_id, "wcmcd_email", $wcmcd_email_value);
  }


  public function campaign_shortcode_attr($atts) {
    extract(shortcode_atts(array(
      'id' => false,
    ), $atts));
    return $this->show_generated_form($id);
  }

  public function show_generated_form($post_id = false) {
    $fields = get_post_meta($post_id, 'wcmcd_display_fields', true );
    $enable_terms_condition = get_post_meta($post_id, 'wcmcd_enable_terms_condition', true );
    $terms_condition_error_msg = get_post_meta($post_id, 'wcmcd_terms_condition_error_msg', true );
    $custom_fields = get_post_meta($post_id, 'wcmcd_campaign_fields', true );

    // print_r($custom_fields);

    $form = '<div class="wcmcd-form-wrapper wcmcd" >';
    $form .= '<div class="wcmd-loading"></div>';
    $form .= '<div class="wcmd_content">';
    $form .='<div class="wcmd_text">';
    $form .= '<form class="wcmcd-form wcmcd_' . $fields . '">';
    $form .= '<div class="validation-wrap"><span class="wcmcd-validation"></span></div><div class="wcmcd-fields">';
      
      if( $fields == 'email_name' || $fields == 'email_name_all' )
        $form .= '<input type="text" placeholder="'. __('Enter first name', 'wcmcd' ) .'" name="wcmcd_fname" class="wcmcd_fname">';

      if( $fields == 'email_name_all' )
        $form .= '<input type="text" placeholder="'. __('Enter last name', 'wcmcd' ) .'" name="wcmcd_lname" class="wcmcd_lname">';
        
      $form .='<input type="text" placeholder="'. __('Enter your email', 'wcmcd' ) .'" name="wcmcd_email" class="wcmcd_email">';

      if( isset($custom_fields['name']) ) {
        foreach( $custom_fields['name'] as $key => $custom_field ) {
          $field_name = trim($custom_field);
          $field_name = str_replace(' ', '_', $custom_field);
          $field_name = strtolower($field_name);

          $form .='<input type="text" placeholder="'. __('Enter your '.$custom_field.'', 'wcmcd' ) .'" name="'.$field_name.'" class="wcmcd_custom_fields">';
        }
      }

      //checkbox for terms and conditions
      if( $enable_terms_condition == 'yes' ) :

        $term_condition_text = get_post_meta($post_id, 'wcmcd_terms_condition_text', true );

        $uniq_id = uniqid();

        $form .= '<div class="wcmcd-checkbox-wrap">';
        $form .= '<input type="checkbox" id='.$uniq_id.' class="wcmcd-terms-conditions" name="wcmcd_terms_condition">';
        $form .= '<label for='.$uniq_id.'>'.$term_condition_text.'</label>';
        $form .= '</div>';
      endif;

      $form .= '</div><div class="wcmcd-btn-cont" style="">';
      $form .= '<div class="wcmcd-data-fields" style="display:none;"><span class="wcmcd-terms-error-msg">'.$terms_condition_error_msg.'</span></div>';
      $form .= '<button data-tems-condition='.$enable_terms_condition.' data-post-id='.$post_id.' class="wcmcd-btn" style="">Subscribe</button>';
      $form .= '</div><div class="wcmcd-clear"></div></form>';
      $form .= '<div class="wcmcd-clear"></div>';
      $form .='</div></div></div>';

    return $form;
        
  }


  public function wcmcd_enque_admin_scripts() {
    wp_enqueue_script('wcmcd-admin-script', plugins_url( 'assets/js/wcmcd-admin.js', WCMCD_FILE ), array( 'jquery' ), '1.0.0', true);

    wp_enqueue_style('wcmcd-admin-style', plugins_url( 'assets/css/wcmcd-admin.css', WCMCD_FILE ), '1.0.0', true);

    wp_enqueue_style( 'select2-style', plugins_url( 'assets/css/select2.css', WCMCD_FILE ) );

    wp_enqueue_script( 'wcmcd-enhanced-select', plugins_url( 'assets/js/select2.min.js', WCMCD_FILE ) , array( 'jquery' ), '1.0.0', true );

    if( is_admin() && isset($_GET['tab']) && $_GET['tab'] == 'mailchimp_campaign_discount' ) {
      
      wp_localize_script( 'wcmcd-enhanced-select', 'wcmcd_enhanced_select_params', array(
          'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
          'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
          'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
          'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
          'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
          'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
          'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
          'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
          'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
          'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
          'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
          'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
        ) ); 
    } 
  }

  public function wcmcd_lang_func( $atts, $content = "" ) {
    $a = shortcode_atts( array(
      'lang' => ''
    ), $atts );

    if( !function_exists('icl_object_id') )
      $current_lang = substr(get_bloginfo('language'), 0, 2);
    else
      $current_lang = ICL_LANGUAGE_CODE;

    if( !empty( $a['lang'] ) && $current_lang == $a['lang'] )
      return $content;
    else
      return;
  }

  /**
  *
  * Add necessary js and css files for the popup
  *
  */

  public function wcmcd_enque_scripts_front() {
    
    
    wp_localize_script('wistia-video-control', 'wcmcd', array(
      'double_optin'    => get_option('wcmcd_double_optin'),
      'effect'          => get_option('wcmcd_popup_animation'),
      'width'           => get_option( 'wcmcd_popup_width' ),
      'success'         => do_shortcode( get_option( 'wcmcd_success_msg' ) ),
      'valid_email'     => __( 'Please enter a valid email id' ),
      'enable_terms_condition' => get_option('wcmcd_terms_condition'),
      'terms_condition_error'  => get_option('wcmcd_terms_condition_error'),
      'ajax_url'        => admin_url( 'admin-ajax.php' ),
      'signup_redirect' => get_option('wcmcd_signup_redirect'),
      'redirect_url'    => get_option('wcmcd_redirect_url'),
      'redirect_timeout' => get_option('wcmcd_redirect_timeout'),
    ));
  }

  public function wcmcd_enque_scripts() {

    wp_enqueue_style( 'select2-style', plugins_url( 'assets/css/select2.css', WCMCD_FILE ) );
    wp_enqueue_script( 'wcmcd-enhanced-select', plugins_url( 'assets/js/select2.min.js', WCMCD_FILE ) , array( 'jquery' ), '1.0.0', true );

    if( get_option( 'wcmcd_disable_discount') != 'yes' && get_option( 'wcmcd_double_optin') == 'yes' && isset( $_GET['mc_discount'] ) && isset( $_POST['type'] ) && $_POST['type'] == 'subscribe' ) {
    
      if( isset($_POST['data']['merges']['wcmcdLANG']) && !empty($_POST['data']['merges']['wcmcdLANG']) ) {
        $current_lang = $_POST['data']['merges']['wcmcdLANG'];
      }
      else {
        if( !function_exists('icl_object_id') )
          $current_lang = substr(get_bloginfo('language'), 0, 2);
        else
          $current_lang = ICL_LANGUAGE_CODE;
      }
      $this->wcmcd_send_coupons( $_POST['data']['email'], $current_lang, '' );
    }
    
    $overlay_color = get_option( 'wcmcd_pop_overlay' );
        list($r, $g, $b) = sscanf($overlay_color, "#%02x%02x%02x");
    $rgb_color = 'rgba('.$r.','.$g.','.$b.','.get_option( 'wcmcd_overlay_opacity' ).')';
    $height = get_option( 'wcmcd_popup_height' ) == 0 ? 'auto' : get_option( 'wcmcd_popup_height' ) . 'px';
    $width = get_option( 'wcmcd_popup_width' ) == 0 ? 'auto' : get_option( 'wcmcd_popup_width' ) . 'px';
    $bg = get_option( 'wcmcd_pop_bg' ) ==  '' ? get_option('wcmcd_pop_bgcolor') : 'url(' . get_option( 'wcmcd_pop_bg' ) . ')';
    $top_pixel = get_option('wcmcd_content_top') . 'px';
    $left_pixel = get_option('wcmcd_content_left') . 'px';
    $form_width = get_option( 'wcmcd_form_width' ) == 0 ? 'auto' : get_option( 'wcmcd_form_width' ) . 'px';
    $close_color = get_option( 'wcmcd_close_color' );
    
    if( $close_color == '' )
      $close_color = '#fff';
      
      $css  = '#wcmcd_modal{ min-height:' . $height . ';background:' . $bg . ';max-width:' . $width . ';}';
      $css .= '#wcmcd_modal .mfp-close{ color:' .$close_color .' !important; }';
      $css .= '#wcmcd-form{float:' . get_option( 'wcmcd_form_alignment' ) . '; max-width:' . $form_width . ';}';
      $css .= '.wcmcd-title{ color:' . get_option( 'wcmcd_header_color' ) . ';}';
      $css .= '.wcmcd_text{ top:' . $top_pixel . ';left:' . $left_pixel . ';}';
      $css .= '.wcmcd-btn{ background:' . get_option( 'wcmcd_btn_color' ) . ';color:' . get_option( 'wcmcd_btn_txt_color' ) . ';}';
      $css .= '.wcmcd-btn:hover{ background:' . get_option( 'wcmcd_btn_hover' ) . ';}';
      $css .= '#wcmcd-form label{ color:' . get_option( 'wcmcd_label_color' ) . ';}';
      $css .= '#wcmcd-form .wcmcd-confirm{ background:' . get_option( 'wcmcd_checkbox_color' ) . ';}';
      $css .= '.wcmcd-form .wcmcd-validation.success{ background: '.get_option('wcmcd_success_bg_color').'; color: '.get_option('wcmcd_success_txt_color').'; border: 1px solid '.get_option('wcmcd_success_bg_color').'; }';
      $css .= '.wcmcd-form .wcmcd-validation.error{ background: '.get_option('wcmcd_error_bg_color').'; color: '.get_option('wcmcd_error_txt_color').'; border: 1px solid '.get_option('wcmcd_error_bg_color').' }';
      $css  .= '#wcmcd_modal .wcmcd-checkbox-wrap * { color:' . get_option('wcmcd_terms_text_color') . ';}';

      //Add custombox css
      wp_enqueue_style( 'wcmcd-custombox-stylesheet', plugins_url( 'assets/css/magnific-popup.css', WCMCD_FILE ));

      //Add our customized css
      wp_add_inline_style( 'wcmcd-custombox-stylesheet', $css );

      //Custombox js script
      wp_enqueue_script( 'wcmcd-custombox', plugins_url( 'assets/js/jquery.magnific-popup.min.js', WCMCD_FILE ) , array( 'jquery' ), '1.0.0', true);
      
      wp_enqueue_script('wcmcd-custom-script', plugins_url( 'assets/js/wcmcd-custom.js', WCMCD_FILE ), array( 'jquery', 'wcmcd-custombox' ), '1.0.0', true );

      wp_localize_script('wcmcd-custom-script', 'wcmcd', array(
        'double_optin'    => get_option('wcmcd_double_optin'),
        'effect'          => get_option('wcmcd_popup_animation'),
        'width'           => get_option( 'wcmcd_popup_width' ),
        'overlayColor'    => $rgb_color,
        'delay'           => get_option( 'wcmcd_dis_seconds'),
        'success'         => do_shortcode( get_option( 'wcmcd_success_msg' ) ),
        'cookie_length'   => get_option( 'wcmcd_cookie_length' ),
        'wcmcd_popup'      => get_option( 'wcmcd_disable_popup' ),
        'valid_email'     => __( 'Please enter a valid email id' ),
        'enable_terms_condition' => get_option('wcmcd_terms_condition'),
        'terms_condition_error'  => get_option('wcmcd_terms_condition_error'),
        'ajax_url'        => admin_url( 'admin-ajax.php' ),
        'exit_intent'     => get_option( 'wcmcd_exit_intent' ),
        'hinge'           => get_option( 'wcmcd_hinge' ),
        'overlay_click'   => get_option( 'wcmcd_overlay_click' ),
        'btn_trigger'     => get_option( 'wcmcd_btn_trigger' ),
        'only_btn'        => get_option( 'wcmcd_only_btn' ),
        'close_time'      => get_option( 'wcmcd_close_seconds' ),
        'wcmcd_home'       => get_option( 'wcmcd_home' ),
        'disable_popup_on_mobile' => get_option('wcmcd_disable_mobile_popup'),
        'is_home'         => is_front_page(),
        'signup_redirect' => get_option('wcmcd_signup_redirect'),
        'redirect_url'    => get_option('wcmcd_redirect_url'),
        'redirect_timeout' => get_option('wcmcd_redirect_timeout'),
      ));
  }

  /**
  *
  * Check if woocommerce is installed and activated and if not
  * activated then deactivate woocommerce mailchimp campaign discount.
  *
  */
  public function check_required_plugins() {
    //Check if WooCommerce is installed and activated
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) { ?>
    <div id="message" class="error">
      <p>WooCommerce Mailchimp Campaign Discount requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="<?php echo admin_url('/plugin-install.php?tab=search&amp;type=term&amp;s=WooCommerce'); ?>" target="">WooCommerce</a> first.</p>
      </div>

      <?php
        deactivate_plugins( '/woocommerce-mailchimp-campaign-discount/woocommerce-mailchimp-campaign-discount.php' );
    }
  }

  /**
  * Add new link for the settings under plugin links
  *
  * @param array $links an array of existing links.
  * @return array of links  along with mailchimp campaign discount settings link.
  *
  */
  public function wcmcd_settings_link($links) {
    $settings_link = '<a href="'.admin_url('admin.php?page=wc-settings&tab=mailchimp_campaign_discount').'">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
  }

  /**
  * Add new admin setting page for woocommerce mailchimp campaign discount settings.
  *
  * @param array $settings an array of existing setting pages.
  * @return array of setting pages along with mailchimp campaign discount settings page.
  *
  */
  public function wcmcd_settings_class( $settings ) {
    $settings[] = include 'class-wc-settings-mailchimp-campaign-discount.php';
    return $settings;
  }


  /**
  * Output the html for the popup.
  *
  * @param void
  * @return outputs the html for the popup
  *
  */
  public function wcmcd_display_popup() {
    $wcmcd_title = do_shortcode( get_option( 'wcmcd_pop_header') );
    $fields = get_option( 'wcmcd_fields' );
    $pop_text = wpautop( stripslashes( get_option('wcmcd_popup_text') ) );
    $pop_text = do_shortcode( $pop_text );
  ?>
  <div id="wcmcd_modal" class="mfp-with-anim mfp-hide">
    <?php if( $wcmcd_title != '' ) echo '<h4 class="wcmcd-title">' . $wcmcd_title . '</h4>'; ?>
    <div class="wcmcd_content">
      <div class="wcmcd-loading"></div>
      <div class="wcmcd_text">
        <?php
        $form = '<form class="wcmcd-form wcmcd_' . $fields . '">';
        $form .= '<div class="wcmcd-fields">';
        
        if( $fields == 'email_name' || $fields == 'email_name_all' )
          $form .= '<input type="text" placeholder="'. __('Enter first name', 'wcmcd' ) .'" name="wcmcd_fname" class="wcmcd_fname">';
        
        if( $fields == 'email_name_all' )
          $form .= '<input type="text" placeholder="'. __('Enter last name', 'wcmcd' ) .'" name="wcmcd_lname" class="wcmcd_lname">';
        
        $form .='<input type="text" placeholder="'. __('Enter your email', 'wcmcd' ) .'" name="wcmcd_email" class="wcmcd_email">';
        $form .= '</div>';

        //checkbox for terms and conditions
        if( get_option('wcmcd_terms_condition') == 'yes' ) :
          $term_condition_text = !empty(get_option('wcmcd_terms_condition_text')) ? get_option('wcmcd_terms_condition_text') : '';
          $uniq_id = uniqid();

          $form .= '<div class="wcmcd-checkbox-wrap">';
          $form .= '<input type="checkbox" id="'.$uniq_id.'" class="wcmcd-terms-conditions" name="wcmcd_terms_condition">';
          $form .= '<label for='.$uniq_id.'>'.$term_condition_text.'</label>';
          $form .= '</div>';
        endif;

        $form .= '<div class="wcmcd-btn-cont">';
        $form .= '<button data-post-id = "" class="wcmcd-btn">' . get_option( 'wcmcd_btn_text' ) . '</button>';
        $form .= '</div><div class="wcmcd-clear"></div><div class="wcmcd-validation"></div></form>';
        $form .= '<div class="wcmcd-clear"></div>';
        
        //Replace the from code and add the form html.
        echo str_replace( '{wcmcd_FORM}', $form, $pop_text );

        ?>
      </div>
    </div>
  </div>
  <?php
  }

  /**
  * Hook our function to send the emails to users when they signup for newsletter
  *
  * @param string $email Email Id for the newly registered user.
  *
  */
  public function wcmcd_send_coupons( $email, $language, $post_id ) {
    global $woocommerce;

    $code_length = get_post_meta($post_id, 'wcmcd_code_length', true );
    $emails = get_option( 'wcmcd_mails', array() );

    //If user is already subscribed in past and trying to register again after unsubscribe.
    // if( is_array( $emails ) && in_array( $email, $emails ) )
    //   return;

    if( $code_length == '' )
      $code_length = 12;

    $prefix = get_post_meta($post_id, 'wcmcd_prefix', true);
    $code = $prefix . strtoupper( substr( str_shuffle( md5( time() ) ), 0, $code_length ) );

    $type = get_post_meta($post_id, 'wcmcd_dis_type', true);
    $amount = get_post_meta($post_id, 'wcmcd_amount', true);
    $product_ids = get_post_meta($post_id, 'wcmcd_products', true);
    $allowed_products = '';
    $excluded_products = '';
          
    if ( is_array( $product_ids ) ) {
      foreach ( $product_ids as $product_id ) {
        $product = wc_get_product( $product_id );
        $allowed_products .= '<a href="'.$product->get_permalink().'">'.$product->get_title().'</a>,';
      }
      $allowed_products = rtrim( $allowed_products, ',' );
      $product_ids = implode( ',', $product_ids );
    }

    $exclude_product_ids = get_post_meta($post_id, 'wcmcd_exclude_products', true);
    if ( is_array( $exclude_product_ids ) ) {
      foreach ( $exclude_product_ids as $product_id ) {
        $product = wc_get_product( $product_id );
        $excluded_products .= '<a href="'.$product->get_permalink().'">'.$product->get_title().'</a>,';
      }
      
      $excluded_products = rtrim( $excluded_products, ',' );
      $exclude_product_ids = implode( ',', $exclude_product_ids );
    }

    $product_categories = get_post_meta($post_id, 'wcmcd_categories', true);
    $allowed_cats = '';
    $excluded_cats = '';
    if ( is_array( $product_categories ) ) {
      foreach ( $product_categories as $cat_id ) {
        $cat = get_term_by( 'id', $cat_id, 'product_cat' );
        $allowed_cats .= '<a href="'.get_term_link( $cat->slug, 'product_cat' ).'">'.$cat->name.'</a>,';
      }
      $allowed_cats = rtrim( $allowed_cats, ',' );
    }
    else
      $product_categories = array();

    $exclude_product_categories = get_post_meta($post_id, 'wcmcd_exclude_categories', true);
    if ( is_array( $exclude_product_categories ) ) {
      foreach ( $exclude_product_categories as $cat_id ) {
        $cat = get_term_by( 'id', $cat_id, 'product_cat' );
        $excluded_cats .= '<a href="'.get_term_link( $cat->slug, 'product_cat' ).'">'.$cat->name.'</a>,';
      }
      $excluded_cats = rtrim( $excluded_cats, ',' );
    }
    else
      $exclude_product_categories = array();

    $days = get_post_meta($post_id, 'wcmcd_days', true);
    $date = '';
    $expire = '';
    $format = get_post_meta($post_id, 'wcmcd_date_format', true ) == '' ? 'jS F Y' : get_post_meta($post_id, 'wcmcd_date_format', true );
      
    if ( $days ) {
      $date = date( 'Y-m-d', strtotime( '+'.$days.' days' ) );
      $expire = date_i18n( $format, strtotime( '+'.$days.' days' ) );
    }

    $free_shipping = get_post_meta($post_id, 'wcmcd_shipping', true );

    $exclude_sale_items = get_post_meta($post_id, 'wcmcd_sale', true );

    if( $free_shipping == 1 )
      $free_shipping = 'yes';

    if( $exclude_sale_items == 1 )
      $exclude_sale_items = 'yes';

    
    $minimum_amount = get_post_meta($post_id, 'wcmcd_min_purchase', true );
    $maximum_amount = get_post_meta($post_id, 'wcmcd_max_purchase', true );

    $customer_email = '';
    
    if ( get_option( 'wcmcd_restrict' ) == 'yes' )
      $customer_email = $email;

      //Add a new coupon when user registers
      $coupon = array(
        'post_title'    => $code,
        'post_content'  => '',
        'post_status'   => 'publish',
        'post_author'   => 1,
        'post_type'     => 'shop_coupon'
      );
      
      $coupon_id = wp_insert_post( $coupon );


      //Add coupon meta data
      update_post_meta( $coupon_id, 'discount_type', $type );
      update_post_meta( $coupon_id, 'coupon_amount', $amount );
      update_post_meta( $coupon_id, 'individual_use', 'yes' );
      update_post_meta( $coupon_id, 'product_ids', $product_ids );
      update_post_meta( $coupon_id, 'exclude_product_ids', $exclude_product_ids );
      update_post_meta( $coupon_id, 'usage_limit', '1' );
      update_post_meta( $coupon_id, 'usage_limit_per_user', '1' );
      update_post_meta( $coupon_id, 'limit_usage_to_x_items', '' );
      update_post_meta( $coupon_id, 'expiry_date', $date );
      update_post_meta( $coupon_id, 'apply_before_tax', 'no' );
      update_post_meta( $coupon_id, 'free_shipping', $free_shipping );
      update_post_meta( $coupon_id, 'exclude_sale_items', $exclude_sale_items );
      update_post_meta( $coupon_id, 'product_categories', $product_categories );
      update_post_meta( $coupon_id, 'exclude_product_categories', $exclude_product_categories );
      update_post_meta( $coupon_id, 'minimum_amount', $minimum_amount );
      update_post_meta( $coupon_id, 'maximum_amount', $maximum_amount );
      update_post_meta( $coupon_id, 'customer_email', $customer_email );

      $search = array( '{COUPONCODE}', '{COUPONEXPIRY}', '{ALLOWEDCATEGORIES}', '{EXCLUDEDCATEGORIES}', '{ALLOWEDPRODUCTS}', '{EXCLUDEDPRODUCTS}' );
      $replace = array( $code, $expire, $allowed_cats, $excluded_cats, $allowed_products, $excluded_products );
      $subject = str_replace( $search, $replace, get_post_meta($post_id, 'wcmcd_email_sub',true ) );
      $subject = do_shortcode( $subject );
      $body = str_replace( $search, $replace, get_post_meta($post_id, 'wcmcd_email',true ) );
      $body = stripslashes( $body );
      $body = do_shortcode( $body );

      add_filter( 'wp_mail_content_type', array( $this, 'mail_content_type' ) );
      add_filter( 'wp_mail_from', array( $this, 'mail_from' ) );
      add_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );
      
      $headers = array('Content-Type: text/html; charset=UTF-8');

      if ( version_compare( $woocommerce->version, '2.3',  ">=" ) ) {
        $mailer = WC()->mailer();
        $mailer->send( $email, $subject, $mailer->wrap_message( $subject, $body ), $headers, '' );
      }
      else
        wp_mail( $email, $subject, wpautop( $body ), $headers );

      remove_filter( 'wp_mail_content_type', array( $this, 'mail_content_type' ) );
      remove_filter( 'wp_mail_from', array( $this, 'mail_from' ) );
      remove_filter( 'wp_mail_from_name', array( $this, 'mail_from_name' ) );
        
      if( $email != get_option( 'wcmcd_test_mail' ) ){
        $emails[] = $email;
        update_option( 'wcmcd_mails', $emails );
      }

      return $code;
  }


  /**
  * This function is used to check whether merge field exists or not
  *  If merge fiel not exists then it will create the field
  * @param string $merge_var merge var that would be created.
  * @param string $merge_field merge field name
  * @param boolean $public the field should be public or not.
  *
  */
  public function check_merge_field($listId, $merge_var, $merge_field, $public) {
    if( !empty($merge_field) ) {
      $merge_field = trim($merge_field);
      $merge_field = str_replace('_', ' ', $merge_field);
      $apiKey = get_option( 'wcmcd_api_key' );
      $mailchimp = new MGMailChimp( $apiKey );
      $merge_var = trim($merge_var);
      $merge_var = str_replace('_', '', $merge_var);

      $check_vars = $mailchimp->get("/lists/{$listId}/merge-fields");

        
      if( count($check_vars) > 0 ) {
        $tags_array = array();
        
        foreach( $check_vars as $vars_result ) {
          foreach( $vars_result as $key => $vals ) {
            array_push($tags_array, $vals['tag']);
          }
        }

        if( !in_array($merge_var, $tags_array) ) {
          $mailchimp->post("/lists/{$listId}/merge-fields",
            array(
              "tag"           => $merge_var,
              "required"      => false,
              "name"          => $merge_field,
              "type"          => "text",
              "default_value" => "",
              "public"        => $public,
            )
          );
        }
      }
    }
  }


  //subscribe to mailchimp newsletter through ajax
  public function wcmcd_subscribe() {
    $post_id    = isset( $_POST['post_id'] ) ? sanitize_text_field($_POST['post_id']) : '';
    $email      = '';
    $fname      = '';
    $lname      = '';
    $data_val = '';

    $custom_merge_vars = array();
    if( isset($_POST) && isset($_POST['customfields']) ) {
      $data = array();
      foreach( $_POST['customfields'] as $key => $fields ) {
        if( $fields['name'] !== '' 
          && $fields['name'] !== 'wcmcd_email'
          && $fields['name'] !== 'wcmcd_fname'
          && $fields['name'] !== 'wcmcd_lname' ) {
          $data_val = trim($_POST['customfields'][$key]['name']);
          $data_val = str_replace('_', '', $_POST['customfields'][$key]['name']);
          $data[$data_val] = $_POST['customfields'][$key]['value'];
          array_push($custom_merge_vars, $_POST['customfields'][$key]['name']);
        }
        if( $fields['name'] == 'wcmcd_email' ) {
          $email = $_POST['customfields'][$key]['value'];
        }
        if( $fields['name'] == 'wcmcd_fname' ) {
          $fname = $_POST['customfields'][$key]['value'];
        }
        if( $fields['name'] == 'wcmcd_lname' ) {
          $lname = $_POST['customfields'][$key]['value'];
        }
      }
    }



    $apiKey     = get_option( 'wcmcd_api_key' );
    $listId     = get_post_meta($post_id, 'wcmcd_list_id', true);

    $welcome    = get_option( 'wcmcd_welcome' ) == 'yes' ? true : false;
    $merge_vars = array( 'FNAME'=> $fname, 'LNAME'=> $lname );
    $source     = get_option( 'wcmcd_source' );
    $signup_source_link = !empty(get_option('wcmcd_source_link')) ? get_option('wcmcd_source_link') : 'WooCommerce Mailchimp Campaign Discount' ;

    if( !function_exists('icl_object_id') )
      $current_language = substr(get_bloginfo('language'), 0, 2);
    else
      $current_language = ICL_LANGUAGE_CODE;


    $optin = get_option( 'wcmcd_double_optin' ) == 'yes' ? 'pending' : 'subscribed';


    if( !empty( $apiKey ) && !empty( $listId ) ) {
      
      $mailchimp = new MGMailChimp( $apiKey );



      if( $source == 'yes' ) 
        $merge_fields = Array('FNAME' => $fname, 'LNAME' => $lname, 'WCMDLANG' => $current_language, 'SOURCE' => $signup_source_link );
      else
        $merge_fields = Array('FNAME' => $fname, 'LNAME' => $lname, 'WCMDLANG' => $current_language );


      if( is_array($custom_merge_vars) && !empty($custom_merge_vars) ) {
        foreach ($custom_merge_vars as $key => $custom_merge_var) {
          $this->check_merge_field($listId, $custom_merge_var, $custom_merge_var, true);
        }
      }

      $merge_vars_array = array_merge($merge_fields, $data);


      $subscriber_hash = md5(strtolower($email));

      $check_member = $mailchimp->get("lists/{$listId}/members/{$subscriber_hash}", [
        'email_address' => $email,
        'status'        => $optin,
        'merge_fields'  => $merge_vars_array,
        'language'      => $current_language
      ]);




      if( is_array($check_member) && isset($check_member['status']) && $check_member['status'] == '404' ) {
        $result = $mailchimp->post("lists/{$listId}/members", [
          'email_address' => $email,
          'status'        => $optin,
          'merge_fields'  => $merge_vars_array,
          'language'      => $current_language
        ]);

        $result['status'] = 'success'; 
      }

      if( is_array($check_member) && isset($check_member['status']) && $check_member['status'] == 'subscribed' ) {
       $result['status'] = 'error'; 
      }

      if( $result['status'] == 'error'  )
        $result['error'] = $email . __( ' is already subscribed to the list.', 'wcmd' );

      if( $result['status'] == 'success' && isset($result['title']) && $result['title'] == 'Invalid Resource' ) {
        $result['error'] = $result['detail'];
      }

         if( $optin == 'subscribed' && get_option( 'wcmcd_disable_discount') != 'yes' && ( isset( $result['status'] ) && $result['status'] !='error' && !isset($result['title']) ) ) {
        $coupon_code = $this->wcmcd_send_coupons( $email, $current_language, $post_id ); 
          
        if( !empty($coupon_code) ) {
          $result['coupon_code'] = $coupon_code;
        }
      }
      echo json_encode($result);
    }
    else {
      //Show error when api key or list id is not set from admin settings
      echo json_encode( array( 'status' => 'error', 'error' => __( 'Please setup mailchimp api key and list id.', 'wcmcd' ) ) );
    }
    exit;
  }

  /**
  *
  * Set default email from address set from the admin.
  *
  * @return string $from_email email address from which the email should be sent.
  *
  */
  public function mail_from() {
    $from_email = get_option( 'wcmcd_email_id' );
    return $from_email;
  }

  /**
  *
  * Set default email from name set from the admin.
  *
  * @return string $from_name name  from which the email should be sent.
  *
  */
  public function mail_from_name() {
    $from_name = get_option( 'wcmcd_email_name' );
    return $from_name;
  }

  /**
  *
  * Set email content type
  *
  * @return string content type for the email to be sent.
  *
  */
  public function mail_content_type() {
    return "text/html";
  }

  /**
  *
  * Our own custom method to verify the coupon for specific email address
  * as the one with woocommerce core doesn't work always.
  *
  * @param $valid boolean validation status.
  * @param $item list of values for the submitted coupon
  *
  * @return boolean status for coupon validation.
  *
  */
  public function validate_coupon( $valid, $item ) {
    if( is_array( $item->customer_email ) ) {
      global $current_user;
      wp_get_current_user();
            
      if( !is_user_logged_in() 
        && $item->customer_email[0] != '' 
        && $item->customer_email[0] != $current_user->user_email  ) {
          add_filter('woocommerce_coupon_error', array($this,'custom_error'), 10, 3);
          return false;
        }
        else {
          if( $item->customer_email[0] != '' 
            && $item->customer_email[0] != $current_user->user_email ) {
              add_filter('woocommerce_coupon_error', array($this,'custom_error'), 10, 3);
              return false;
          }
        }
    }
    return $valid;
  }

  /**
  *
  * Custom error message for coupon validation.
  *
  * @param string $err default error message.
  * @param string $errcode error code for the error
  * @param array of values for the applied coupon
  *
  * @return string error message.
  *
  */
  public function custom_error( $err, $errcode, $val ) {
    if( !is_user_logged_in() )
      return __( 'Please login to apply this coupon.', 'wcmcd' );
    else
      return __( 'This coupon is assigned to some other user, Please verify !', 'wcmcd' );
  }

  /**
  *
  * Output products for the ajax search on admin.
  *
  * @return json matched products
  *
  */
  public function wcmcd_ajax_products() {
    global $wpdb;
    $post_types = array( 'product' );
    ob_start();

    if ( empty( $term ) ) {
      $term = wc_clean( stripslashes( $_GET['term'] ) );
    } 
    else {
      $term = wc_clean( $term );
    }

    if ( empty( $term ) ) {
      die();
    }

    $like_term = '%' . $wpdb->esc_like( $term ) . '%';

    if ( is_numeric( $term ) ) {
      $query = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id WHERE posts.post_status = 'publish' AND ( posts.post_parent = %s OR posts.ID = %s OR posts.post_title LIKE %s OR ( postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
                    )
                )
            ", $term, $term, $term, $like_term );
    } 
    else {
      $query = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id WHERE posts.post_status = 'publish' AND ( posts.post_title LIKE %s
        or posts.post_content LIKE %s OR ( postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
                    )
                )
            ", $like_term, $like_term, $like_term );
    }

    $query .= " AND posts.post_type IN ('" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "')";

    if ( ! empty( $_GET['exclude'] ) ) {
      $query .= " AND posts.ID NOT IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['exclude'] ) ) ) . ")";
    }

    if ( ! empty( $_GET['include'] ) ) {
      $query .= " AND posts.ID IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['include'] ) ) ) . ")";
    }

    if ( ! empty( $_GET['limit'] ) ) {
      $query .= " LIMIT " . intval( $_GET['limit'] );
    }

    $posts          = array_unique( $wpdb->get_col( $query ) );
    $found_products = array();

    if ( ! empty( $posts ) ) {
      foreach ( $posts as $post ) {
        $product = wc_get_product( $post );
        
        if ( ! current_user_can( 'read_product', $post ) ) {
          continue;
        }

        if ( ! $product || ( $product->is_type( 'variation' ) && empty( $product->parent ) ) ) {
          continue;
        }

        $found_products[ $post ] = rawurldecode( $product->get_formatted_name() );
      }
    }

    wp_send_json( $found_products );
  }

  //shortcode tag for generating 
  public function wcmcd_shortcode( $atts ) {
    $options = shortcode_atts( array(
      'width' => '100%',
      'align' => '',
      'btn_width' => 'auto',
      'btn_align' => 'center',
      'top_text'  => '',
      'top_text_color' => '#000',
      'layout'    => 'vertical'
        ), $atts );
    extract( $options );

    if( $align == 'center' )
      $align = 'margin:0 auto;';
    else if( $align == 'left' || $align == 'right' )
      $align = 'float:' . $align . ';';

    $fields = get_option( 'wcmcd_fields' );
    $form = '<div class="wcmcd-form-wrapper wcmcd-' . $layout . '" style="width:' . $width . '; ' . $align . '">';
    $form .= '<div class="wcmcd-loading"></div>';
    
    if( $top_text != '' )
      $form .= '<div class="wcmcd-top-title" style="color:' . $top_text_color . '">' . $top_text . '</div>';
        
      $form .= '<div class="wcmcd_content">';
      $form .='<div class="wcmcd_text">';
      $form .= '<form class="wcmcd-form wcmcd_' . $fields . '">';
      $form .= '<div class="validation-wrap"><span class="wcmcd-validation"></span></div><div class="wcmcd-fields">';
      
      if( $fields == 'email_name' || $fields == 'email_name_all' )
        $form .= '<input type="text" placeholder="'. __('Enter first name', 'wcmcd' ) .'" name="wcmcd_fname" class="wcmcd_fname">';

      if( $fields == 'email_name_all' )
        $form .= '<input type="text" placeholder="'. __('Enter last name', 'wcmcd' ) .'" name="wcmcd_lname" class="wcmcd_lname">';
        
      $form .='<input type="text" placeholder="'. __('Enter your email', 'wcmcd' ) .'" name="wcmcd_email" class="wcmcd_email">';

      //checkbox for terms and conditions
      if( get_option('wcmcd_terms_condition') == 'yes' ) :

        $term_condition_text = !empty(get_option('wcmcd_terms_condition_text')) ? get_option('wcmcd_terms_condition_text') : '';
        $uniq_id = uniqid();

        $form .= '<div class="wcmcd-checkbox-wrap">';
        $form .= '<input type="checkbox" id='.$uniq_id.' class="wcmcd-terms-conditions" name="wcmcd_terms_condition">';
        $form .= '<label for='.$uniq_id.'>'.$term_condition_text.'</label>';
        $form .= '</div>';
      endif;

      $form .= '</div><div class="wcmcd-btn-cont" style="text-align:' . $btn_align . '">';
      $form .= '<button class="wcmcd-btn" style="width:' . $btn_width . '">' . get_option( 'wcmcd_btn_text' ) . '</button>';
      $form .= '</div><div class="wcmcd-clear"></div></form>';
      $form .= '<div class="wcmcd-clear"></div>';
      $form .='</div></div></div>';

    return $form;
  }

  /**
  * Add Contextual help tab
  */
  public function add_tabs() {
    $screen = get_current_screen();

    if ( $screen->id != 'woocommerce_page_wc-settings' )
      return;
    
    $screen->add_help_tab( 
      array(
        'id'      => 'wcmcd_wpml',
        'title'   => __( 'WPML ShortCodes ', 'wcmcd' ),
        'content' =>
          '<p>' . __( 'You can use [wcmcd] shortcode to translate the contents of email body, email subject, popup text, popup header text and success message. Please find the list of variables.', 'wcmcd' ) . '</p>' .
              '<table class="widefat">
                <tr>
                  <th>Variable</th>
                  <th>Description</th>
                </tr>
                <tr>
                  <td>lang</td>
                  <td>Language code of the content</td>
                </tr>
              </table>' .
              '<p>' . __( 'Here some examples below:</br>[wcmcd lang="en"]English Content[/wcmcd]</br>[wcmcd lang="fr"]French Content[/wcmcd]', 'wcmcd' ) . '</p>'
      ));

    $screen->add_help_tab( 
      array(
        'id'      => 'wcmcd_help',
        'title'   => __( 'Mailchimp Campaign Discount ', 'wcmcd' ),
        'content' =>
          '<p>' . __( 'Thanks for purchasing the plugin. Please find the list of variables you can use for email body and email subject.', 'wcmcd' ) . '</p>' .

          '<table class="widefat">
            <tr>
              <th>Variable</th>
              <th>Description</th>
            </tr>
            <tr>
              <td><input readonly value="{COUPONCODE}"></td>
              <td>The coupon code which the user will use to reedem his discount. Make sure you have added this in email content otherwise the user can\'t get the discount.</td>
            </tr>
            <tr>
              <td><input readonly value="{COUPONEXPIRY}"></td>
              <td>It will output the coupon expiry date if you have entered a value for coupon validity.</td>
            </tr>
            <tr>
              <td><input readonly size="26" value="{ALLOWEDCATEGORIES}"></td>
              <td>It will display the list of categories with their link on which the discount is applicable. Make sure you have selected some categories otherwise it will output nothing.</td>
            </tr>
            <tr>
              <td><input readonly size="26" value="{EXCLUDEDCATEGORIES}"></td>
              <td>It will display the list of categories with their link on which the discount is not applicable. Make sure you have selected some categories otherwise it will output nothing.</td>
            </tr>
            <tr>
              <td><input readonly size="26" value="{ALLOWEDPRODUCTS}"></td>
              <td>It will display the list of products with their link on which the discount is applicable. Make sure you have selected some products otherwise it will output nothing.</td>
            </tr>
            <tr>
              <td><input readonly size="26" value="{EXCLUDEDPRODUCTS}"></td>
              <td>It will display the list of products with their link on which the discount is not applicable. Make sure you have selected some products otherwise it will output nothing.</td>
            </tr>
          </table>'));

    $screen->add_help_tab( array(
      'id'      => 'wcmcd_help_shortcode',
      'title'   => __( 'Mailchimp Campaign Discount Shortcode', 'wcmcd' ),
      'content' => 
        '<p>' . __( 'You can use <i>[wc_mailchimp_campaign_discount]</i> shortcode to use the mailchimp campaign discout form on your page/post/widget etc.<br>Please find the list of variables you can use with shortcode.' ) . '</p>'.

          '<table class="widefat">
            <tr>
              <th>Variable</th>
              <th>Description</th>
            </tr>
            <tr>
              <td><input readonly size="10" value="width"></td>
              <td>Define a width for the signup form. <br>Possible values: 100px, 100%, 500px etc. <br>Usage: [wc_mailchimp_discount width="400px"]</td>
            </tr>
            <tr>
              <td><input readonly size="10" value="align"></td>
              <td>Set the alignment for the signup form. <br> Possible values: left,right and center.<br>Usage: [wc_mailchimp_discount align="center"]</td>
            </tr>
            <tr>
              <td><input readonly size="10" value="btn_width"></td>
              <td>Set width for the subscribe button.<br> Possible values: 100px, 429px, 100%, 69% etc.<br>Usage: [wc_mailchimp_discount btn_width="300px"]</td>
            </tr>
            <tr>
              <td><input readonly size="10" value="btn_align"></td>
              <td>Set the alignment for the subscribe button. <br> Possible values: left,right and center.<br>Usage: [wc_mailchimp_discount btn_align="right"]</td>
            </tr>
            <tr>
              <td><input readonly size="10" value="top_text"></td>
              <td>Define a text that would appear on top of the form.<br>Usage: [wc_mailchimp_discount top_text="Subscribe to our newsletter and win discount"]</td>
            </tr>
            <tr>
              <td><input readonly size="16" value="top_text_color"></td>
              <td>Set a text color for the top text.<br>Usage: [wc_mailchimp_discount top_text_color="#ffcc00"]</td>
            </tr>
            </table>'.
            '<p>' . 'You can combine any of the shortcode variables and create different type of forms. Check some examples below:<br>'.
            '[wc_mailchimp_campaign_discount width="400px" align="center" btn_width="100%" texttop_text_top="Signup for newsletter" top_text_color="#333333"]</b>'
        ) );
  }

}