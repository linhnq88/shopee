(function($) {

       	 $(document).on( 'click', '.wcvaswatchinput',
       	 	function( e ){
              var hoverimage    = $(this).attr('data-o-src');
              var parent        = $(this).closest('li');
              var parentdiv     = $(this).closest('div.shopswatchinput');
              var productimage  = $(this).closest('.product').find("img").attr("src"); 
             
			 $( this ).closest('.shopswatchinput').find('div.selectedswatch').removeClass('selectedswatch').addClass('wcvashopswatchlabel');
			 $( this ).closest('.wcvaswatchinput').find('div.wcvashopswatchlabel').removeClass('wcvashopswatchlabel').addClass( 'selectedswatch' );
			 
               if (hoverimage) {
                $(this).closest('.product').find("img").attr("src",hoverimage);
				$(this).closest('.product').find("img").attr("srcset",hoverimage);
                $(parentdiv).attr("prod-img",productimage);
               }
             }
			 

         );       

})(jQuery);