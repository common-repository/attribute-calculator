jQuery(document).ready(function ($) {		
	

	$(document).on('found_variation', 'form.cart', function (event, variation) {	
		
		theLength=($('#pa_'+theVal.multiplyBy+' option:selected').val());
		if(typeof theLength !== 'undefined'){	
		
			theLength = theLength.substring(0, theLength.indexOf('-'));				
			cleanMe = $('.woocommerce-variation-price .amount').html();
			cleaned = cleanMe.replace(/[^0-9\.]/g, "");
			total = (parseFloat(cleaned) * theLength).toFixed(2);	
$('.single_variation_wrap .single_variation .price').append('<span class="length-amount"></span>');			
			$('.single_variation_wrap .length-amount').html(' x ' + theLength + ' = ' + theVal.theCurrency + total);
			console.log(' x ' + theLength + ' = ' + theVal.theCurrency + total);
			
		}else{
			console.log('nope');
			//$('table.variations').append(theVal.theError);
		}
		
		
	});

});
