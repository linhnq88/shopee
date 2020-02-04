jQuery(document).ready(function($) {
        
	    if (wcva.tooltip == "yes") {
		    $('.swatchinput label').powerTip();
	    }
      
	    $('form.variations_form').on( 'change', '.wcva-standard-select', function() {
			var selectedtext         = $(this).val();
			
			if (wcva.show_attribute == "yes") {
			   $( this ).closest('tr').prev().find('.wcva_selected_attribute').text(selectedtext);
		    }
		});
     
	    $('form.variations_form').on( 'click', '.swatchinput label', function() {
		    var selectid           = $(this).attr("selectid");
            var dataoption         = $(this).attr("data-option");
			var selectedtext       = $(this).attr("selectedtext");
		    var attributeindex     = $(this).closest('.attribute-swatch').attr('attribute-index');
	
		    
		    if (wcva.quick_view == "off") {
			    if ($(this).hasClass('selectedswatch')) {
				
				$(this).removeClass('selectedswatch').addClass('wcvaswatchlabel');
				
				var currentoptionToSelect = parent.jQuery("form.variations_form #" + selectid + "").children("[value='']");

               //mark the option as selected
                currentoptionToSelect.prop("selected", "selected").change();
				
				$( this ).closest('tr').prev().find('.wcva_selected_attribute').text("");
				
				return;
			   }
		    }
		    
		   if (wcva.show_attribute == "yes") {
			   $( this ).closest('tr').prev().find('.wcva_selected_attribute').text(selectedtext);
		   }
	      
		   
		   $( this ).closest('.attribute-swatch').find('.selectedswatch').removeClass('selectedswatch').addClass('wcvaswatchlabel');
	       $( this ).removeClass('wcvaswatchlabel').addClass( 'selectedswatch' );
		  
		  
		  
           //find the option to select
           var optionToSelect = parent.jQuery("form.variations_form #" + selectid + "").children("[value='" + dataoption + "']");

           //mark the option as selected
           optionToSelect.prop("selected", "selected").change();
		 
					    
		});	   
		
		
		if (wcva.disable_options == "yes") {
			 		
			$('form.variations_form').on( 'click', '.swatchinput label', function( event ) {

                 wcva_disable_swatches_as_dropdown();
		    });
		}
	   
	   
	    
        $( window ).load(function() {
           	
           	   wcva_disable_swatches_as_dropdown();
        });
      
       
        $('form.variations_form').on( 'click', '.reset_variations', function() {
			
			$('form.variations_form').find('.selectedswatch').removeClass('selectedswatch').addClass('wcvaswatchlabel');
			$('form.variations_form').find('.wcva_selected_attribute').text("");
		
		if (wcva.disable_options == "yes") {
			
			$('form.variations_form' ).find('.wcvadisabled').removeClass('wcvadisabled');
			jQuery('.swatchinput').removeClass('wcvadisabled');
            
            if (wcva.hide_options == "yes") {
				jQuery('.swatchinput').show();
			}

            if (wcva_attribute_number == 1) {

               wcva_disable_swatches_as_dropdown();
            }

		}
			
		});



		function wcva_disable_swatches_as_dropdown() {

            var availableoptions = [];
			
			jQuery('form.variations_form').find( '.variations select' ).each( function( i, e ) {
				
				var eachselect = jQuery( e );
				
				
				
				jQuery(e).trigger('focusin');
				
				jQuery(eachselect).find('option').each(function(index,element){
					
					
						
						availableoptions.push(element.value);
						
					
				});

				//console.log(availableoptions);
				
				var wcvalabel = jQuery(this).closest('td').find('.swatchinput label');
				
				jQuery(wcvalabel).each(function(){
					var dataoption = jQuery(this).attr("data-option");
					
					if(jQuery.inArray( dataoption, availableoptions ) < 0){
						
						if ($(this).hasClass('selectedswatch')) {
						   jQuery(this).removeClass('selectedswatch').addClass('wcvaswatchlabel');
		                   jQuery(this).addClass('wcvadisabled');
						   
                           if (wcva.hide_options == "yes") {
						     jQuery(this).parent().hide();
                           }
						 
						} else {
						   jQuery(this).addClass('wcvadisabled');
						   if (wcva.hide_options == "yes") {
						    jQuery(this).parent().hide();
						   }
						 
						}
						
					}else{
						
						jQuery(this).removeClass('wcvadisabled');
						if (wcva.hide_options == "yes") {
						 jQuery(this).parent().show();
						}
					}
				});
			   });
		}

	   
});