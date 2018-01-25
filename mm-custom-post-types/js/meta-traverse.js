(function($) {

    $('.meta-traverse-checkbox').change(function() {
        $(this).closest('.meta-traverse-container').find('.meta-traverse-toggle').toggle();
    });

})(jQuery);
