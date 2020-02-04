jQuery(document).ready(function() {
	jQuery('[data-action="accordion"]').unbind().click(function() {
		var me = jQuery(this);
		var accordion;
		if ( me.attr('data-accordion-selector') ) {
			accordion = jQuery(me.attr('data-accordion-selector'));
		} else {
			accordion = me.closest('.accordion');
		}

		if ( accordion.hasClass('expanded') ) {
			accordion.removeClass('expanded').addClass('collapsed');
		} else {
			accordion.closest('.accordion-container').children('.accordion').removeClass('expanded').addClass('collapsed');
			accordion.removeClass('collapsed').addClass('expanded');
		}
		

	
	});
	
	
});
