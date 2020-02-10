

function add_tag(tag) {
    if ($('#tags').raw().value == "") {
        $('#tags').raw().value = tag;
    } else {
        $('#tags').raw().value = $('#tags').raw().value + " " + tag;
    }
    CursorToEnd($('#tags').raw());
}
function CursorToEnd(textarea){ 
     // set the cursor to the end of the text already present
    if (textarea.setSelectionRange) { // ff/chrome/opera
        var len = textarea.value.length * 2; //(*2 for opera stupidness)
        textarea.setSelectionRange(len, len);
    } else { // ie8-, fails in chrome
        textarea.value = textarea.value;
    }
}

function Load_Cookie()  {
			 
	if(jQuery.cookie('searchPanelState') == undefined) {
		jQuery.cookie('searchPanelState', 'expanded', { expires: 100 });
	}
	//var state = jQuery.cookie('searchPanelState');
      
	if(jQuery.cookie('searchPanelState') == 'collapsed') {
		jQuery('#search_box').hide();
		jQuery('#search_button').text('Open Search Center');
	} else {
		jQuery('#search_button').text('Close Search Center');
      }
}
		
 
function Panel_Toggle() { 
    jQuery('#search_box').slideToggle('slow', function() { 
        if(jQuery.cookie('searchPanelState') == 'expanded') {
            jQuery.cookie('searchPanelState', 'collapsed', { expires: 100 });
            jQuery('#search_button').text('Open Search Center');
        } else {
            jQuery.cookie('searchPanelState', 'expanded', { expires: 100 });
            jQuery('#search_button').text('Close Search Center');
        }
    });
    return false;
}

function show_delete() {
    jQuery('#torrent_table').find('input:checked').parents('tr').addClass("delbar");
    jQuery('#torrent_table').find('input:not(:checked)').parents('tr').removeClass("delbar");
}

function show_hnr() {
    jQuery('#torrent_table').find('input:checked').parents('tr').addClass("delbar");
    jQuery('#torrent_table').find('input:not(:checked)').parents('tr').removeClass("delbar");
}

function show_collage(ShowName) {
    $('#batchadd').raw().value = ShowName;
}

function toggle_delete_all() {
    jQuery('input[name=delete_select\\[\\]]').prop('checked', jQuery('#delete_all').is(':checked'));
    show_delete()
    
    if (jQuery('input[name=delete_select\\[\\]]').prop('checked')) { // select all
    	del_torrents = jQuery('#torrent_table').find('input:checked');
//    	if(del_torrents.length > 0) 
//        torrent_id =  '#showname_' + del_torrents.first().get(0).value; // get first one
//        $('#batchadd').raw().value = $(torrent_id).raw().innerHTML;
    }    
    else { 
    	$('#batchadd').raw().value = ''; // deselect
    }
}

function toggle_hnr_all() {
    jQuery('input[name=hnr_select\\[\\]]').prop('checked', jQuery('#hnr_all').is(':checked'));
    show_hnr()
    
    if (jQuery('input[name=hnr_select\\[\\]]').prop('checked')) { // select all
    	hnr_torrents = jQuery('#torrent_table').find('input:checked');
    }    
}

function do_mass_delete() {
    del_torrents = jQuery('#torrent_table').find('input:checked');
    if(del_torrents.length > 0) {
        torrent_ids =  del_torrents.map(function() {return this.value;}).get();
        r = confirm('Torrents with IDs: ' + torrent_ids.join(', ') + ' will be deleted!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'takemassdelete');
            jQuery('#delform').submit();
        }
    } else {
        alert('Select at least 1 torrent.');
    }
}

function do_mass_collage() {
    del_torrents = jQuery('#torrent_table').find('input:checked');
    if(del_torrents.length > 0) {
        torrent_ids =  del_torrents.map(function() {return this.value;}).get();
        r = confirm('Torrents with IDs: ' + torrent_ids.join(', ') + ' will be added to Collage `' + $('#batchadd').raw().value + '`!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'add_torrent_batch');
            jQuery('#delform').submit();
        }
    } else {
        alert('Select at least 1 torrent.');
    }
}

function do_mass_scrape() {
    del_torrents = jQuery('#torrent_table').find('input:checked');
    if(del_torrents.length > 0 && $('#tvmazeid').raw().value != '') {
        torrent_ids =  del_torrents.map(function() {return this.value;}).get();
        r = confirm('Torrents with IDs: ' + torrent_ids.join(', ') + ' will be added scraped with TVMaze ID `' + $('#tvmazeid').raw().value + '`!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'scrape_torrents');
            jQuery('#delform').submit();
        }
    } else {
        alert('Select at least 1 torrent & / Enter TVMaze ID.');
    }
}

function do_mass_doubleseed() {
    ds_torrents = jQuery('#torrent_table').find('input:checked');
    if(ds_torrents.length > 0) {
        torrent_ids =  ds_torrents.map(function() {return this.value;}).get();
        r = confirm('Torrents with IDs: ' + torrent_ids.join(', ') + ' will be Doubleseeded!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'takemassdoubleseed');
            jQuery('#delform').submit();
        }
    } else {
        alert('Select at least 1 torrent.');
    }
}

function undo_mass_doubleseed() {
    ds_torrents = jQuery('#torrent_table').find('input:checked');
    if(ds_torrents.length > 0) {
        torrent_ids =  ds_torrents.map(function() {return this.value;}).get();
        r = confirm('Torrents with IDs: ' + torrent_ids.join(', ') + ' will be UnDoubleseeded!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'takemassundoubleseed');
            jQuery('#delform').submit();
        }
    } else {
        alert('Select at least 1 torrent.');
    }
}

function do_mass_freeleech() {
    fl_torrents = jQuery('#torrent_table').find('input:checked');
    if(fl_torrents.length > 0) {
        torrent_ids =  fl_torrents.map(function() {return this.value;}).get();
        r = confirm('Torrents with IDs: ' + torrent_ids.join(', ') + ' will be Freeleeched!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'takemassfreeleech');
            jQuery('#delform').submit();
        }
    } else {
        alert('Select at least 1 torrent.');
    }
}

function undo_mass_freeleech() {
    fl_torrents = jQuery('#torrent_table').find('input:checked');
    if(fl_torrents.length > 0) {
        torrent_ids =  fl_torrents.map(function() {return this.value;}).get();
        r = confirm('Torrents with IDs: ' + torrent_ids.join(', ') + ' will be UnFreeleeched!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'takemassunfreeleech');
            jQuery('#delform').submit();
        }
    } else {
        alert('Select at least 1 torrent.');
    }
}

function do_mass_download() {
    fl_torrents = jQuery('#torrent_table').find('input:checked');
    if(fl_torrents.length > 0) {
        torrent_ids =  fl_torrents.map(function() {return this.value;}).get();
        r = confirm('Torrents with IDs: ' + torrent_ids.join(', ') + ' will be downloaded!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'takemassdownload');
            jQuery('#delform').submit();
        }
    } else {
        alert('Select at least 1 torrent.');
    }
}

function do_mass_hnr() {
    fl_torrents = jQuery('#torrent_table').find('input:checked');
    if(fl_torrents.length > 0) {
        torrent_ids =  fl_torrents.map(function() {return this.value;}).get();
        r = confirm('HnR for torrents with IDs: ' + torrent_ids.join(', ') + ' will be cleared!\nAre you sure?');
        if(r == true) {
            jQuery('#action').attr('value', 'clearhnr');
            jQuery('#hnrform').submit();
        }
    } else {
        alert('Select at least 1 torrent.');
    }
}

jQuery(function(){show_delete();});

addDOMLoadEvent(Load_Cookie);
