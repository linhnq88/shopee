jQuery(document).ready(function($) {
$(function() {
$('#wcva_woocommerce_global_activation').live('change',function(){
    if ($(this).prop('checked')) {
             $(this).closest("tr").next().show(200);
        }
        else {
             $(this).closest("tr").next().hide(200);
        }
});

$('#woocommerce_wcva_swatch_tooltip:not(:checked)').closest('tr').next().hide();
//hide show "disable tooltip on iphone devices checkbox"
$('#woocommerce_wcva_swatch_tooltip').change(function() {
	
	var wcvarow = $(this).closest('tr').next();
	
    if( $(this).is(':checked')) {
        wcvarow.show();
    } else {
        wcvarow.hide();
    }
}); 



});
});