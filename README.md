VMG Chosen Member
========
Fieldtype for Expression Engine 2
--------

**VMG Chosen Member** is a fieldtype allowing the AJAX selection of one or more members inside an entry. It's specifically designed for use on sites with a large amount of members, in cases where a regular `<select>` dropdown with thousands of `<option>`s would decrease publish page performance.

It includes autocomplete capabilities and the friendly selection/listing of large numbers of users using a modified version of the [Chosen](http://harvesthq.github.com/chosen/) JavaScript plugin.

**VMG Chosen Member** can be used alone, within [Matrix](http://pixelandtonic.com/matrix/), [Low Variables](http://gotolow.com/addons/low-variables/), and [Better Workflow](http://devot-ee.com/add-ons/better-workflow/).

Installation
-------
*	Upload ee2/third_party/vmg_chosen_member to system/expressionengine/third_party
*	Upload themes/third_party/vmg_chosen_member to themes/third_party
*	Install the fieldtype by going to Add-Ons &rarr; Fieldtypes
*	Ensure that both the Fieldtype and Module are installed

Usage
-------

### Single Variable Tag

	{custom_field}
> Outputs the pipe (|) delimited list of member IDs.

### Variable Tag Pair

	{custom_field}
		<h2>{cm_screen_name}</h2>
		<p>Email: {cm_email}</p>
		<p>Some custom member field: {cm_some_custom_member_field}</p>
	{/custom_field}
> Outputs member data for the selected members and provides access to all data tags.<br />**Note:** A prefix of "cm_" is added by default, but can be manually changed or set to empty if desired.

> #### Parameters
*	**disable** = "member_data"<br />Setting *disable* to *member_data* will skip loading custom member fields.
*	**prefix** = "cm\_"<br/>**Set to "cm\_" by default**<br />By providing a prefix, all data tags will be parsed prepended with the string of your choosing. This can be helpful for avoiding naming collisions.
*	**group_id** = "1|2|3"<br />While you can limit selections to specific member groups in the field's settings, this parameter allows you to further limit results on output.
*	**orderby** = "email"<br />The *orderby* parameter sets the display order of the output.
*	**sort** = "asc"<br />The *sort* order will be applied if you specify an *orderby* parameter.
*	**limit** = "1"<br />The *limit* parameter will set the number of results that will be returned.
*	**backspace** = "0"<br />Remove _X_ number of characters from the final tag output.
*	**member_id** = "1|2|3"<br />Limit returned users to those specified.


### :total_members Tag

	{custom_member_field:total_members}
> Outputs the total number of members selected within the field.


### Associated Entries

To view Channel Entries that a user has been selected in (via a VMG Chosen Member field), you can use the {exp:vmg_chosen_member:assoc_entries} tag.

	{exp:vmg_chosen_member:assoc_entries prefix="cm_" field="chosen_member_field" member_id="1|2|3"}

		{exp:channel:entries
			require_entry="yes"
			entry_id="{cm_entry_ids}"
		}	
			<ul>
				{chosen_member_field prefix="cmf_"}

					<li>
						<h3>{cmf_screen_name}</h3>
						<img src="/path/to/avatars/{cmf_avatar_filename}"/>
					</li>
				
				{/chosen_member_field}
			</ul>
			
		{/exp:channel:entries}

	{/exp:vmg_chosen_member:assoc_entries}

> #### Parameters
*	**prefix** = "cm\_"<br/>**Set to "cm\_" by default**<br />By providing a prefix, all data tags will be parsed prepended with the string of your choosing. This can be helpful for avoiding naming collisions.
*	**field** = "chosen_member_field"<br />The VMG Chosen Member field you would like to search. If your VMG Chosen Member field is within a Matrix field, you would set the name of the parent Matrix field.
*	**col** = "my_matrix_col"<br />If your VMG Chosen Member field is within a Matrix field, you would set the name of the Matrix column here.
*	**member_id** = "1|2|3"<br />Member IDs that you would like to search for.

> Outputs the prefixed {entry_ids} tag.

### Support for other fieldtypes/add-ons

VMG Chosen Member can be used within [Matrix](http://pixelandtonic.com/matrix/), [Low Variables](http://gotolow.com/addons/low-variables/), and [Better Workflow](http://devot-ee.com/add-ons/better-workflow/).

Compatibility
---------

VMG Chosen Member has been tested to work on ExpressionEngine 2.1.3+ with PHP 5.1+. 

Warranty/License
--------
There's no warranty of any kind. If you find a bug, please report it or submit a pull request with a fix. It's provided completely as-is; if something breaks, you lose data, or something else bad happens, the author(s) and owner(s) of this add-on are in no way responsible.

This add-on is owned by [Vector Media Group, Inc](http://www.vectormediagroup.com). You can modify it and use it for your own personal or commercial projects, but you can't redistribute it.