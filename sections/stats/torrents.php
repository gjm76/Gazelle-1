<?php

if (!check_perms('site_stats_advanced')) error(403);

$DB->query("SELECT tg.NewCategoryID, COUNT(t.ID) AS Torrents
              FROM torrents AS t JOIN torrents_group AS tg ON tg.ID=t.GroupID
          GROUP BY tg.NewCategoryID ORDER BY Torrents DESC");
$Groups = $DB->to_array();
$Pie = new PIE_CHART(750,400,array('Other'=>0.2,'Percentage'=>1));
foreach ($Groups as $Group) {
    list($NewCategoryID, $Torrents) = $Group;
    //$CategoryName = $NewCategories[$NewCategoryID]['name'];
    $Pie->add($NewCategories[$NewCategoryID]['name'],$Torrents);
}
$Pie->transparent();
$Pie->color('FF33CC');
$Pie->generate();
$TorrentCategories = $Pie->url();

//==========================================================

if (isset($_POST['view']) && check_perms('site_stats_advanced')) {

    $start = date('Y-m-d H:i:s', strtotime( "$_POST[year1]-$_POST[month1]-$_POST[day1]" )  );
    $end = date('Y-m-d H:i:s', strtotime( "$_POST[year2]-$_POST[month2]-$_POST[day2]" )  );
   // error("$start --> $end");
    if($start===false) error("Error in start time input");
    if($end===false) error("Error in end time input");
    if (strtotime($start)<strtotime("2011-02-01")) {
        $start = "2011-02-01 00:00:00";
        $_POST['year1']=2011; $_POST['month1']=02; $_POST['day1']=01;
    }
    if (strtotime($end)>time()) $end = sqltime();
    if ($start>=$end) error("Start date ($start) cannot be after end date ($end)");

    $title = "$_POST[year1]-$_POST[month1]-$_POST[day1] to $_POST[year2]-$_POST[month2]-$_POST[day2]";
    $DB->query("SELECT UNIX_TIMESTAMP(DATE(Time))*1000 AS Date,
                Count(ID) As Torrents
                FROM torrents
                WHERE Time BETWEEN '$start' AND '$end'
                GROUP BY Date
                ORDER BY Date DESC
                LIMIT 365");
    $TorrentUploadStats = $DB->to_array(false, MYSQLI_ASSOC, false, false);
    foreach($TorrentUploadStats as $Key => $Value) {
        $TorrentUploadStats[$Key] = array_map('intval', array_slice($Value, 0, 2));
    }

    $DB->query("SELECT UNIX_TIMESTAMP(DATE(Time))*1000 AS Date,
                COUNT(ID) AS Count
                FROM log
                WHERE Time BETWEEN '$start' AND '$end'
                AND Message LIKE '%deleted for inactivity%'
                GROUP BY Date
                ORDER BY Date DESC
                LIMIT 365");
    $TorrentDeleteStats = $DB->to_array(false, MYSQLI_ASSOC, false, false);
    foreach($TorrentDeleteStats as $Key => $Value) {
        $TorrentDeleteStats[$Key] = array_map('intval', array_slice($Value, 0, 2));
    }

    $TorrentStats = ['uploaded'=>$TorrentUploadStats, 'deleted'=>$TorrentDeleteStats];
}

if (!$TorrentStats) {
    $TorrentStats = $Cache->get_value('torrents_byday');
    $title = "last 365 days";
}

if ($TorrentStats === false) {
    $DB->query("SELECT UNIX_TIMESTAMP(DATE(Time))*1000 AS Date,
                Count(ID) As Torrents
                FROM torrents
                WHERE Time < (NOW() - INTERVAL 1 DAY)
                GROUP BY Date
                ORDER BY Date DESC");
    $TorrentUploadStats = $DB->to_array(false, MYSQLI_ASSOC, false, false);
    foreach($TorrentUploadStats as $Key => $Value) {
        $TorrentUploadStats[$Key] = array_map('intval', array_slice($Value, 0, 2));
    }

    $DB->query("SELECT UNIX_TIMESTAMP(DATE(Time))*1000 AS Date,
                COUNT(ID) AS Count
                FROM log
                WHERE Time < (NOW() - INTERVAL 1 DAY)
                AND Message LIKE '%deleted for inactivity%'
                GROUP BY Date
                ORDER BY Date DESC");
    $TorrentDeleteStats = $DB->to_array(false, MYSQLI_ASSOC, false, false);
    foreach($TorrentDeleteStats as $Key => $Value) {
        $TorrentDeleteStats[$Key] = array_map('intval', array_slice($Value, 0, 2));
    }

    $TorrentStats = ['uploaded'=>$TorrentUploadStats, 'deleted'=>$TorrentDeleteStats];
    $Cache->cache_value('torrents_byday',$TorrentStats, 3600*24 );
}

//=================================================================

show_header('Torrent statistics','charts,flot/excanvas,flot/jquery.flot.min,flot/jquery.flot.time');
?>

<div class="thin">
    <h2>Torrent stats</h2>
    <div class="linkbox">
        <a href="stats.php?action=users">[User graphs]</a>
        <a href="stats.php?action=site">[Site stats]</a>
        <strong><a href="stats.php?action=torrents">[Torrent stats]</a></strong>
    </div>
    <br/>

    <div class="head">Uploads daily</div>
    <table class="">
        <tr><td class="box pad center">
<?php
    if ($TorrentStats) {
?>
        <script type="text/javascript">
            jQuery(function() {
                diff = 60*24*60*60*1000;
                startdate = <?=end($TorrentStats['uploaded'])[0]?>;
                enddate = new Date().setHours(0,0,0,0);
                maxdate = enddate;
                mindate = maxdate - diff;
                var plot = jQuery.plot(jQuery("#torrents_timeline"), [
                    { data: <?=json_encode($TorrentStats['uploaded'])?>, label: "Uploaded"}
                ], {
                    series: {
                        bars: {
                            show: true,
                            fill: true,
                            fillColor: "rgba(0, 0, 255, 0.2)"
                        }
                    },
                    bars: {
                        align: "center",
                        barWidth: (60*60*24*1000)
                    },
                    grid: {
                        hoverable: true
                    },
                    xaxis: {
                        mode: "time",
                        timeformat: "%d %b %y",
                        min: mindate,
                        max: maxdate
                    },
                    colors: ["#0000FF", "#FF0000"]
                });


                jQuery("<div id='tooltip' class='box pad'></div>").css({
                    position: "absolute",
                    display: "none",
                    padding: "2px",
                    opacity: 0.80
                }).appendTo("body");

                jQuery("#torrents_timeline").bind("plothover", function (event, pos, item) {
                    if (item) {
                        var d = new Date(item.datapoint[0]),
			y = item.datapoint[1].toFixed(0);

                        jQuery("#tooltip").html(d.toDateString()+"</br>"+item.series.label + " " + y)
                            .css({top: item.pageY+5, left: item.pageX+5})
                            .fadeIn(200);
                    } else {
                        jQuery("#tooltip").hide();
                    }
                });

                controls = jQuery("#timeline_controls");

                jQuery('<input class="chart_button" type="button" value="|<" title="start" />')
                    .appendTo(controls)
                    .click(function (e) {
                        e.preventDefault();
                        mindate = startdate+1;
                        maxdate = mindate + diff;
                        plot.getAxes().xaxis.options.max = maxdate;
                        plot.getAxes().xaxis.options.min = mindate;
                        plot.setupGrid();
                        plot.draw();
                    });
                jQuery('<input class="chart_button" type="button" value="<<" title="back" />')
                    .appendTo(controls)
                    .click(function (e) {
                        e.preventDefault();
                        mindate -= (28*24*60*60*1000);
                        mindate = Math.max(mindate, startdate);
                        maxdate = mindate + diff;
                        plot.getAxes().xaxis.options.max = maxdate;
                        plot.getAxes().xaxis.options.min = mindate;
                        plot.setupGrid();
                        plot.draw();
                    });
                jQuery('<input class="chart_button" type="button" value="<" title="back" />')
                    .appendTo(controls)
                    .click(function (e) {
                        e.preventDefault();
                        mindate -= (7*24*60*60*1000);
                        mindate = Math.max(mindate, startdate);
                        maxdate = mindate + diff;
                        plot.getAxes().xaxis.options.max = maxdate;
                        plot.getAxes().xaxis.options.min = mindate;
                        plot.setupGrid();
                        plot.draw();
                    });

                jQuery('<span>&nbsp;&nbsp;</span>').appendTo(controls);

                jQuery('<input class="chart_button" type="button" value="▽" title="zoom out" />')
                    .appendTo(controls)
                    .click(function (e) {
                        e.preventDefault();
                        diff *= 1.5;
                        mindate = maxdate - diff;
                        plot.getAxes().xaxis.options.min = mindate;
                        plot.setupGrid();
                        plot.draw();
                    });
                jQuery('<input class="chart_button" type="button" value="△" title="zoom in" />')
                    .appendTo(controls)
                    .click(function (e) {
                        e.preventDefault();
                        diff /= 1.5;
                        mindate = maxdate - diff;
                        plot.getAxes().xaxis.options.min = mindate;
                        plot.setupGrid();
                        plot.draw();
                    });

                jQuery('<span>&nbsp;&nbsp;</span>').appendTo(controls);

                jQuery('<input class="chart_button" type="button" value=">" title="forward" />')
                    .appendTo(controls)
                    .click(function (e) {
                        e.preventDefault();
                        maxdate += (7*24*60*60*1000);
                        maxdate = Math.min(maxdate, enddate);
                        mindate = maxdate - diff;
                        plot.getAxes().xaxis.options.max = maxdate;
                        plot.getAxes().xaxis.options.min = mindate;
                        plot.setupGrid();
                        plot.draw();
                    });
                jQuery('<input class="chart_button" type="button" value=">>" title="forward" />')
                    .appendTo(controls)
                    .click(function (e) {
                        e.preventDefault();
                        maxdate += (28*24*60*60*1000);
                        maxdate = Math.min(maxdate, enddate);
                        mindate = maxdate - diff;
                        plot.getAxes().xaxis.options.max = maxdate;
                        plot.getAxes().xaxis.options.min = mindate;
                        plot.setupGrid();
                        plot.draw();
                    });
                jQuery('<input class="chart_button" type="button" value=">|" title="end" />')
                    .appendTo(controls)
                    .click(function (e) {
                        e.preventDefault();
                        maxdate = enddate;
                        mindate = maxdate - diff;
                        plot.getAxes().xaxis.options.max = maxdate;
                        plot.getAxes().xaxis.options.min = mindate;
                        plot.setupGrid();
                        plot.draw();
                    });
            });
        </script>
        <h1>Uploads daily</h1>
        <span style="position:relative;left:0px;"><?=$title?></span>
        <div id="torrents_timeline" style="width:100%;height:600px"></div>
        <div id="timeline_controls" style="margin:10px auto 10px"></div>
<?php
        if (check_perms('site_debug')) {  ?>
            <span style="float:left">
                <a href="#debuginfo" onclick="$('#databox').toggle(); this.innerHTML=(this.innerHTML=='DEBUG: (Hide chart data)'?'DEBUG: (View chart data)':'DEBUG: (Hide chart data)'); return false;">DEBUG: (View chart data)</a>
            </span>&nbsp;

            <div id="databox" class="box pad hidden">
            <?=print_r($TorrentStats)?>
            </div>
<?php       }  ?>
<?php
    } else { ?>
        <p>No torrent data found</p>
<?php   }  ?>
        </td></tr>

<?php
    if (check_perms('site_stats_advanced')) {
        if (isset($_POST['year1'])) {
            $start = array ($_POST['year1'],$_POST['month1'],$_POST['day1']);
            $end = array ($_POST['year2'],$_POST['month2'],$_POST['day2']);
        } else {
            //$start = array (2011,02,01);
            $start =  date('Y-m-d', time() - (3600*24*365));
            $start = explode('-', $start);
            $end =  date('Y-m-d');
            $end = explode('-', $end);
        }
?>
        <tr><td class="colhead">view options</td></tr>
        <tr><td class="box pad center">
            <form method="post" action="">
                <input type="text" style="width:30px" title="day" name="day1" value="<?=$start[2]?>" />
                <input type="text" style="width:30px" title="month" name="month1"  value="<?=$start[1]?>" />
                <input type="text" style="width:50px" title="year" name="year1"  value="<?=$start[0]?>" />
                &nbsp;&nbsp;To&nbsp;&nbsp;
                <input type="text" style="width:30px" title="day" name="day2"  value="<?=$end[2]?>" />
                <input type="text" style="width:30px" title="month" name="month2"  value="<?=$end[1]?>" />
                <input type="text" style="width:50px" title="year" name="year2"  value="<?=$end[0]?>" />
                &nbsp;&nbsp;&nbsp;&nbsp;
                <input type="submit" name="view" value="View history" />
            </form>
        </td></tr>
<?php   }  ?>

    </table>
    <br/><br/>
    <div class="head">Torrents by category</div>
    <div class="box pad center">
        <h1>Torrents by category</h1>
        <img src="<?=$TorrentCategories?>" />
    </div>
</div>
<?php
show_footer();
