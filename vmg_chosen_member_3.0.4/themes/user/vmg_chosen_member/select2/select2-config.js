$(document).ready(function(){
	$('.vmg-chosen-member-container').each(function(){
		var current_field = $(this).find('.vmg-chosen-member');
        var unique_id = current_field.attr('rel');

		// Get out quick if no rel data
		if (unique_id == '') { return true; }

		var vmgcm_max_selections = $('#vmg_chosen_member_' + unique_id + '_max_selections').val();
		var vmgcm_placeholder_text = $('#vmg_chosen_member_' + unique_id + '_placeholder_text').val();
		var vmgcm_json_url = $(this).find('.vmg_chosen_member_json_url').val();

		_this = $('#vmg_chosen_member_' + unique_id);
		_this.select2({
			ajax: {
			    url: vmgcm_json_url,
			    type: "post",
			    dataType: 'json',
			    delay: 250,
			    data: function (params) {
			    	if(_this.val() != null) {
			    		escape = JSON.stringify(_this.val());
			    	} else {
			    		escape = null;
			    	}
			    	return {
				        query: params.term, // search term
				        /*escape: escape*/  // set to not return selected values.
				    };
				},
				processResults: function (data, page) {
					_this.find("option[value='']").remove();
					return {
						results: $.map(data, function (item) {
							return {
								id: item.value,
								text: htmlDecode(item.option),
								display_item: item.tag,
								/*text: htmlDecode(item.text),
								display_item: item.text.substring(0 , item.text.indexOf('&nbsp;&nbsp;&nbsp;')),*/
							}
						})
					};
				},
				cache: false
			},
			templateSelection: function(container) {
				if(typeof(container.display_item) === "undefined") {
					return container.text;
				}else{
					$(container.element).attr("display_item", container.display_item);
					return container.display_item;
				}
			},
			minimumInputLength: "1",
			maximumSelectionLength: vmgcm_max_selections,
			placeholder: vmgcm_placeholder_text,
			allowClear: false,
			multiple: true,
			/*dropdownParent: $('#vmg_chosen_member_' + unique_id).parents('.vmg-chosen-member-container:first'),*/
		});

		_this.on('select2:unselect', function(event) {
			$el = $(this);
			setTimeout(function() {
				$el.select2("close");
				setTimeout(function() {
					if((! $el.val()) || ($el.val() == null || $el.val() == "null" || $el.val() == ""))
					{
						$el.html("").append('<option value="" selected></option>');
						$el.trigger('change');
					}
				}, 300);
			}, 10);
		}).trigger('change');

		_this.on('select2:select', function(e) {
			var element = $(this).find('[value="' + e.params.data.id + '"]');
			$(this).append(element);
			$(this).trigger('change');
		});

		$('#hold_field_' + $('#vmg_chosen_member_' + unique_id + '_field_id').val()).css('overflow', 'visible');

	})
});

function htmlEncode(value){
return $('<div/>').text(value).html();
}

function htmlDecode(value){
return $('<div/>').html(value).text();
}