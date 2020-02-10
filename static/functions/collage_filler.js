jQuery(function($) {
    'use strict';
    $('#fillcollage').on('click', fillCollage);
    function fillCollage(evt){
        evt.preventDefault();
        var tvmaze = prompt("Enter TVMaze show URL or ID", "0");
        if(tvmaze === "0"){
            return false;
        }
        var tvmazeid = 1;
        if ($.isNumeric(tvmaze)){
            tvmazeid = tvmaze;
        } else {
            tvmazeid = tvmaze.match(/\/shows\/([0-9]*)\/?/)[1];
        }
        $.get(`//api.tvmaze.com/shows/${tvmazeid}?embed[]=cast&embed[]=episodes&embed[]=seasons&embed[]=crew`, insertData);
    }
    function insertData(res){
            var airinfo = '[b]Airs on: [/b][url=/shows.php?page=1&sort=weight&taglist=all&genre=&network={channelNameEncoded}]{channelName}[/url] ({fromDate}-{toDate})';

            // Start tag getting
            var tags = [];
            tags = tags.concat(res.genres);
            if (res.language !== 'English') {
                tags.push(res.language);
            }

            tags.push(res.type);

            // Format network
            if (res.webChannel) {
                tags.push(res.webChannel.name.replace(/ /, '.'));
                airinfo = airinfo.replace(/{channelName}/, res.webChannel.name).replace(/{channelNameEncoded}/, encodeURIComponent(res.webChannel.name));
            } else if (res.network){
                tags.push(res.network.name.replace(/ /, '.'));
                airinfo = airinfo.replace(/{channelName}/, res.network.name).replace(/{channelNameEncoded}/, encodeURIComponent(res.network.name));
            }
            
            // Format date FROM
            var dateFromYear = new Date(res.premiered).getFullYear();
            airinfo = airinfo.replace(/{fromDate}/, dateFromYear);

            var toDate;
            // Format date TO
            if (res.status === 'Running'){
                toDate = "now";
            } else {
                var lastSeason = res._embedded.seasons[res._embedded.seasons.length - 1];
                toDate = new Date(lastSeason.endDate).getFullYear();
            }
            airinfo = airinfo.replace(/{toDate}/, toDate);

            var creators = [];
            for (var i = 0; i < res._embedded.crew.length; i++) {
                var creator = res._embedded.crew[i];
                if (creator.type === 'Creator'){
                    creators.push(`[url=${creator.person.url}]${creator.person.name}[/url]`);
                }
            }
            if (creators.length > 0) {
                creators = `\n[b]Created by: ${creators.join(' | ')}[/b]`;
            }

            var officialSite = '';
            if (res.officialSite) {
            	// Get host by creating fake anchor element and extracting hostname natively
            	var hostName = $("<a/>").attr('href', res.officialSite)[0].hostname;
                officialSite = `\n[b]Official site:[/b] [url=${res.officialSite}]${hostName}[/url]`;
            }
            var summary = '';
            if (res.summary) {
                summary = $("<span/>").html(res.summary).text(); // Strip tags by creating an element then getting inner text
            }

            var cast = [];
            if (res._embedded.cast) {
                for (var i = 0; i < 8; i++) {
                    // Every fourth actor
                    if ((i!==0 && (i % 4 === 0))){
                        cast.push('[/tr][tr]');
                    }
                    if (res._embedded.cast[i]) {
                        var image = '';
                        if (res._embedded.cast[i].character.image !== null) {
                            var imageObj = res._embedded.cast[i].character.image;
                            if (imageObj !== null) {
                                image = res._embedded.cast[i].character.image.medium.replace('medium_portrait', 'small_portrait');
                            } else {
                                image = '/static/common/images/noimg.png';
                            }
                        } else {
                            var imageObj = res._embedded.cast[i].person.image;
                            if (imageObj !== null) {
                                image = res._embedded.cast[i].person.image.medium.replace('medium_portrait', 'small_portrait');
                            } else {
                                image = '/static/common/images/noimg.png';
                            }
                        }
                        
                        cast.push(`[td][img]${image}[/img][br] [b][url=${res._embedded.cast[i].person._links.self.href.replace('api.', '')}/x]${res._embedded.cast[i].person.name} [/url][/b][br] as [url=${res._embedded.cast[i].character._links.self.href.replace('api.', '')}/x]${res._embedded.cast[i].character.name} [/url][/td]`);
                    }
                }
            }
            tags = tags.join(' ').toLowerCase();
            var template = `[table=nball][tr][td=40%][hr][hr][hr][/td][td][size=2][align=center][b]Show Info:[/b][/align][/size][/td][td=40%][hr][hr][hr][/td][/tr][/table][table=nball][tr][td=10%][/td][td][url=${res.url}][size=4]${res.name}[/size][/url]

${airinfo}
[b]Show Type: [/b]${res.type}
[b]Genres: [/b]${res.genres.join(', ')}
${creators}${officialSite}

${summary}
[/td][td=10%][/td][/tr][/table]
[table=nball][tr][td=40%][hr][hr][hr][/td][td][size=2][align=center][b]Cast:[/b][/align][/size][/td][td=40%][hr][hr][hr][/td][/tr][/table]

[table][tr]${cast.join('\n')}[/tr][/table]
[align=right][url=${res.url}/cast]View Full Cast[/url][/align]`.replace(/http:\/\//ig, 'https://'); // :-(
        $('#collagename [name="name"]').val(res.name);
        $('#description').val(template);
        $('#tags').val(tags);
    }
});
