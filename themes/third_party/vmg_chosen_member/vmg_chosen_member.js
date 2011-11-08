$(document).ready(function(){

	$('.vmg-chosen-member-container').each(function(){

		var current_field = $(this).find('.vmg-chosen-member');

		// Get out quick if no rel data
		if (current_field.attr('rel') == '') { return true; }

		var vmgcm_max_selections = $('#vmg_chosen_member_' + current_field.attr('rel') + '_max_selections').val();
		var vmgcm_placeholder_text = $('#vmg_chosen_member_' + current_field.attr('rel') + '_placeholder_text').val();
		var vmgcm_json_url = $(this).find('.vmg_chosen_member_json_url').val() + '&field_id=' + $('#vmg_chosen_member_' + current_field.attr('rel') + '_field_id').val();
		
		$('#vmg_chosen_member_' + current_field.attr('rel')).chosen(vmgcm_json_url, {
			max_selections: vmgcm_max_selections,
			placeholder_text: vmgcm_placeholder_text,
		});

		$('#hold_field_' + $('#vmg_chosen_member_' + current_field.attr('rel') + '_field_id').val()).css('overflow', 'visible');
	});

});