jQuery(document).ready(function(){
   MailchimpServicesFunction();
   jQuery(document).on("change", ".service_select select", function(){	
		if(jQuery(this).val()=="mailchimp"){
			setTimeout(function(){ MailchimpServicesFunction(); }, 200);
		}	
	});
}); 

function MailchimpServicesFunction(){
	jQuery.each(MailchimpServices, function (index, ListName) {	
		jQuery(".mail_chimp_list_name select").append('<option value="'+index+'">'+ListName.mail_chimp_list_name+'</option>');	

		jQuery.each(ListName.mail_chimp_tags, function (index, TagsName) {		
			jQuery(".mail_chimp_list_tags select").append('<option value="'+TagsName.mail_chimp_tag_id+'">'+TagsName.mail_chimp_tag_name+'</option>');
		});	
		
		jQuery.each(ListName.mail_chimp_groups, function (index, GroupsName) {		
			jQuery(".mail_chimp_list_groups select").append('<option value="'+GroupsName.mail_chimp_parent_groups_id+'-'+GroupsName.mail_chimp_group_id+'">'+GroupsName.mail_chimp_parent_groups_name+' - '+GroupsName.mail_chimp_group_name+'</option>');
		});	
	});	
}