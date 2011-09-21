<div class="vmg-chosen-member-container">
	<select id="vmg_chosen_member_<?=$field_id . '_' . $row_id . '_' . $col_id?>" name="<?=$field_name?>[]" <?=($max_selections != 1 ? 'multiple ' : '')?>style="width: 100%;" class="vmg-chosen-member" rel="<?=$field_id . '_' . $row_id . '_' . $col_id?>">
		<?php foreach($member_data AS $member): ?>
			<option value="<?=$member['member_id']?>" selected="selected"><?=$member['screen_name']?></option>
		<?php endforeach; ?>
	</select>
	<input type="hidden" id="vmg_chosen_member_<?=$field_id . '_' . $row_id . '_' . $col_id . '_field_id'?>" value="<?=$field_id?>"/>
	<input type="hidden" id="vmg_chosen_member_<?=$field_id . '_' . $row_id . '_' . $col_id . '_low_var'?>" value="<?=($is_low_var ? 'true' : 'false')?>"/>
	<input type="hidden" id="vmg_chosen_member_<?=$field_id . '_' . $row_id . '_' . $col_id . '_max_selections'?>" value="<?=$max_selections?>"/>
	<input type="hidden" id="vmg_chosen_member_<?=$field_id . '_' . $row_id . '_' . $col_id . '_placeholder_text'?>" value="<?=$placeholder_text?>"/>
	<input type="hidden" class="vmg_chosen_member_json_url" value="<?=$json_url?>"/>
</div>