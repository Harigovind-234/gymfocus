(function ($) {
    "use strict";
    
    $(document).ready(function() {
        // Menu Dropdown Toggle
        if($('.menu-trigger').length){
            $(".menu-trigger").on('click', function() {
                $(this).toggleClass('active');
                $('.header-area .nav').slideToggle(200);
            });
        }

        // Window Resize Mobile Menu Fix
        $(window).on('resize', function() {
            var width = $(window).width();
            $('.submenu').on('click', function() {
                if(width < 767) {
                    $('.submenu ul').removeClass('active');
                    $(this).find('ul').toggleClass('active');
                }
            });
        });
    });
})(jQuery);