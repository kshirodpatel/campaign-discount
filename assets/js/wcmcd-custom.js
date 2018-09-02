
function wcmcd_isEmail(email) {
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(email);
}


jQuery(document).ready(function($) {
  $('.wcmcd-form').submit(function() {
    msg = '';
    $form = $(this);
    customfields = $form.serializeArray();
    $form.find('.wcmcd-validation').removeClass('error').hide();
    var TermsValidationErrorMsg = $form.find('.wcmcd-terms-error-msg').text();

    var terms_validation = true;
    if( $form.find('.wcmcd-btn').attr('data-tems-condition') == 'yes' ) {
      if( $form.find('input[name=wcmcd_terms_condition]').prop('checked') ==  false ) {
        terms_validation = false;
      }
    }


    if( wcmcd_isEmail($form.find('.wcmcd_email').val()) && terms_validation ) {
      $form.parents('.wcmcd-form-wrapper').find('.wcmcd-loading ').show();
      $.post(
        wcmcd.ajax_url,
        {
          post_id: $form.find('.wcmcd-btn').attr('data-post-id'),
          email: $form.find('.wcmcd_email').val(), 
          fname: $form.find('.wcmcd_fname').val(), 
          lname: $form.find('.wcmcd_lname').val(),
          customfields : customfields,
          action: 'wcmcd_subscribe' 
        },
        
        function(data) {
          var response = jQuery.parseJSON(data);
          $form.parents('.wcmcd-form-wrapper').find('.wcmcd-loading ').hide();
          if( typeof response.status  !== "undefined" && response.status == 'error' ) {
            $form.find('.wcmcd-validation').html(response.error).addClass('error').css('display','inline-block');
          }
          else if( typeof response.status  !== "undefined" && response.status =='success' && response.title == 'Invalid Resource' ) {
            $form.find('.wcmcd-validation').html(response.detail).addClass('error').css('display','inline-block');
          }
          else{
            if( wcmcd.close_time > 0 && $('.mfp-ready').length > 0 )
              setTimeout( wcmcd_close_popup, wcmcd.close_time*1000 );

            var SuccessMsg = wcmcd.success;
            if( wcmcd.double_optin !== 'yes' ) {
              var ResponseMsg = SuccessMsg.replace('{COUPONCODE}', response.coupon_code);
            }
            else {
              var ResponseMsg = SuccessMsg.replace('{COUPONCODE}', '');
            }
            $form.find('.wcmcd-validation').html(ResponseMsg).addClass('success').css('display','inline-block');

            if( wcmcd.signup_redirect == 'yes' && wcmcd.redirect_url !== '' ) {
              window.setTimeout(function () {
                window.location.href = wcmcd.redirect_url;
              }, wcmcd.redirect_timeout*1000 );
            }
          }
      });
    }
    else {
      if( terms_validation ) {
        $form.find('.wcmcd-validation').html( wcmcd.valid_email ).addClass('error').css('display','inline-block');
      }
      else {
        $form.find('.wcmcd-validation').html( TermsValidationErrorMsg ).addClass('error').css('display','inline-block');
      }
      
    }
    return false;
  });
});
