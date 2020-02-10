var ARTIST_AUTOCOMPLETE_URL = 'people.php?action=autocomplete';
var SHOW_AUTOCOMPLETE_URL = 'shows.php?action=autocomplete';
var TORRENT_AUTOCOMPLETE_URL = 'torrents.php?action=autocomplete';
var SELECTOR = '[data-gazelle-autocomplete="true"]';
$(document).ready(initAutocomplete)

function initAutocomplete() {
  if (!jQuery.Autocomplete) {
    window.setTimeout(function() {
      initAutocomplete();
    }, 500)
    return;
  }

  var url = {
    path: window.location.pathname.split('/').reverse()[0].split(".")[0],
    query: window.location.search.slice(1).split('&').reduce((a,b)=>Object.assign(a,{[b.split('=')[0]]:b.split('=')[1]}),{})
  }

  jQuery('#searchbox_torrents' + SELECTOR).autocomplete({
    deferRequestBy: 300,
    onSelect : function(suggestion) {
      window.location = 'torrents.php?id=' + suggestion['data'];
    },
    serviceUrl : TORRENT_AUTOCOMPLETE_URL,
  });

  jQuery('#searchbox_shows' + SELECTOR).autocomplete({
    deferRequestBy: 300,
    onSelect : function(suggestion) {
      window.location = 'torrents.php?action=show&showid=' + suggestion['data'];
    },
    serviceUrl : SHOW_AUTOCOMPLETE_URL,
  });

  jQuery('#searchbox_people' + SELECTOR).autocomplete({
    deferRequestBy: 300,
    onSelect : function(suggestion) {
      window.location = 'torrents.php?action=person&personid=' + suggestion['data'];
    },
    serviceUrl : ARTIST_AUTOCOMPLETE_URL,
  });

};
