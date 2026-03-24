(function( $ ) {
	'use strict';
	//Se a section=lkn_pix_for_woocommerce_paghiper
	if( window.location.search.indexOf('section=lkn_pix_for_woocommerce_paghiper') > -1 ) {
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.getElementById('mainform');
			if(form){
				const link = document.createElement('a');
				const pElement = form.querySelector('p');
				if(pElement){
					link.href = 'https://paghiper.com';
					link.target = '_blank';
					link.textContent = 'PagHiper';
			
					const parts = pElement.textContent.split('PagHiper');
			
					pElement.textContent = '';
			
					parts.forEach((part, index) => {
						pElement.appendChild(document.createTextNode(part));
			
						if (index < parts.length - 1) {
							pElement.appendChild(link.cloneNode(true));
						}
					});
				}	
			}
		});
	}

})( jQuery );