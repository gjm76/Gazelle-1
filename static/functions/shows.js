/*
 * Save sort by order in Shows
 */
jQuery(function($) {
    $('#sort_box').on('change', function() {
    	  
    	  var array = ['id','weight','name','rating','premiered','updated'];
    	  var sortval = $('#sort_box').val();
    	  
        if(array.indexOf(sortval)+1) { // fail safe
            $.cookie('sortPanelState', sortval, { expires: 100 });
        }
    });
});

/*
 * Search Shows by text or TVMaze ID 
 */
jQuery(function($) {
    $('#searchbox_shows').on('keypress', function(evt) {
        //evt.preventDefault();
         if(evt.which === 13){

            //Disable textbox to prevent multiple submit
            $(this).attr("disabled", "disabled");
            
            if ($.isNumeric($(this).val())) { 
               $(this).attr('name','showid');
               $('#shows_action').val('show');
            }   
            else {
               $(this).attr('name','title');
               $('#shows_action').val('advanced');
            }	   

            //Enable the textbox again if needed.
            $(this).removeAttr("disabled");
         }        
    });
});
