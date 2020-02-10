/*function Categories() {
	ajax.get('ajax.php?action=upload_section&categoryid=' + $('#categories').raw().value, function (response) {
		$('#dynamic_form').raw().innerHTML = response;
	});
}
 */

function add_tag() {
	if($('#tags').raw().value == "") {
		$('#tags').raw().value = $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	} else if($('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value == '---') {
	} else {
		$('#tags').raw().value = $('#tags').raw().value + ' ' + $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
	}
      CursorToEnd($('#tags').raw());
      resize('tags');
}


function SynchInterface(){
    change_tagtext();
    resize('tags');
}

function SelectTemplate(can_delete_any){ // a proper check is done in the backend.. the param is just for the interface
    $('#fill').disable($('#template').raw().selectedIndex==0);
    var can_delete = can_delete_any=='1' || !EndsWith($('#template').raw().options[$('#template').raw().selectedIndex].text, ')*');
    $('#delete').disable($('#template').raw().selectedIndex==0 || !can_delete);
    $('#save').disable($('#template').raw().selectedIndex==0 || !can_delete);
    return false;
}


function DeleteTemplate(can_delete_any){
    
    var TemplateID = $('#template').raw().options[$('#template').raw().selectedIndex].value; 
    if(TemplateID==0) return false;
    
    if(!confirm("This will permanently delete the selected template '" + $('#template').raw().options[$('#template').raw().selectedIndex].text + "'\nAre you sure you want to proceed?"))return false;
    
    var ToPost = [];
    ToPost['template'] = TemplateID;
 
    ajax.post("upload.php?action=delete_template", ToPost, function(response){
        var x = json.decode(response);  
        if ( is_array(x)){ 
            if (x[0]==0) { //  
                $('#messagebar').add_class('alert');
                $('#messagebar').html(x[1]);
            } else {  
                $('#messagebar').remove_class('alert');
                $('#messagebar').html(x[1]);
            } 
            $('#template_container').html(x[2]);
        } else { // a non number == an error  if ( !isnumeric(response)) 
                $('#messagebar').add_class('alert');
                $('#messagebar').html(x);
        }
        $('#messagebar').show(); 
        SelectTemplate(can_delete_any);
    });
    return false;
}



function OverwriteTemplate(can_delete_any){
      
    var TemplateID = $('#template').raw().options[$('#template').raw().selectedIndex].value;
    if(TemplateID==0) return false;
    
    if(!confirm("This will overwrite the selected template '" + $('#template').raw().options[$('#template').raw().selectedIndex].text + "'\nAre you sure you want to proceed?"))return false;
    
    return SaveTemplate(can_delete_any, 0, '', TemplateID);
}

function AddTemplate(can_delete_any, is_public){
    if(is_public==1) if(!confirm("Public templates are available for any user to use and display the authorname\nWarning: You cannot delete a public template once it is created\nAre you sure you want to proceed?"))return false;
    var name = prompt("Please enter the name for this template", "");
    if (!name || name =='') return false; 
    return SaveTemplate(can_delete_any, is_public, name, 0);
}



function SaveTemplate(can_delete_any, is_public, name, id){
    
    var ToPost = [];
    ToPost['templateID'] = id;
    ToPost['name'] = name;
    ToPost['ispublic'] = is_public;
    ToPost['title'] = $('#title').raw().value;
    ToPost['category'] = $('#category').raw().value;
    ToPost['image'] = $('#image').raw().value;
    ToPost['tags'] = $('#tags').raw().value;
    ToPost['body'] = $('#desc').raw().value;
    
    ajax.post("upload.php?action=add_template", ToPost, function(response){
        
        var x = json.decode(response);  
        if ( is_array(x)){ 
            if (x[0]==0) { //  
                $('#messagebar').add_class('alert');
                $('#messagebar').html(x[1]);
            } else {  
                $('#messagebar').remove_class('alert');
                $('#messagebar').html(x[1]);
            } 
            $('#template_container').html(x[2]);
        } else { // a non number == an error  if ( !isnumeric(response)) 
                $('#messagebar').add_class('alert');
                $('#messagebar').html(x);
        }
        $('#messagebar').show(); 
        SelectTemplate(can_delete_any);
       
    });
    return false;
}

var LogCount = 1;

function AddLogField() {
		if(LogCount >= 200) {return;}
		var LogField = document.createElement("input");
		LogField.type = "file";
		LogField.id = "file";
		LogField.name = "logfiles[]";
		LogField.size = 50;
		var x = $('#logfields').raw();
		x.appendChild(document.createElement("br"));
		x.appendChild(LogField);
		LogCount++;
}

function RemoveLogField() {
		if(LogCount == 1) {return;}
		var x = $('#logfields').raw();
		for (i=0; i<2; i++) {x.removeChild(x.lastChild);}
		LogCount--;
}


function Upload_Quick_Preview() {
   $('#post_preview').raw().title = ""; 
	$('#post_preview').raw().value = "Make changes";
	$('#post_preview').raw().preview = true;
	ajax.post("ajax.php?action=preview_upload","upload_table", function(response){
        
        var x = json.decode(response); 
        if ( is_array(x)){
            $('#uploadpreviewbody').show();
            $('#messagebar').raw().innerHTML = x[0];
            if(x[0]) $('#messagebar').show();
            else $('#messagebar').hide()
            $('#contentpreview').raw().innerHTML = x[1];
            $('.uploadbody').hide(); 
        }
	});
}

function Upload_Quick_Edit() {
	$('#post_preview').raw().value = "Preview";
	$('#post_preview').raw().preview = false;
	$('#uploadpreviewbody').hide();
	$('.uploadbody').show(); 
}

function ShowNext(x) {
   document.getElementById(x).style.display = 'table-row';
}

function SwitchCat(episode,season) {

   $('#messagebar').hide();
   $('#category').raw().style.backgroundColor = '';
	category = document.getElementById('category').value;
   ShowNext('tvmaze');
	ShowNext('mediainfowrap');
	ShowNext('checkbutton');

	if (category == season) {         // season
     ShowNext('screenswrap');
/*     ShowNext('trailerwrap'); */
   }
   else if(category == episode) {     // episode

/*     document.getElementById('screenswrap').style.display = 'none';   */
/*     document.getElementById('trailerwrap').style.display = 'none';   */
/*     document.getElementById('screens').value = '';                   */
/*     document.getElementById('trailer').value = ''; */
     document.getElementById('description').value = '';

   }                           
   else {                       // none  

     document.getElementById('mediainfowrap').style.display = 'none';   
     document.getElementById('tvmaze').style.display = 'none';
/*     document.getElementById('screenswrap').style.display = 'none';   */
/*     document.getElementById('trailerwrap').style.display = 'none';   */
     document.getElementById('checkbutton').style.display = 'none';   
     document.getElementById('media').value = '';
/*     document.getElementById('screens').value = '';   */
/*     document.getElementById('trailer').value = '';   */  
     document.getElementById('description').value = '';

   }  
}	

function SecureMedia(url,element) {
	var x = url.replace(/http:/g, 'https:');
	document.getElementById(element).value = x;
}

// ----------- Templates -------------------------------- //

function FillMediaInfo() {
   document.getElementById('mediaclean').value = '[mediainfo]\n\n'
   + document.getElementById('media').value + '\n\n[/mediainfo]';

   document.getElementById('desc').value = document.getElementById('media').value;
}

/*function FillScreens() {
   document.getElementById('screensclean').value = '[center]\n'
   + document.getElementById('screens').value + '\n[/center]';
}

function FillTrailer() {
   document.getElementById('trailerclean').value = '[center][br][video='
   + document.getElementById('trailer').value + '][/center]';
}*/

// ------------------------------------------------------ //

function CheckMediaInfo() {

   if (document.getElementById('media').value == ''){ // empty

    document.getElementById('checkonly').value = 'Checking...';	
    document.getElementById('media').style.backgroundColor = '#380608';    
	 $('#messagebar').raw().innerHTML = 'Media Info is required!';  	 
  	 $('#messagebar').show();
    document.getElementById('checkonly').value = 'Check Upload/Autofill';
    document.getElementById('media').focus();
    return false;

   }
   else {                                            // check

    var mediainfo = document.getElementById('media').value;   
    var strs = ['Video','Audio','Codec ID','Format/Info','Frame rate','Complete name','jpg','png','youtube','youtu.be','vimeo','[mediainfo]','[/mediainfo]'];
    var complete_name = mediainfo.split(strs[5]);   // count

    if (mediainfo.indexOf(strs[0]) == -1 || mediainfo.indexOf(strs[1]) == -1 || mediainfo.indexOf(strs[2]) == -1 || mediainfo.indexOf(strs[3]) == -1 || mediainfo.indexOf(strs[4]) == -1 || mediainfo.indexOf(strs[5]) == -1 || mediainfo.indexOf(strs[6]) != -1 || mediainfo.indexOf(strs[7]) != -1 || mediainfo.indexOf(strs[8]) != -1 || mediainfo.indexOf(strs[9]) != -1 || mediainfo.indexOf(strs[10]) != -1 || mediainfo.indexOf(strs[11]) != -1 || mediainfo.indexOf(strs[12]) != -1 || (complete_name.length - 1) != 1 ) {

      document.getElementById('media').style.backgroundColor = '#380608';
      $('#messagebar').raw().innerHTML = 'Media Info is invalid!';  	 
  	   $('#messagebar').show();
      document.getElementById('checkonly').value = 'Check Upload/Autofill';
      document.getElementById('media').focus();
      document.getElementById('checkonly').type = 'button';       
      document.getElementById('post').type = 'button';       
      return false;

     }

    document.getElementById('media').style.backgroundColor = ''; 
    return true; 
   } 
}	

/*function CheckScreens(minUploadScreenshots) {
   
   if (minUploadScreenshots == undefined || minUploadScreenshots == 0) minUploadScreenshots = '';
   
   if (document.getElementById('screens').value == '') { // empty

    document.getElementById('checkonly').value = 'Checking...';	
	 $('#messagebar').raw().innerHTML = minUploadScreenshots + ' Screens are required!';  	 
  	 $('#messagebar').show();
    document.getElementById('screens').style.backgroundColor = '#380608';        
    document.getElementById('checkonly').value = 'Check Upload/Autofill';
    document.getElementById('screens').focus();
    document.getElementById('checkonly').type = 'button';
    document.getElementById('post').type = 'button';                  
    return false;

   }
   else {                                            // check

    var screens = document.getElementById('screens').value;   
    var strs = ['.jpg','.png','[img]','[/img]','http','thumbnail'];
    var jpg = screens.split(strs[0]);                // count
    var png = screens.split(strs[1]);
    var imgtag = screens.split(strs[2]);
    var imgtagc = screens.split(strs[3]);
                                                     // type                                                                                                                                                                                                                                                                                                     // count                                           
    if ((screens.indexOf(strs[0]) == -1 || screens.indexOf(strs[2]) == -1 || screens.indexOf(strs[3]) == -1 || screens.indexOf(strs[4]) == -1 || screens.indexOf(strs[5]) != -1) && (screens.indexOf(strs[1]) == -1 || screens.indexOf(strs[2]) == -1 || screens.indexOf(strs[3]) == -1 || screens.indexOf(strs[4]) == -1 || screens.indexOf(strs[5]) != -1) || (((png.length - 1) != minUploadScreenshots || (imgtag.length - 1) != minUploadScreenshots || (imgtagc.length - 1) != minUploadScreenshots) && ((jpg.length - 1) != minUploadScreenshots || (imgtag.length - 1) != minUploadScreenshots || (imgtagc.length - 1) != minUploadScreenshots))) {
    	document.getElementById('screens').style.backgroundColor = '#380608';
      $('#messagebar').raw().innerHTML = 'Screens are invalid!';  	 
  	   $('#messagebar').show();
      document.getElementById('checkonly').value = 'Check Upload/Autofill';
      document.getElementById('screens').focus();
      document.getElementById('checkonly').type = 'button';
      document.getElementById('post').type = 'button';                    
      return false;
    }
    
   } 

   document.getElementById('screens').style.backgroundColor = '';    
   return true; 
}

function CheckTrailer() {

    var trailer = document.getElementById('trailer').value;
    var strs = ['youtube','youtu.be','vimeo','https'];
    var yt = trailer.split(strs[0]);              // count
    var yt2 = trailer.split(strs[1]);
    var vm = trailer.split(strs[2]);
    var ht = trailer.split(strs[3]);
                                                 // type                                                                                                                                                                        // count                                                                  
    if (((trailer.indexOf(strs[0]) == -1 || trailer.indexOf(strs[3]) == -1 ) && (trailer.indexOf(strs[1]) == -1 || trailer.indexOf(strs[3]) == -1 ) && (trailer.indexOf(strs[2]) == -1 || trailer.indexOf(strs[3]) == -1 )) || (((yt.length - 1) != 1 || (ht.length - 1) != 1) && ((yt2.length - 1) != 1 || (ht.length - 1) != 1) && ((vm.length - 1) != 1 || (ht.length - 1) != 1))) {
    	document.getElementById('trailer').style.backgroundColor = '#380608';
      $('#messagebar').raw().innerHTML = 'Trailer is invalid!';  	 
  	   $('#messagebar').show();
      document.getElementById('checkonly').value = 'Check Upload/Autofill';
      document.getElementById('trailer').focus();
      document.getElementById('checkonly').type = 'button';
      document.getElementById('post').type = 'button';                    
      return false;
    }
  
   document.getElementById('trailer').style.backgroundColor = '';    
   return true; 
}*/

function FillDescription(minUploadScreenshots,episode) {
	
   if ($('#file').raw().value == '') { // no torrent
     $('#messagebar').raw().innerHTML = 'Select torrent file!';
     $('#messagebar').show();
     return;   
   }	    
   
   category = document.getElementById('category').value;
   
   if (category == 0) {     // no category
     $('#messagebar').raw().innerHTML = 'Select category!';
     document.getElementById('category').style.backgroundColor = '#380608';
     $('#messagebar').show();
     return;
   }
   
	if (!CheckMediaInfo()) return;
	
   if (category == episode) {    // episode
 
    FillMediaInfo();
    $('#messagebar').hide();
    document.getElementById('checkonly').type = 'submit';   
    document.getElementById('post').type = 'submit';   

   }
   else {          // season
   
/*     if (!CheckScreens(minUploadScreenshots)) return;
   
    FillMediaInfo();
/*    FillScreens();   */
    
/*    if (document.getElementById('trailer').value != '') {
     if(!CheckTrailer()) {
     	return;
     	}
      else {
      	FillTrailer();
      }	
    }*/
    
    FillMediaInfo();
    	
    $('#messagebar').hide();
    document.getElementById('checkonly').type = 'submit';
    document.getElementById('post').type = 'submit';          
   }
   
   return true; 
}
