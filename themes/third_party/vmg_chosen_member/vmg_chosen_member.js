$(document).ready(function(){

	$('.vmg-chosen-member').each(function(){

		// Get out quick if no rel data
		if ($(this).attr('rel') == '') { return true; }

		var vmgcm_max_selections = $('#vmg_chosen_member_' + $(this).attr('rel') + '_max_selections').val();
		var vmgcm_placeholder_text = $('#vmg_chosen_member_' + $(this).attr('rel') + '_placeholder_text').val();
		var vmgcm_low_var = $('#vmg_chosen_member_' + $(this).attr('rel') + '_low_var').val();
		var vmgcm_json_url = $('.vmg_chosen_member_json_url:first').val() + '&field_id=' + $('#vmg_chosen_member_' + $(this).attr('rel') + '_field_id').val() + '&lv=' + vmgcm_low_var;

		$('#vmg_chosen_member_' + $(this).attr('rel')).chosen(vmgcm_json_url, {
			max_selections: vmgcm_max_selections,
			placeholder_text: vmgcm_placeholder_text,
		});

		$('#hold_field_' + $('#vmg_chosen_member_' + $(this).attr('rel') + '_field_id').val()).css('overflow', 'visible');
	});

});