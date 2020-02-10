jQuery( document ).ready(function( $ ) {
    if (useTooltipster) {
        // Set tooltips on browse page
        $("[data-browse-id]").each(function( i ) {
            var id = $(this).data('browse-id');
            $('#tooltip-browse-'+id).tooltipster({
               content: $(eval('overlay'+id))
            });
        });

        // Set tooltips on shows page
        $(".showmaze").each(function( i ) {
            $(this).tooltipster({
                theme: 'tooltipster-shows-page',
                maxWidth: 500
            });
        });

        // Set tooltips on tvschedule page
        $(".tvschedule").each(function( i ) {
            $(this).tooltipster({
                theme: 'tooltipster-tvschedule-page',
                maxWidth: 500
            });
        });

        // Get position via data-attribute and do things
        $('[title][title!=""]').each(function(){
            // Get Position for Tooltips
            var ttPos;
            var ttW;
            switch ($(this).data('tooltip-position')) {
                case 'right':
                    ttPos = 'right';
                break;
                case 'left':
                    ttPos = 'left';
                break;
                case 'top':
                    ttPos = 'top';
                break;
                case 'top-right':
                    ttPos = 'top-right';
                break;
                case 'top-left':
                    ttPos = 'top-left';
                break;
                case 'bottom':
                    ttPos = 'bottom';
                break;
                case 'bottom-right':
                    ttPos = 'bottom-right';
                break;
                case 'bottom-left':
                    ttPos = 'bottom-left';
                break;
                default:
                    ttPos = 'top';
                break;
            }

            // Get Width for Tooltips
            if ($(this).data('tooltip-width') != null) {
                ttW = $(this).data('tooltip-width');
            } else {
                ttW = null;
            }

            $(this).tooltipster({
                position: ttPos,
                maxWidth: ttW
            });
        });
    }
    $("li.dropdown-more").on({
        mouseenter: function () {
            $(this).addClass('expanded');
        },
        mouseleave: function () {
            $(this).removeClass('expanded');
        }
    });
});