function SynchInterface(){
    change_tagtext();
    resize('tags');
}

function Cover_Toggle() {

    jQuery('#coverimage').toggle();
 
    if (jQuery('#coverimage').is(':hidden')) 
        jQuery('#covertoggle').html('(Show)');
    else  
        jQuery('#covertoggle').html('(Hide)');
            
    jQuery.cookie('requestDetailsState', Get_Cookie());
    return false;
}

function TagBox_Toggle() {

    jQuery('#tag_container').toggle();
 
    if (jQuery('#tag_container').is(':hidden')) 
        jQuery('#tagtoggle').html('(Show)');
    else  
        jQuery('#tagtoggle').html('(Hide)');
            
    jQuery.cookie('requestDetailsState', Get_Cookie());
    return false;
}

function Desc_Toggle() {

    jQuery('#descbox').toggle();
 
    if (jQuery('#descbox').is(':hidden')) 
        jQuery('#desctoggle').html('(Show)');
    else  
        jQuery('#desctoggle').html('(Hide)');
            
    jQuery.cookie('requestDetailsState', Get_Cookie());
    return false;
}

function Get_Cookie() {
    return json.encode([((jQuery('#coverimage').is(':hidden'))?'0':'1'), 
                        ((jQuery('#tag_container').is(':hidden'))?'0':'1'), 
                        ((jQuery('#descbox').is(':hidden'))?'0':'1')]);
}

function Load_Details_Cookie()  {
  
    
	if(jQuery.cookie('requestDetailsState') == undefined) {
		jQuery.cookie('requestDetailsState', json.encode(['1', '1','1']));
	}
	var state = json.decode( jQuery.cookie('requestDetailsState') );
    
	if(state[0] == '0') {
		jQuery('#coverimage').hide();
		jQuery('#covertoggle').text('(Show)');
      } else 
		jQuery('#covertoggle').text('(Hide)');
 
	if(state[1] == '0') {
		jQuery('#tag_container').hide();
		jQuery('#tagtoggle').text('(Show)');
      } else 
		jQuery('#tagtoggle').text('(Hide)');
 
	if(state[2] == '0') {
		jQuery('#descbox').hide();
		jQuery('#desctoggle').text('(Show)');
      } else 
		jQuery('#desctoggle').text('(Hide)');
     
}
 

function Preview_Request() {
	if ($('#preview').has_class('hidden')) {
		var ToPost = [];
		ToPost['body'] = $('#quickcomment').raw().value;
		ajax.post('ajax.php?action=preview', ToPost, function (data) {
			$('#preview').raw().innerHTML = data;
			$('#preview').toggle();
			$('#editor').toggle();
			$('#previewbtn').raw().value = "Edit";
		});
	} else {
		$('#preview').toggle();
		$('#editor').toggle();
		$('#previewbtn').raw().value = "Preview";
	}
}

function ReadableAmount(size) {
    var units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    var i = 0;
    while(size >= 1024) {
        size /= 1024;
        ++i;
    }
    return size.toFixed(1) + ' ' + units[i];
}


// used on the requests page and user profile
function VotePromptMB(requestid) {
	if(!requestid) return; // error
    var amount = prompt("Please enter the amount in Cubits you want to add to the bounty\nmin vote: 100 Cubits\nmax vote 10,000 Cubits", 100);
    if(!amount || amount==0) return;
    if(amount < 100 ) amount = 100 ;
    if(amount > 10000 ) amount = 10000; // max vote 10k from this prompt
    
    if (!confirm(amount + ' Cubits will immediately be removed from your Cubits total, are you sure?')) return;

	ajax.get('requests.php?action=takevote&id=' + requestid + '&auth=' + $('#auth').raw().value + '&amount=' + amount, function (response) {
			
			var x = json.decode(response);
            
            if(!is_array(x)) {  // unexpected error
				alert("Error processing vote: " + response ); 
                return;
            }
            amount = x[1];      // amount 
            
            if(x[0] == 'bankrupt') {    // failed to vote
				alert("You do not have sufficient Cubits credit to add " + amount + " Cubits to this request");
				return;
            } else if (x[0] == 'success') {
				//votecount.innerHTML = (parseInt(votecount.innerHTML)) + 1;
			}
            // now we get all values from ajax, means page always stays internally consistent even with paralell voting
            $('#vote_count_'+requestid).html( x[3]);
			$('#bounty_'+requestid).html(x[2]);
             
		}
	);
    
}

// used on request page
function Vote(amount, requestid) {
	if(typeof amount == 'undefined') {
		amount = parseInt($('#amount').raw().value);
	}
    
	if(amount == 0) amount = 500;
	else if(amount < 100) amount = 100;
   
    if (!confirm(amount + ' Cubits will immediately be removed from your Cubits total, are you sure?')) return;
    
	//var index;
	var votecount;
	if(!requestid) { // used on request page
		requestid = $('#requestid').raw().value;
		votecount = $('#votecount').raw();
		//index = false;
	} else {        // used on requests browse page
		votecount = $('#vote_count_' + requestid).raw();
		//bounty = $('#bounty_' + requestid).raw();
		//index = true;
	}

	ajax.get('requests.php?action=takevote&id=' + requestid + '&auth=' + $('#auth').raw().value + '&amount=' + amount, function (response) {
			
			var x = json.decode(response);
            
            if(!is_array(x)) {  // unexpected error
				error_message("Error processing vote: " + response ); 
                return;
            }
            amount = x[1];      // amount 
            
            if(x[0] == 'bankrupt') {    // failed to vote
				error_message("You do not have sufficient Cubits credit to add " + amount + " Cubits to this request");
				return;
            } else if (x[0] == 'success') {
				//votecount.innerHTML = (parseInt(votecount.innerHTML)) + 1;
			}
            // now we get all values from ajax, means page always stays internally consistent even with parallel voting
            votecount.innerHTML = x[3];
            var startBounty = x[2] - x[1];  // parseInt($('#total_bounty').raw().value);
			var totalBounty = x[2];         // startBounty + parseInt(amount);
			$('#total_bounty').raw().value = totalBounty;
			$('#formatted_bounty').raw().innerHTML = totalBounty + ' Cubits';

			save_message("Bounty was " + startBounty + " Cubits + your vote of " + amount + " Cubits = Total Bounty: " + totalBounty + ' Cubits');
			$('#button_vote').raw().disabled = true;
            
            if (x[4]!==false) {
                $('#request_votes').html(x[4]);
            }
		}
	);
}

function Calculate() {

    var amt = parseInt($('#amount_box').raw().value);

    if(amt > parseInt($('#current_credits').raw().value)) {
		$('#new_points').raw().innerHTML = "You can't afford that request!";
		$('#new_bounty').raw().innerHTML = "0";
		$('#button_vote').raw().disabled = true;
	} else if(isNaN($('#amount_box').raw().value)
			|| (window.location.search.indexOf('action=new') != -1 && $('#amount_box').raw().value < 500)
			|| (window.location.search.indexOf('action=view') != -1 && $('#amount_box').raw().value < 100)) {
		$('#new_points').raw().innerHTML = ($('#current_credits').raw().value);
		$('#new_bounty').raw().innerHTML = "0";
		$('#button_vote').raw().disabled = true;
	} else {
		$('#button_vote').raw().disabled = false;
		$('#amount').raw().value = amt;
		$('#new_points').raw().innerHTML =($('#current_credits').raw().value - amt);
		$('#new_bounty').raw().innerHTML =  $('#amount_box').raw().value;
      $('#inform').raw().innerHTML = value + ' Cubits will immediately be removed from your Cubits total.';
	}
}
 

function add_tag() {
	if ($('#tags').raw().value == "") {
		$('#tags').raw().value = $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	} else if ($('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value == "---") {
	} else {
		$('#tags').raw().value = $('#tags').raw().value + ", " + $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	}
}

function Toggle(id, disable) {
	var arr = document.getElementsByName(id + '[]');
	var master = $('#toggle_' + id).raw().checked;
	for (var x in arr) {
		arr[x].checked = master;
		if(disable == 1) {
			arr[x].disabled = master;
		}
	}
	
	if(id == "formats") {
		ToggleLogCue();
	}
}

function ToggleLogCue() {
	var formats = document.getElementsByName('formats[]');
	var flac = false;
	
	if(formats[1].checked) {
		flac = true;
	}
	
	if(flac) {
		$('#logcue_tr').show();
	} else {
		$('#logcue_tr').hide();
	}
	ToggleLogScore();
}

function ToggleLogScore() {
	if($('#needlog').raw().checked) {
		$('#minlogscore_span').show();
	} else {
		$('#minlogscore_span').hide();
	}
}

jQuery(function($) {
    'use strict';
    $('#tvmazeload').on('click', getinfo);
    function getinfo(evt){
        evt.preventDefault();

        var tvmaze = $('#tvmaze').val();
        tvmaze = parseInt(tvmaze);
        
        if(tvmaze === "0" || !tvmaze){
        	   $('#messagebar').text('Invalid TVMaze ID!');
        	   $('#messagebar').removeClass('hidden');
            return false;
        }

        if($("#category option:selected").text() === "---"){
        	   $('#messagebar').text('Select a Category!');
        	   $('#messagebar').removeClass('hidden');
            return false;
        }

        var tvmazeid = 1;
        if ($.isNumeric(tvmaze)){
            tvmazeid = tvmaze;
        } else {
            tvmazeid = tvmaze.match(/\/shows\/([0-9]*)\/?/)[1];
        }
        $.get(`//api.tvmaze.com/shows/${tvmazeid}`, insertData).fail(function(){ 
        	   $('#messagebar').text('Invalid TVMaze ID!');
        	   $('#messagebar').removeClass('hidden');
            return false;
        });
    }
    function insertData(res){

        $('#messagebar').addClass('hidden');
        $('#tvmaze').prop('readonly', true);
        $('#tvmazeload').prop('disabled', true);
    	
        $('#title').val(res.name);
        $('#title').prop('readonly', true);
        $('#title_wrap').css('display','table-row');
        // Start tag getting
        var tags = [];

        tags.push($("#category option:selected").text()); // add category

        tags = tags.concat(res.genres);
        if (res.language !== 'English') {
           tags.push(res.language);
        }

        tags.push(res.type);

        // Format network
        if (res.webChannel) {
            tags.push(res.webChannel.name.replace(/ /, '.'));
        } else if (res.network){
            tags.push(res.network.name.replace(/ /, '.'));
        }        
        tags = tags.join(', ').toLowerCase();
        
        $('#tags_wrapper').css('display','table-row');
        
        $('#season_wrap').css('display','inline');
        if ($("#category option:selected").text() === 'Episode') {
           $('#episode_wrap').css('display','inline');
        }
        else {
        	  $('#episode_wrap').css('display','none');
        }	
        $('#source_wrap').css('display','inline');
        $('#resolution_wrap').css('display','inline');
        $('#codec_wrap').css('display','inline');
        $('#container_wrap').css('display','inline');
        $('#release_wrap').css('display','inline');
        $('#set_wrap').css('display','inline');

        $('#tags').val(tags);
        
        res.url = res.url.replace(/http:\/\//ig, 'https://');
        $('#quickcomment').val('[url=' + res.url + ']' + res.url + '[/url]');
                
    }

    $('#seasonepisodeset').click(function() {

      $('#messagebar').addClass('hidden');

      var season = $('#season').val();
      var episode = $('#episode').val();
      var resolution = $('#resolution').val().toLowerCase();
      var source = $('#source').val().toLowerCase();
      var codec = $('#codec').val().toLowerCase();
      var container = $('#container').val().toLowerCase();
      var release = $('#release').val().toLowerCase();
      
      if (source == '0') source = 'any.source';
      if (codec == '0') codec = 'any.codec';
      if (container == '0') container = 'any.container';
      if (release == '0') release = 'any';
      release += '.release'; 
      if (resolution == '0') resolution = 'any.resolution';

      if (!$.isNumeric(season)){
     	   $('#messagebar').text('Invalid Season!');
     	   $('#messagebar').removeClass('hidden');         
         return false;
      }      

      if ($("#category option:selected").text() === 'Episode' && !$.isNumeric(episode)){
     	   $('#messagebar').text('Invalid Episode!');
     	   $('#messagebar').removeClass('hidden');         
         return false;
      }

      season = parseInt(season);
      episode = parseInt(episode);

      if (season < 10) season = '0' + season;       
      if (episode && episode < 10) episode = '0' + episode;       

      $('#tags').val( resolution + ', ' + source + ', ' + codec + ', ' + container + ', ' + release + ', ' + $('#tags').val() );
      
      if($('#subs').is(":checked")) {
         $('#tags').val($('#tags').val() + ', subtitles');
         $('#quickcomment').val($('#quickcomment').val() + '[br][br]Subtitles requested!');
      }         
      
      if (episode) 
         $('#title').val( $('#title').val() + ' - S' + season + 'E' + episode );
      else   
         $('#title').val( $('#title').val() + ' - S' + season );

      $('#tags').prop('readonly', true);
      $('#season').prop('readonly', true);
      $('#episode').prop('readonly', true);
      $('#seasonepisodeset').prop('disabled', true);
      $('#subs').attr('disabled', true);
      $('#quickcomment').prop('readonly', true);
      $('#voting_wrap').css('display','table-row');        
      $('#bounty_info').css('display','table-row');        
      
    })
    
    $('#category').on('change', getreset);    
    function getreset(evt){
        evt.preventDefault();

        $('#messagebar').addClass('hidden');
        $('#tvmazeid').css('display','table-row');

        $('#tvmaze').val('');
        $('#title').val('');
        $('#tags').val('');
        $('#season').val('');
        $('#episode').val('');
        $('#source').val('0');
        $('#resolution').val('0');
        $('#codec').val('0');
        $('#container').val('0');
        $('#release').val('0');
        $('#quickcomment').val('');
        $('#subs').attr('checked', false);
        
        $('#tvmaze').prop('readonly', false);
        $('#title').prop('readonly', false);
        $('#tags').prop('readonly', false);
        $('#season').prop('readonly', false);
        $('#episode').prop('readonly', false);
        $('#tvmazeload').prop('disabled', false);
        $('#seasonepisodeset').prop('disabled', false);
        $('#subs').attr('disabled', false);
        
        $('#title_wrap').css('display','none');                
        $('#tags_wrapper').css('display','none');                
        $('#season_wrap').css('display','none');                
        $('#episode_wrap').css('display','none');                
        $('#source_wrap').css('display','none');                
        $('#resolution_wrap').css('display','none');                
        $('#codec_wrap').css('display','none');                
        $('#container_wrap').css('display','none');                
        $('#release_wrap').css('display','none');                
        $('#set_wrap').css('display','none');                
        $('#voting_wrap').css('display','none');                
        $('#bounty_info').css('display','none');        
        $('#create_wrap').css('display','none');
    }
});

function flow() {

   if (document.getElementById("category").value === '0') {
     	$('#messagebar').raw().innerHTML = 'Select a Category!';      
      $('#messagebar').show();   	
   	return false;
   }	

   if (document.getElementById("tvmaze").value === '' || document.getElementById("tvmazeload").disabled == false) {
     	$('#messagebar').raw().innerHTML = 'Load a TVMaze ID!';      
      $('#messagebar').show();   	
   	return false;
   }

   if (!document.getElementById('seasonepisodeset').disabled) {
     	$('#messagebar').raw().innerHTML = 'Set tags!';      
      $('#messagebar').show();   	
   	return false;
   }
   
   Calculate();   
}	

addDOMLoadEvent(Load_Details_Cookie);
