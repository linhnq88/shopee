(function($) {

       	 $(document).on( 'mouseover', '.wcvaswatchinput',
       	 	function( e ){
              var hoverimage    = $(this).attr('data-o-src');
              var parent        = $(this).closest('li');
              var parentdiv     = $(this).closest('div.shopswatchinput');
              var productimage  = $(this).closest('.product').find("img").attr("src"); 

               if (hoverimage) {
                $(this).closest('.product').find("img").attr("src",hoverimage);
				$(this).closest('.product').find("img").attr("srcset",hoverimage);
                $(parentdiv).attr("prod-img",productimage);
               }
             }
			 

         );       

})(jQuery);