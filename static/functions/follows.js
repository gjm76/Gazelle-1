/*
 * Save order by in Follows
 */
jQuery(function($) {
    $('#order_by').on('change', function() {
    	  
    	  var array = ['Name','Index','Added'];
    	  var sortval = $('#order_by').val();

        if(array.indexOf(sortval)+1) { // fail safe
            $.cookie('FollowsOrderPanelState', sortval, { expires: 100 });
        }
    });
});

jQuery(function($) {
    $('#order_way').on('change', function() {
    	  
    	  var array = ['desc','asc'];
    	  var sortval = $('#order_way').val();

        if(array.indexOf(sortval)+1) { // fail safe
            $.cookie('FollowsOrderWayPanelState', sortval, { expires: 100 });
        }
    });
});
