
function Trailer_Toggle() {

    jQuery('#trailerbox').toggle();
 
    if (jQuery('#trailerbox').is(':hidden')) 
        jQuery('#trailertoggle').html('(Show)');
    else  
        jQuery('#trailertoggle').html('(Hide)');
            
    jQuery.cookie('showDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function Cast_Toggle() {

    jQuery('#castbox').toggle();
 
    if (jQuery('#castbox').is(':hidden')) 
        jQuery('#casttoggle').html('(Show)');
    else  
        jQuery('#casttoggle').html('(Hide)');
            
    jQuery.cookie('showDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function ShowInfo_Toggle() {

    jQuery('#showinfobox').toggle();
 
    if (jQuery('#showinfobox').is(':hidden')) 
        jQuery('#showinfotoggle').html('(Show)');
    else  
        jQuery('#showinfotoggle').html('(Hide)');
            
    jQuery.cookie('showDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function Cover_Toggle() {

    jQuery('#coverbox').toggle();
 
    if (jQuery('#coverbox').is(':hidden')) 
        jQuery('#covertoggle').html('(Show)');
    else  
        jQuery('#covertoggle').html('(Hide)');
            
    jQuery.cookie('showDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function FanArt_Toggle() {

    jQuery('#fanartbox').toggle();
 
    if (jQuery('#fanartbox').is(':hidden')) 
        jQuery('#fanarttoggle').html('(Show)');
    else  
        jQuery('#fanarttoggle').html('(Hide)');
            
    jQuery.cookie('showDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function Season_Toggle(e) {
    let toggleLink = jQuery(e.target);
    let seasonRows = toggleLink.closest('tr.colhead').nextUntil('tr.colhead');
    let show = seasonRows.first().is(':hidden');
    toggleLink.text(show ? "(Hide)" : "(Show)");
    seasonRows.toggle(show);
    
    jQuery.cookie('showDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function Get_Cookie() {
    return json.encode([((jQuery('#trailerbox').is(':hidden'))?'0':'1'),
                        ((jQuery('#castbox').is(':hidden'))?'0':'1'), 
                        ((jQuery('#showinfobox').is(':hidden'))?'0':'1'), 
                        ((jQuery('#coverbox').is(':hidden'))?'0':'1'),
				    ((jQuery('#fanartbox').is(':hidden'))?'0':'1'),
				    ((jQuery('#seasonbox').is(':hidden'))?'0':'1')
                        ]);
}

function Load_Show_Cookie()  {
 
	if(jQuery.cookie('showDetailsState') == undefined) {
		jQuery.cookie('showDetailsState', json.encode(['1', '1', '1']));
	}
	var state = json.decode( jQuery.cookie('showDetailsState') );
      
	if(state[0] == '0') {
		jQuery('#trailerbox').hide();
		jQuery('#trailertoggle').text('(Show)');
      } else 
		jQuery('#trailertoggle').text('(Hide)');
 
	if(state[1] == '0') {
		jQuery('#castbox').hide();
		jQuery('#casttoggle').text('(Show)');
      } else 
		jQuery('#casttoggle').text('(Hide)');

	if(state[2] == '0') {
		jQuery('#showinfobox').hide();
		jQuery('#showinfotoggle').text('(Show)');
      } else 
		jQuery('#showinfotoggle').text('(Hide)');    

	if(state[3] == '0') {
		jQuery('#coverbox').hide();
		jQuery('#covertoggle').text('(Show)');
      } else 
		jQuery('#covertoggle').text('(Hide)');
     
	if(state[4] == '0') {
		jQuery('#fanartbox').hide();
		jQuery('#fanarttoggle').text('(Show)');
      } else 
		jQuery('#fanarttoggle').text('(Hide)');
}

function Notify(label, shows, tvmazeid, callback) {
	ajax.get("torrents.php?action=notify_handle&label=" + label + "&shows=" + shows + "&auth=" + authkey + "&tvmazeid=" + tvmazeid, function() {
		if(callback) {
			callback();
		}
	});
}

function Unnotify(id, callback) {
	ajax.get("torrents.php?action=notify_delete&auth=" + authkey + "&id=" + id, function() {
		if(callback) {
			callback();
		}
	});
}

jQuery(function($) {
    $('.__notify-show').on('click', function(evt){
        evt.preventDefault();
        var bmEl = $(this);
        var label = bmEl.data('label');
        var shows = bmEl.data('shows');
        var tvmazeid = bmEl.data('tvmazeid');
        var id = bmEl.data('id');
        var icon = bmEl.find('.icon');

        if (!icon.hasClass('notified')) {
            Notify(label, shows, tvmazeid, function(){
                icon.addClass('notified');                
                if (useTooltipster) {
                    bmEl.tooltipster('content', 'Do not notify');
                } else {
                	  bmEl.attr('title', 'Do not notify')                	
                }            	
            });
        } else {
            Unnotify(id, function(){
                icon.removeClass('notified');
                if (useTooltipster) {
                    bmEl.tooltipster('content', 'Notify of new uploads');
                } else {
                	  bmEl.attr('title', 'Notify of new uploads')
                }            	
            });
        }
    });
});

function set_mouseover(id) {
  jQuery('#user_rating').val(id);
}

function clear_hover() {
  if (!jQuery('#removePersonalRating').is(":visible")) {
     jQuery('#user_rating').val('0');
  }   
  else {
     jQuery('#user_rating').val(jQuery('#prev_user_rating').val());
  }   
}

function update_data(showid) {

  setTimeout(function() { 
    ajax.get("torrents.php?action=calculate&showid=" + showid + "&auth=" + authkey, function(result) {
     var results = result.split("|");
     var avr = (parseFloat(results[0])).toFixed(1);
     if (avr==10) avr = 10;
     if (avr=='NaN') avr = 0;
     jQuery("#average_rating").val(avr);
     jQuery("#votes").val(results[1] + ' votes');
     
     jQuery("input[name='ratingS']").each(function(){
       if(jQuery(this).val() != "0"){
       jQuery(this).prop("checked",false);
       }
     });     

     jQuery("input[name='ratingS']").each(function(){
       if(jQuery(this).val() == Math.round(avr)){
       jQuery(this).prop("checked",true);
       }
     }); 
      
	  });
   }, 500);
}

function rate(showid,rating) {
  ajax.get("torrents.php?action=rate&showid=" + showid + "&auth=" + authkey + "&rating=" + rating);
  jQuery("#removePersonalRating").show();
  jQuery('#prev_user_rating').val(rating);
  update_data(showid);
}

jQuery(function($) {
    $('#removePersonalRating').on('click', function(evt) {
        evt.preventDefault();
        var bmEl = $(this);
        $('#user_rating').val('0');
        var showid = bmEl.data('showid');
        ajax.get("torrents.php?action=unrate&showid=" + showid + "&auth=" + authkey);  
        bmEl.hide();
        update_data(showid);
    });
});

function show_ratings(showid) {
	jQuery('#votes').blur();
   document.location = 'torrents.php?action=ratings&showid='+showid;
}

jQuery(function($) {
    $('#submitForm').on('click', function(evt) {
    x = confirm('This will delete DB entry and cache.\nAre you sure you want to reconstruct this show?');     	
    	if (x) {
        $('#submitForm').val('Reconstructing');
        $('#submitForm').setAttribute("disabled", "disabled");
      }
      else return false;  
    });
});

jQuery(function($) {
    $('#submitFormCache').on('click', function(evt) {
    	x = confirm('This will refresh show cache.\nAre you sure you want to refresh this show?');
    	if (x) {
    	  $('#action').val('refresh_cache');
        $('#submitFormCache').val('Refreshing...');
        $('#submitFormCache').setAttribute("disabled", "disabled");
      }
      else return false;
    });
});

function Follow(showid, callback) {
	ajax.get("torrents.php?action=follow_handle&auth=" + authkey + "&showid=" + showid, function() {
		if(callback) {
			callback();
		}
	});
}

function Unfollow(showid, callback) {
	ajax.get("torrents.php?action=follow_delete&auth=" + authkey + "&showid=" + showid, function() {
		if(callback) {
			callback();
		}
	});
}

jQuery(function($) {
    $('.__fav-show').on('click', function(evt){ 
        evt.preventDefault();
        var bmEl = $(this);
        var icon = bmEl.find('.icon');
        var showid = bmEl.data('favtvmazeid');
        
        if (icon.hasClass('followed')) {
            Unfollow(showid, function(){
                icon.removeClass('followed');                
                if (useTooltipster) {
                    bmEl.tooltipster('content', 'Follow');
                } else {
                	  bmEl.attr('title', 'Follow')                	
                }
          
            });
        } else {
            Follow(showid, function(){
                icon.addClass('followed');
                if (useTooltipster) {
                    bmEl.tooltipster('content', 'Following');
                } else {
                	  bmEl.attr('title', 'Following')
                }  
            });
        }
    });
});

jQuery(function($) {
    $('#refrefhTVMazeRating').on('click', function(evt) {
        evt.preventDefault();
        var bmEl = $(this);
        var showid = bmEl.data('showid');
        var rating = 0;
        $.get(`//api.tvmaze.com/shows/${showid}`, insertData);        
        function insertData(res){
           if (res.rating.average) rating = res.rating.average;
           $('#tvmaze_rating').val(rating);
           ajax.get("torrents.php?action=refreshrating&showid=" + showid + "&auth=" + authkey + "&rating=" + rating);
        }
        bmEl.hide();
        
    });
});

jQuery(function($) {
    $('#edit_button').on('click', function(evt) {
    $('#action').val('editshow');
    $('#edit_button').val('Loading...');
    $('#edit_button').setAttribute("disabled", "disabled");
    });
    jQuery('.seasontoggle').on('click', Season_Toggle);
});

addDOMLoadEvent(Load_Show_Cookie);
