/*
	TODO: Further optimize serialize function
	
	UPDATE: We were forced to create an individual XHR for each request 
	to avoid race conditions on slower browsers where the request would 
	be overwritten before the callback triggered, and leave it hanging. 
	This only happened in FF3.0 that we tested.

	Example usage 1:
	ajax.handle = function () {
		$('#preview' + postid).raw().innerHTML = ajax.response;
		$('#editbox' + postid).hide();	
	}
	ajax.post("ajax.php?action=preview","#form-id" + postid);
	
	Example usage 2:
	ajax.handle = function() {
		$('#quickpost').raw().value = "[quote="+username+"]" + ajax.response + "[/quote]";
	}
	ajax.get("?action=get_post&post=" + postid);
	
*/
"use strict";
var json = {
	encode: function (object) {
		try {
			return JSON.stringify(object);
		} catch (err) {
			return '';
		}
	},
	decode: function (string) {
		if (window.JSON && JSON.parse) {
			return JSON.parse(string);
		} else {
			return eval("(" + string + ")");
			//return (new Function("return " + data))();
		}
	}
};

var ajax = {
	getXML: function (url, callback) {
		var req = (typeof(window.ActiveXObject) === 'undefined') ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
		//if(req.overrideMimeType) req.overrideMimeType("text/xml");
            if (callback !== undefined) {
			req.onreadystatechange = function () {
				if (req.readyState !== 4 || req.status !== 200) {
					return;
				}
				callback(req.responseXML);
			};
		}
		req.open("GET", url, true);
		req.send(null);
	},
	get: function (url, callback) {
		var req = (typeof(window.ActiveXObject) === 'undefined') ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
		if (callback !== undefined) {
			req.onreadystatechange = function () {
				if (req.readyState !== 4 || req.status !== 200) {
					return;
				}
				callback(req.responseText);
			};
		}
		req.open("GET", url, true);
		req.send(null);
	},
	post: function (url, data, callback) {
		var req = isset(window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
		var params = ajax.serialize(data);
		if (callback !== undefined) {
			req.onreadystatechange = function () {
				if (req.readyState !== 4 || req.status !== 200) {
					return;
				}
				callback(req.responseText);
			};
		}
		req.open('POST', url, true);
		req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		req.send(params);
	},
	serialize: function (data) {
		var query = '',
			elements;
		if (is_array(data)) {
			for (var key in data) {
				query += key + '=' + encodeURIComponent(data[key]) + '&';
			}
		} else {
			elements = document.getElementById(data).elements;
			for (var i = 0, il = elements.length; i < il; i++) {
				var element = elements[i];
				if (!isset(element) || element.disabled || element.name === '') {
					continue;
				}
				switch (element.type) {
					case 'text':
					case 'hidden':
					case 'password':
					case 'textarea':
					case 'select-one':
						query += element.name + '=' + encodeURIComponent(element.value) + '&';
						break;
					case 'select-multiple':
						for (var j = 0, jl = element.options.length; j < jl; j++) {
							var current = element.options[j];
							if (current.selected) {
								query += element.name + '=' + encodeURIComponent(current.value) + '&';
							}
						}
						break;
					case 'radio':
						if (element.checked) {
							query += element.name + '=' + encodeURIComponent(element.value) + '&';
						}
						break;
					case 'checkbox':
						if (element.checked) {
							query += element.name + '=' + encodeURIComponent(element.value) + '&';
						}
						break;
				}
			}
		}
		return query.substr(0, query.length - 1);
	}
};

//Bookmarks
function Bookmark(type, id, newName, callback) {
	ajax.get("bookmarks.php?action=add&type=" + type + "&auth=" + authkey + "&id=" + id, function() {
		if(callback) {
			callback();
		}
	});
}

function Unbookmark(type, id, newName, callback) {
	if(window.location.pathname.indexOf('bookmarks.php') != -1) {
		ajax.get("bookmarks.php?action=remove&type=" + type + "&auth=" + authkey + "&id=" + id, function() {
			$('#group_' + id).remove();
			$('.groupid_' + id).remove();
			$('.bookmark_' + id).remove();
		});
	} else {
		ajax.get("bookmarks.php?action=remove&type=" + type + "&auth=" + authkey + "&id=" + id, function() {
			if(callback) {
				callback();
			}
		});
	}
}

jQuery(function($) {
    $('#torrent_table, .torrent_table').on('click', '.__bookmark-torrent', function(evt){
        evt.preventDefault();
        var bmEl = $(this);
        var icon = bmEl.find('.icon');
        var torrentID = bmEl.data('torrentid');
        if (icon.hasClass('bookmarked')) {
            Unbookmark('torrent', torrentID, '', function(){
                icon.removeClass('bookmarked');
                if (useTooltipster) {
                    bmEl.tooltipster('content', 'Add bookmark');
                } else {
                 	  bmEl.attr('title', 'Add bookmark')
                    }
               });
        } else {
            Bookmark('torrent', torrentID, '', function(){
                icon.addClass('bookmarked');
                if (useTooltipster) {
                    bmEl.tooltipster('content', 'Remove bookmark');
                } else {
                 	  bmEl.attr('title', 'Remove bookmark')
                }
            });
        }
    });
});

jQuery(function($) {
    $('.__bookmark-collage').on('click', function(evt){
        evt.preventDefault();
        var bmEl = $(this);
        var collageID = bmEl.data('collageid');
        var bookmarked = typeof bmEl.attr('data-bookmarked') !== typeof undefined && bmEl.attr('data-bookmarked') !== false;
        if (!bookmarked) {
            Bookmark('collage', collageID, '', function(){
                bmEl.attr('data-bookmarked', '1');
            });
            bmEl.text('[Remove Bookmark]');
        } else {
            Unbookmark('collage', collageID, '', function(){
                bmEl.removeAttr('data-bookmarked');
            });
            bmEl.text('[Bookmark]');
        }
    });
});

jQuery(function($) {
    $('.__bookmark-request').on('click', function(evt){
        evt.preventDefault();
        var bmEl = $(this);
        var requestID = bmEl.data('requestid');
        var bookmarked = typeof bmEl.attr('data-bookmarked') !== typeof undefined && bmEl.attr('data-bookmarked') !== false;
        if (!bookmarked) {
            Bookmark('request', requestID, '', function(){
                bmEl.attr('data-bookmarked', '1');
            });
            bmEl.text('[Remove Bookmark]');
        } else {
            Unbookmark('request', requestID, '', function(){
                bmEl.removeAttr('data-bookmarked');
            });
            bmEl.text('[Bookmark]');
        }
    });
});
