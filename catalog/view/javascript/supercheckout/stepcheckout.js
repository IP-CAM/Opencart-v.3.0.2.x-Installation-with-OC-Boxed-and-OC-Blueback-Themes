$(document).ready(function ($) {
//
//	$(' .top-link-cart , .top-link-checkout ').on( "click" , function() {	
//		return false;
//	});
//	
    $('#shipping-step').hide();
    $('#review-step').hide();
    $('.goto-shipping-step').attr('style', 'display: block;');
    $(' .show-offcanvas-checkout , .top-link-cart , .top-link-checkout , .cart-link, .skip-cart , #cart-step ').on("click", function () {

        $('body').addClass('active-offcanvas-checkout');
        $('html').addClass('active-offcanvas-checkout');
        $('.control-overlay').show();
        $('.kb_slideCheckout #cart-step').show();
        $('.kb_slideCheckout').addClass('active');
        $('.kb_slideCheckout').removeClass('step2 step3');
        $('.goto-shipping-step').attr('style', 'display: block;');
        setTimeout(function () {
            $('.kb_slideCheckout #shipping-step').show();
            $('.kb_slideCheckout #review-step').show();
        }, 1000);

    });

    $('.close-off-canvas').on("click", function () {
        setTimeout(function () {
            $('.kb_slideCheckout').removeClass('active');
            $('.offcanvas-checkout-button').show();
            $('.control-overlay').hide();
            setTimeout(function () {
                $('body').removeClass('active-offcanvas-checkout');
                $('html').removeClass('active-offcanvas-checkout');
            }, 900);
        }, 100);
    });
    $('.close-offcanvas-checkout').click(function () {
        $('.kb_slideCheckout #shipping-step').hide();
        $('.kb_slideCheckout #review-step').hide();
        $('.kb_slideCheckout').removeClass('active');
        $('.offcanvas-checkout-button').show();
        $('#cart-step').show();
//                $('.control-overlay').hide();
        setTimeout(function () {
            $('body').removeClass('active-offcanvas-checkout');
            $('html').removeClass('active-offcanvas-checkout');
        }, 900);
    });
//	$('.control-overlay').click( function() {	
//		$('.kb_slideCheckout').removeClass('active');
//        $('.offcanvas-checkout-button').show();
//        $('.control-overlay').hide();
//		setTimeout(function () {      
//			$('body').removeClass('active-offcanvas-checkout');  
//			$('html').removeClass('active-offcanvas-checkout');  
//		}, 900);
//	});

    $('.shipping-step-content').click(function () {
        $('.kb_slideCheckout').removeClass('step3');
        $('.kb_slideCheckout').addClass('step2');
//        $('.goto-shipping-step').hide(); 
        $('.goto-shipping-step').attr('style', 'display: none !important;');
        $('.goto-review-step').attr('style', 'display: block;');
        $('#review-step').hide();
    });

    $('.goto-shipping-step').click(function () {
//		$('#cart-step').hide();
        $('#shipping-step').show();
        $('#review-step').hide();
    });
    $('.goto-review-step').click(function () {
//        $('#shipping-step').hide();
        $('#review-step').show();
//                $('#cart-step').hide();
    });

    $('.hover_offcanvas_checkout').click(function () {
        $('.box .hidden-mst').addClass('hidden-mst-active');
    });

    //}, 10000);
    $('.offcanvascheckout_login').click(function () {
        $('.offcanvas-checkout-login-form').show();
        $('.offcanvas-checkout-forget-pass-form').hide();
    });
    $('.offcanvascheckout_password_link').click(function () {
        $('.login-message').hide();
        $('.offcanvas-checkout-login-form').hide();
        $('.offcanvas-checkout-forget-pass-form').show();
    });
    $('.offcanvascheckout_login_link').click(function () {
        $('.offcanvas-checkout-login-form').show();
        $('.offcanvas-checkout-forget-pass-form').hide();
    });
    $('.close-login-popup').click(function () {
        $('.login-message').hide();
        $('.offcanvas-checkout-login-form').hide();
    });
});
