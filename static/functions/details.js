

function Add_Tag(){
    // now as an ajax call... better user feedback
    if ( $('#tagname').raw().value =='') return false;
    ajax.post('torrents.php?action=add_tag', 'form_addtag', function (response) { 
            display_tag_response(response);
    });
    $('#tagname').raw().value ='';
    return false;
}

function Del_Tag(tagid, groupid, tagsort){
    
    var ToPost = [];
    ToPost['tagid'] = tagid; 
    ToPost['groupid'] = groupid;  
    ToPost['tagsort'] = tagsort;  
    ToPost['auth'] = authkey; 
    ajax.post('torrents.php?action=delete_tag', ToPost, function (response) { 
        display_tag_response(response); 
    });
    return false;
}

var sort_types = new Array("uses","score","az","added");
var sort_type = 'uses';
var sort_order = 'desc';
function Resort_Tags(groupid, tagsort, order) {
    if (!in_array(tagsort, sort_types, false)) tagsort = 'uses'
    sort_type = tagsort;
	if(order == undefined || (order!='asc' && order!='desc')) {
        //alert(order);
        sort_order = (sort_order=='desc')?'asc':'desc';
        //alert(sort_order);
    } else {
        sort_order = order;
    }
    var ToPost = [];
    ToPost['groupid'] = groupid;  
	ToPost['tagsort'] = sort_type;  
	ToPost['order'] = sort_order;  
	ToPost['auth'] = authkey; 
    ajax.post('torrents.php?action=resort_tags', ToPost, function (response) { 
        var x = json.decode(response);  
        if ( is_array(x)){
            $('#torrent_tags').html(x[0]);
            var i=0;
            for (i=0;i<4;i++) {
                if( sort_types[i]==sort_type) $('#sort_' + sort_types[i]).add_class("sort_select");
                else $('#sort_' + sort_types[i]).remove_class("sort_select");
            }
            Write_Tagsort_Cookie();
        } else { 
            alert('unforseen error :('); 
        }
        jQuery('.stats a').tooltipster();
    });
    return false;
}

function display_tag_response(response){
    
    var x = json.decode(response);  
    if ( is_array(x)){
        if ( !is_array(x[0])){
            alert('unforseen error :(');
        } else { 
            jQuery(".rmv").remove();
            var len = x[0].length;
            for(var i = 0; i < len; i++) {
                var xtrclass = x[0][i][0]==0?' alert' : ''; // +x[0][i][0]; (numMsgs++)
                jQuery("#messagebar").before('<div id="messagebar'+i+'" class="rmv messagebar'+xtrclass+'" title="'+ x[0][i][1]+'">'+ x[0][i][1]+'</div>');
            }
            //$('#messagebar'+displayID).raw().scrollIntoView(false);
        }
        if (x[1] != 0) $('#torrent_tags').html(x[1]);
                
    } else { // a non array == an error 
        $('#messagebar').add_class('alert');
        $('#messagebar').html(x);
        $('#messagebar').show(); 
    }
    //$('#tags').raw().scrollIntoView();
}

function Vote_Tag(tagname, tagid, groupid, way){
 
	  var ToPost = [];
	  ToPost['tagid'] = tagid; 
	  ToPost['groupid'] = groupid; 
	  ToPost['way'] = way; 
	  ToPost['auth'] = authkey; 
        ajax.post('torrents.php?action=vote_tag', ToPost, function (response) { 
            var x = json.decode(response); 
            if ( is_array(x)){
                if(x[0]==0){    // already voted so no vote
                    $('#messagebar').add_class('alert');
                } else {        // vote was counted
                    $('#messagebar').remove_class('alert');
                    var score = parseInt( $('#tagscore' + tagid).raw().innerHTML) + x[0];
                    if (score<0) // remove negative scores (they are already removed from the db)
                        jQuery('#tlist' + tagid).remove();
                    else // update with new vote score
                        $('#tagscore' + tagid).html(score);
                }
                $('#messagebar').html(x[1] +tagname);
            } else { // a non array == an error 
                $('#messagebar').add_class('alert');
                $('#messagebar').html(x);
            }
            $('#messagebar').raw().title=$('#messagebar').raw().innerHTML;
            $('#messagebar').show(); 
            //$('#messagebar').raw().scrollIntoView();
        });
        return false;
}

function Send_Okay_Message(group_id, conv_id){
    if(conv_id==0) conv_id = null;
    if (confirm("Make sure you have really fixed the problem before sending this message!\n\nAre you sure it is fixed?")){
        
	  var ToPost = [];
	  ToPost['groupid'] = group_id; 
	  ToPost['auth'] = authkey; 
	  if (conv_id) ToPost['convid'] = conv_id; 
        ajax.post('?action=send_okay_message', ToPost, function (response) {
            // show  user response 
            conv_id = response;
            $('#user_message').raw().innerHTML = '<div class="messagebar"><a href="staffpm.php?action=viewconv&id=' + conv_id + '">Message sent to staff</a></div>';
            $('#convid').raw().value = conv_id;
        });
    }
    return false;
}

function Validate_Form_Reviews(status){
    if(status == 'Warned' || status== 'Pending'){
        return confirm("Are you sure you want to override the warning already in process?"); 
    } else if(status == 'Okay'){
        return confirm("Are you sure you want to override the okay status?"); 
    }
    return true;
}

function Select_Reason(overwrite_warn){ 
   
    var value = $('#reasonid').raw().value;
    //if reason == -1 (not set)
    if(value == -1){
        $('#mark_delete_button').disable(); 
        $('#review_message').hide(); 
        $('#warn_insert').html('');
    } else { // is set
	  var ToPost = [];
	  ToPost['groupid'] = $('#groupid').raw().value;
	  ToPost['reasonid'] = $('#reasonid').raw().value;
        ajax.post('?action=get_review_message', ToPost, function (response) {
            //enable button and show pm response
            $('#mark_delete_button').disable(false); 
            //if reason == other then show textarea
            if (value == 0 )$('#reason_other').show();
            else $('#reason_other').hide();
            $('#message_insert').raw().innerHTML = response;
            $('#review_message').show(); 
            if (overwrite_warn){
                $('#warn_insert').html("Are you sure you want to override the current status?");
            }
        });
    }
    return false;
}

function Tools_Toggle() {
    if ($('#slide_tools_button').raw().innerHTML=='Hide Tools'){
        jQuery.cookie('torrentDetailsToolState', 'collapsed', { expires: 100 });
        $('#slide_tools_button').raw().innerHTML=('Show Tools');
        jQuery('#staff_tools').hide();
                            
    } else{
        jQuery.cookie('torrentDetailsToolState', 'expanded', { expires: 100 });
        $('#slide_tools_button').raw().innerHTML=('Hide Tools');
        jQuery('#staff_tools').show();
    }
    return false;
}


function Load_Tools_Cookie()  {
	var panel = jQuery('#staff_tools');
	var button = jQuery('#slide_tools_button');
    
	if(jQuery.cookie('torrentDetailsToolState') == undefined) {
		jQuery.cookie('torrentDetailsToolState', 'expanded', { expires: 100 });
	}
	if(jQuery.cookie('torrentDetailsToolState') == 'collapsed') {
		panel.hide();
		button.text('Show Tools');
	} else {
		button.text('Hide Tools');
      }
}


function Details_Toggle() {
    //var state = new Array();
    //state[1]=((jQuery('#coverimage').is(':hidden'))?'0':'1');
    //state[2]=((jQuery('#tag_container').is(':hidden'))?'0':'1');
    
    jQuery('#details_top').slideToggle('700', function(){
            
        if (jQuery('#details_top').is(':hidden')) 
            jQuery('#slide_button').html('Show Info'); 
        else
            jQuery('#slide_button').html('Hide Info');
            
        //state[0]=((jQuery('#details_top').is(':hidden'))?'0':'1');
        //jQuery.cookie('torrentDetailsState', json.encode(state), { expires: 100 });
        
        jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
   
    });
    return false;
}

function Cover_Toggle() {

    jQuery('#coverimage').toggle();
 
    if (jQuery('#coverimage').is(':hidden')) 
        jQuery('#covertoggle').html('(Show)');
    else  
        jQuery('#covertoggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function TagBox_Toggle() {

    jQuery('#tag_container').toggle();
 
    if (jQuery('#tag_container').is(':hidden')) 
        jQuery('#tagtoggle').html('(Show)');
    else  
        jQuery('#tagtoggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function Desc_Toggle() {

    jQuery('#descbox').toggle();
 
    if (jQuery('#descbox').is(':hidden')) 
        jQuery('#desctoggle').html('(Show)');
    else  
        jQuery('#desctoggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

/*
function Info_Toggle(cluster) {

    jQuery('#' + cluster + 'box').toggle();
 
    if (jQuery('#' + cluster + 'box').is(':hidden')) 
        jQuery('#' + cluster + 'toggle').html('(Show)');
    else  
        jQuery('#' + cluster + 'toggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}
*/
function BuyFL_Toggle() {

    jQuery('#donatediv').toggle();
 
    if (jQuery('#donatediv').is(':hidden')) 
        jQuery('#donatebutton').html('(Show)');
    else  
        jQuery('#donatebutton').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function MediaInfo_Toggle() {

    jQuery('#mediainfobox').toggle();
 
    if (jQuery('#mediainfobox').is(':hidden')) 
        jQuery('#mediainfotoggle').html('(Show)');
    else  
        jQuery('#mediainfotoggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function EpisodeInfo_Toggle() {

    jQuery('#episodeinfobox').toggle();
 
    if (jQuery('#episodeinfobox').is(':hidden')) 
        jQuery('#episodeinfotoggle').html('(Show)');
    else  
        jQuery('#episodeinfotoggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function ShowInfo_Toggle() {

    jQuery('#seasoninfobox').toggle();
 
    if (jQuery('#seasoninfobox').is(':hidden')) 
        jQuery('#seasoninfotoggle').html('(Show)');
    else  
        jQuery('#seasoninfotoggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function Screens_Toggle() {

    jQuery('#screensbox').toggle();
 
    if (jQuery('#screensbox').is(':hidden')) 
        jQuery('#screenstoggle').html('(Show)');
    else  
        jQuery('#screenstoggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}


function Trailer_Toggle() {

    jQuery('#trailerbox').toggle();
 
    if (jQuery('#trailerbox').is(':hidden')) 
        jQuery('#trailertoggle').html('(Show)');
    else  
        jQuery('#trailertoggle').html('(Hide)');
            
    jQuery.cookie('torrentDetailsState', Get_Cookie(), { expires: 100 });
    return false;
}

function Get_Cookie() {
    return json.encode([((jQuery('#details_top').is(':hidden'))?'0':'1'), 
                        ((jQuery('#coverimage').is(':hidden'))?'0':'1'), 
                        ((jQuery('#tag_container').is(':hidden'))?'0':'1'), 
                        ((jQuery('#donatediv').is(':hidden'))?'0':'1'),
                        ((jQuery('#descbox').is(':hidden'))?'0':'1'), 
                        ((jQuery('#mediainfobox').is(':hidden'))?'0':'1'),
                        ((jQuery('#episodeinfobox').is(':hidden'))?'0':'1'),                      
                        ((jQuery('#seasoninfobox').is(':hidden'))?'0':'1'),                      
                        ((jQuery('#screensbox').is(':hidden'))?'0':'1'),                      
                        ((jQuery('#trailerbox').is(':hidden'))?'0':'1')                      
                        ]);
}


function Write_Tagsort_Cookie() {
    jQuery.cookie('tagsort', json.encode([sort_type, sort_order]), { expires: 100 });
}

function Load_Tagsort_Cookie()  {
	if(jQuery.cookie('tagsort') == undefined) {
		jQuery.cookie('tagsort', json.encode(['uses', 'desc']));
	}
	var state = json.decode( jQuery.cookie('tagsort') );
    Resort_Tags( $('#sort_groupid').raw().value , state[0], state[1]);
}


function Load_Details_Cookie()  {
 
	// the div that will be hidden/shown
	var panel = jQuery('#details_top');
	var button = jQuery('#slide_button');
    
	if(jQuery.cookie('torrentDetailsState') == undefined) {
		jQuery.cookie('torrentDetailsState', json.encode(['1', '1', '1', '1','1','1','1','1','1','1']));
	}
	var state = json.decode( jQuery.cookie('torrentDetailsState') );
      
	if(state[0] == '0') {
		panel.hide();
		button.text('Show Info');
	} else
		button.text('Hide Info');
      
	if(state[1] == '0') {
		jQuery('#coverimage').hide();
		jQuery('#covertoggle').text('(Show)');
      } else 
		jQuery('#covertoggle').text('(Hide)');
 
	if(state[2] == '0') {
		jQuery('#tag_container').hide();
		jQuery('#tagtoggle').text('(Show)');
      } else 
		jQuery('#tagtoggle').text('(Hide)');
    
	if(state[3] == '0') {
		jQuery('#donatediv').hide();
		jQuery('#donatebutton').text('(Show)');
      } else 
		jQuery('#donatebutton').text('(Hide)');
    
	if(state[4] == '0') {
		jQuery('#descbox').hide();
		jQuery('#desctoggle').text('(Show)');
      } else 
		jQuery('#desctoggle').text('(Hide)');

	if(state[5] == '0') {
		jQuery('#mediainfobox').hide();
		jQuery('#mediainfotoggle').text('(Show)');
      } else 
		jQuery('#mediainfotoggle').text('(Hide)');		

	if(state[6] == '0') {
		jQuery('#episodeinfobox').hide();
		jQuery('#episodeinfotoggle').text('(Show)');
      } else 
		jQuery('#episodeinfotoggle').text('(Hide)');

	if(state[7] == '0') {
		jQuery('#seasoninfobox').hide();
		jQuery('#seasoninfotoggle').text('(Show)');
      } else 
		jQuery('#seasoninfotoggle').text('(Hide)');

	if(state[8] == '0') {
		jQuery('#screensbox').hide();
		jQuery('#screenstoggle').text('(Show)');
      } else 
		jQuery('#screenstoggle').text('(Hide)');

	if(state[9] == '0') {
		jQuery('#trailerbox').hide();
		jQuery('#trailertoggle').text('(Show)');
      } else 
		jQuery('#trailertoggle').text('(Hide)');
}
 
 function Say_Thanks() {
    $('#thanksbutton').raw().disabled=true;
    ajax.post("torrents.php?action=thank","thanksform", function (response) {
        if(response=='err'){
            alert('Error: GroupID not set!');
        } else {
            if($('#thankstext').raw().innerHTML!='') response = ', ' + response;
            $('#thankstext').raw().innerHTML += response;
            $('#thanksdigest').raw().innerHTML = 'The following '+$('#thankstext').raw().innerHTML.split(' ').length+' people said thanks!';
            $('#thanksform').hide();
            $('#thanksdiv').show();
        }
    });
 }

/* Torrent Details:  Show various tables etc dynamically */

function show_peers (TorrentID, Page) {
	if(Page>0) {
		ajax.get('torrents.php?action=peerlist&page='+Page+'&torrentid=' + TorrentID,function(response){
			$('#peers_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#peers_' + TorrentID).raw().innerHTML === '') {
			$('#peers_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=peerlist&torrentid=' + TorrentID,function(response){
				$('#peers_' + TorrentID).show().raw().innerHTML=response;
			});
		} else {
			$('#peers_' + TorrentID).toggle();
		}
	}
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_snatches (TorrentID, Page){
	if(Page>0) {
		ajax.get('torrents.php?action=snatchlist&page='+Page+'&torrentid=' + TorrentID,function(response){
			$('#snatches_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#snatches_' + TorrentID).raw().innerHTML === '') {
			$('#snatches_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=snatchlist&torrentid=' + TorrentID,function(response){
				$('#snatches_' + TorrentID).show().raw().innerHTML=response;
			});
		} else {
			$('#snatches_' + TorrentID).toggle();
		}
	}
	$('#peers_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_downloads (TorrentID, Page){
	if(Page>0) {
		ajax.get('torrents.php?action=downloadlist&page='+Page+'&torrentid=' + TorrentID,function(response){
			$('#downloads_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#downloads_' + TorrentID).raw().innerHTML === '') {
			$('#downloads_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=downloadlist&torrentid=' + TorrentID,function(response){
				$('#downloads_' + TorrentID).raw().innerHTML=response;
			});
		} else {
			$('#downloads_' + TorrentID).toggle();
		}
	}
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_files(TorrentID){
	$('#files_' + TorrentID).toggle();
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_reported(TorrentID){
	$('#files_' + TorrentID).hide();
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#reported_' + TorrentID).toggle();
}


 

jQuery(function($) {
    $('.__bookmark-torrent').on('click', function(evt){
        evt.preventDefault();
        var bmEl = $(this);
        var torrentID = bmEl.data('torrentid');
        var bookmarked = typeof bmEl.attr('data-bookmarked') !== typeof undefined && bmEl.attr('data-bookmarked') !== false;
        if (!bookmarked) {
            Bookmark('torrent', torrentID, '', function(){
                bmEl.attr('data-bookmarked', '1');
            });
            bmEl.text('Remove Bookmark');
        } else {
            Unbookmark('torrent', torrentID, '', function(){
                bmEl.removeAttr('data-bookmarked');
            });
            bmEl.text('Bookmark');
        }
    });
});
            
addDOMLoadEvent(Load_Details_Cookie);
addDOMLoadEvent(Load_Tagsort_Cookie);

function toggleDisplay(node) {
   node.style.display = node.style.display === 'none' ? '' : 'none';
}
