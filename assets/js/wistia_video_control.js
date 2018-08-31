jQuery(document).ready(function($) {
function load_video() {
	window._wq = window._wq || [];
	_wq.push({ id: '_all', onReady: function(video) {
	  // for all existing and future videos, run this function
		video.bind('end', function() {
			var allVideos = Wistia.api.all();
			for (var i = 0; i < allVideos.length; i++) {
				if (allVideos[i].hashedId() == video.hashedId()) {
				console.log(allVideos[i].hashedId());
				console.log(video.hashedId());
					var hash_id = video.hashedId();
					var data_post_id = '';
					$(".wistia_video_id[data-id='" + hash_id +"']").each( function (e) {
						data_post_id = $(this).attr('data-postId');
						// Set Cookie
						function wcmd_setCookie(cname, cvalue, exdays) {
						  var d = new Date();
						  d.setTime(d.getTime() + (exdays*24*60*60*1000));
						  var expires = "expires="+d.toUTCString();
						  document.cookie = cname + "=" + cvalue + "; " + expires + ";path=/";
						}

						// Get Cookie
						function wcmd_getCookie(cname) {
						  var name = cname + "=";
						  var ca = document.cookie.split(';');
						  for(var i=0; i<ca.length; i++) {
						    var c = ca[i];
						    while (c.charAt(0)==' ') c = c.substring(1);
						    if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
						  }
						  return "";
						}

						//check device is mobile or not
						function check_mobile_device() {
						  var check = false;
						  (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
						  return check;
						};

						function wcmd_isEmail(email) {
						  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
						  return regex.test(email);
						}
						console.log('here');

						function open_wcmd_modal() {
						  	// if( wcmd.wcmd_home == 'yes' && wcmd.is_home != '1' ) return;
						  
						  	if( jQuery('#wcmd_modal').length > 0 ){
							    var overlayClick = wcmd.overlay_click == 'yes' ? true : false;

							    jQuery.magnificPopup.open({
								    items: {
								    	src: '#wcmd_modal'
								    },
								    type: 'inline',
								    removalDelay: 1000,
								    closeOnBgClick: overlayClick,
								    callbacks: {
								        beforeOpen: function() {
								          	this.st.mainClass = wcmd.effect;
								        },

								        beforeClose: function() {
								          	if( wcmd.hinge == 'yes' )
								            	this.content.addClass('hinge');
								        	else
								            	jQuery('.mfp-wrap').css('background', 'transparent');
								        },

								        close: function() {
								          	if( wcmd.hinge == 'yes' )
								            	this.content.removeClass('hinge');
									        // if (hash_id != '')
									        // 	wcmd_setCookie( 'wcmd_'+hash_id, 'yes', wcmd.cookie_length );
								        },

								        open: function(){
								          	jQuery('.mfp-wrap').css('background', wcmd.overlayColor);
								        }
						      		}
						    	});
						    }
						}


						//check if popup is disabled on mobile devices
						if( check_mobile_device() ) {
							if( wcmd.disable_popup_on_mobile != 'yes' && wcmd_getCookie('wcmd_'+hash_id) != 'yes' ) {
								setTimeout( open_wcmd_modal, 1000 );
							}
						}
						else {
							if( wcmd_getCookie('wcmd_'+hash_id) != 'yes' && wcmd.wcmd_popup != 'yes' ) {
							 	if( wcmd.exit_intent == 'yes' )
							    	$(document).on( 'mouseleave', open_wcmd_modal );
							  	else if( wcmd.only_btn != 'yes' )
							    	setTimeout( open_wcmd_modal, 1000 );
							}
						}

						if( wcmd.btn_trigger == 'yes' ){
							$('body').on('click','.wcmd-trigger',function(e){
							  	e.preventDefault();
							  	open_wcmd_modal();
							});
						}
					  

						function wcmd_close_popup(){
							$.magnificPopup.close();
						}


						$('.wcmd-form').submit(function() {
							if (wcmd_getCookie('wcmd_'+hash_id) == 'yes') {
								return false;
							}
							msg = '';
							$form = $(this);
							$form.find('.wcmd-validation').removeClass('success').hide();
							$form.find('.wcmd-validation').removeClass('error').hide();

							var terms_validation = true;
							if( wcmd.enable_terms_condition == 'yes' ) {
								if( $form.find('input[name=wcmd_terms_condition]').prop('checked') ==  false ) {
							    	terms_validation = false;
							  	}
							}

						    if(wcmd_isEmail($form.find('.wcmd_email').val()) && terms_validation ) {
								$form.parents('.wcmd-form-wrapper,#wcmd_modal').find('.wcmd-loading ').show();
								$.post(
						        wcmd.ajax_url,
						        {email: $form.find('.wcmd_email').val(),post_id: data_post_id, hash_id: hash_id, fname: $form.find('.wcmd_fname').val(), lname: $form.find('.wcmd_lname').val(), action: 'wcmd_subscribe'},
						        function(data) {
									var response = jQuery.parseJSON(data);
									$form.parents('.wcmd-form-wrapper,#wcmd_modal').find('.wcmd-loading ').hide();
									if( typeof response.status  !== "undefined" && response.status == 'error' ) {
										$form.find('.wcmd-validation').html(response.error).addClass('error').css('display','inline-block');
									}
									else if( typeof response.status  !== "undefined" && response.status =='success' && response.title == 'Invalid Resource' ) {
										$form.find('.wcmd-validation').html(response.detail).addClass('error').css('display','inline-block');
									}
									else{
										if( wcmd.close_time > 0 && $('.mfp-ready').length > 0 )
									  		setTimeout( wcmd_close_popup, wcmd.close_time*1000 );

							            var SuccessMsg = wcmd.success;
							            if( wcmd.double_optin !== 'yes' ) {
							              var ResponseMsg = SuccessMsg.replace('{COUPONCODE}', response.coupon_code);
							            }
							            else {
							              var ResponseMsg = SuccessMsg.replace('{COUPONCODE}', '');
						            	}
						            	$form.find('.wcmd-validation').html(ResponseMsg).addClass('success').css('display','inline-block');

							            if( wcmd.signup_redirect == 'yes' && wcmd.redirect_url !== '' ) {
								            window.setTimeout(function () {
								            	window.location.href = wcmd.redirect_url;
								            }, wcmd.redirect_timeout*1000 );
							            }
					          		}
					      		});
						    }
						    else {
							    if( terms_validation ) 
							        $form.find('.wcmd-validation').html( wcmd.valid_email ).addClass('error').css('display','inline-block');
							    else 
							        $form.find('.wcmd-validation').html( wcmd.terms_condition_error ).addClass('error').css('display','inline-block');
						    }
					    	return false;
					  	});
					});
					
				}

				
			}
		});
	}});
}
	setTimeout(load_video, 2000);
});
