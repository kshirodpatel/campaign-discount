jQuery(function($){
	$('.wcmcd_cats, .wcmcd_products').select2();
  $('.wcmcd-add-custom-field').hide();
  $("#wcmcd_shipping").click(function() {
      $(this).val(this.checked ? 1 : 0);          
  });
  $("#wcmcd_sale").click(function() {
      $(this).val(this.checked ? 1 : 0);          
  });

  //Add custom field
  $('body').on('click', '.wcmcd-add-custom-field', function() {
    var FieldType = $(this).parents('td').find('select.wcmcd-custom-field-type').val();
  	var SelectedButton = $(this);
  	var CustomField = $(this).parents('td.custom-field-wrap').find('.wcmcd-custom-field-name').val();
    var PlaceHolder;
    var ValidationMessage;
    var html;
    var Row;
    var RemoveButton;
    var HiddenData;

    if( SelectedButton.parents('td.custom-field-wrap').find('input.wcmcd-validation-enable:checked').length > 0 ) {
      PlaceHolder = SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-custom-field-placeholder').val();
      ValidationMessage = SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-custom-field-validation').val();
    }

    if( PlaceHolder == undefined ) {
      PlaceHolder = '';
    }

    if( ValidationMessage == undefined ) {
      ValidationMessage = '';
    }


  	if( CustomField.length ) {
      Row = SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-custom-fields-list li').length;
  		RemoveButton = SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-remove-wrap').html();
  		SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-custom-field-name').val('');
      SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-custom-field-placeholder').val('');
      SelectedButton.parents('td.custom-field-wrap').find('input.wcmcd-validation-enable').prop('checked', false); // Unchecks it
      SelectedButton.parents('td.custom-field-wrap').find('input.wcmcd-custom-field-validation').val('');
      html = '<input type="hidden" name="wcmcd_campaign_fields[name][]" value="'+CustomField+'">';
      // html += '<input type="hidden" name="wcmcd_campaign_fields[placeholder][]" value="'+PlaceHolder+'">';
      // html += '<input type="hidden" name="wcmcd_campaign_fields[validation][]" value="'+ValidationMessage+'">';
      RemoveButton = '<div class="wcmcd-remove-btn"></div>';
      ToggleButton = '<div class="wcmcd-toggle-btn"></div>';
      var TestData = SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-hidden-custom-fields').html();
  		//SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-custom-fields-list ul').append('<li data-name="'+CustomField+'" data-required="" data-placeholder="'+PlaceHolder+'" data-validation="'+ValidationMessage+'">'+CustomField+RemoveButton+html+'</li>');
      SelectedButton.parents('td.custom-field-wrap').find('.wcmcd-custom-fields-list ul').append('<li>'+ToggleButton+RemoveButton+'<h4>'+CustomField+'</h4>'+html+'</li>');
  	}
  	
  });

  $('body').on('click', '.wcmcd-remove-btn', function(e) {
  	e.preventDefault();
  	$(this).parent('li').remove();
  });


  $( 'select.wcmcd-custom-field-type' ).on('change', function(event) {
    event.preventDefault();
    var SelectedRow = $(this);
    var Selected = $(this).val();
    if( Selected !== 'Select Type' ) {
      $('.wcmcd-add-custom-field').show();
    }
    else {
      $('.wcmcd-add-custom-field').hide();
    }
  });

  $('body').on('click', '.wcmcd-toggle-btn', function() {
    console.log('test');
  });

});