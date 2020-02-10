
jQuery(function($) {
    $('#submitForm').on('click', function(evt) {
    x = confirm('This will delete DB entry and cache.\nAre you sure you want to reconstruct this person?');     	
    	if (x) {
        $('#submitForm').val('Reconstructing');
        $('#submitForm').setAttribute("disabled", "disabled");
      }
      else return false;  
    });
});

jQuery(function($) {
    $('#submitFormCache').on('click', function(evt) {
    	x = confirm('This will refresh person cache.\nAre you sure you want to refresh this person?');
    	if (x) {
    	  $('#action').val('refresh_person_cache');
        $('#submitFormCache').val('Refreshing...');
        $('#submitFormCache').setAttribute("disabled", "disabled");
      }
      else return false;
    });
});

function Notify(label, person, personid, callback) {
	ajax.get("torrents.php?action=notify_handle&label=" + label + "&people=" + person + "&auth=" + authkey + "&personid=" + personid, function() {
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
    $('.__notify-person').on('click', function(evt){
        evt.preventDefault();
        var bmEl = $(this);
        var label = bmEl.data('label');
        var person = bmEl.data('person');
        var personid = bmEl.data('personid');
        var id = bmEl.data('id');
        var icon = bmEl.find('.icon');

        if (!icon.hasClass('notified')) {
            Notify(label, person, personid, function(){
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
