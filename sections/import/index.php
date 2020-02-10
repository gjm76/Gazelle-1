<?php
//******************************************************************************//
//----------------------------------- Import -----------------------------------//
// This script can be used from the command line to import torrent files into   //
// the database if migrating from another tracker/site software                 //
//******************************************************************************//

if ((!isset($argv[1]) || $argv[1]!=SCHEDULE_KEY) && !check_perms('admin_schedule')) {
    error(403);
}

if (isset($argv[2]) && !empty($argv[2])) {
    $TorrentName = $argv[2];
} else {
    echo "You must specify a file path!\n";
    die();
}

ini_set('upload_max_filesize', MAX_FILE_SIZE_BYTES);
ini_set('max_file_uploads', 100);

define('QUERY_EXCEPTION', true); // Shut up debugging

//******************************************************************************//
//---------------- Find and read the file into a torrent object --------------- //
// This is used if the form doesn't validate, and when the time comes to enter  //
// it into the database.							//

$File = fopen($TorrentName, 'rb'); // open file for reading
$Contents = fread($File, 10000000);
$Tor = new TORRENT($Contents); // New TORRENT object
fclose($File);

// Although this fixes bugs produced by invalid torrent files, it also affects their info hash. :(
//$Tor->use_strict_bencode_specification(); // Fix torrents that do not follow the bencode specification.
$Tor->set_announce_url('ANNOUNCE_URL'); // We just use the string "ANNOUNCE_URL"

// $Private is true or false. true means that the uploaded torrent was private, false means that it wasn't.
$Private = $Tor->make_private();
// The torrent is now private.

//******************************************************************************//
//------------------------- Attempt to import the file -------------------------//

$InfoHash = pack("H*", sha1($Tor->Val['info']->enc()));
$HexHash = bin2hex($InfoHash);
$DB->query("SELECT ID FROM torrents WHERE info_hash='" . db_string($InfoHash) . "'");
if ($DB->record_count() > 0) {
    list($ID) = $DB->next_record();
    $DB->query("SELECT TorrentID FROM torrents_files WHERE TorrentID = " . $ID);
    if ($DB->record_count() > 0) {
	echo "Torrent ($HexHash)already exists in the database!\n";
	die();
    } else {
        //One of the lost torrents.
        $DB->query("INSERT INTO torrents_files (TorrentID, File) VALUES ($ID, '" . db_string($Tor->dump_data()) . "')");
        list($TotalSize, $FileList) = $Tor->file_list();

        // Use this section to control freeleeches
        if ($TotalSize >= AUTO_FREELEECH_SIZE) {
            $FreeTorrent='1';
        } else {
            $FreeTorrent='0';
        }
        $NumFiles = count($FileList);

        $TmpFileList = array();
        foreach ($FileList as $File) {
            list($Size, $Name) = $File;

            if (preg_match('/INCOMPLETE~\*/i', $Name)) {
                $Err = 'The torrent contained one or more forbidden files (' . $Name . ').';
            }
            if (preg_match('/\?/i', $Name)) {
                $Err = 'The torrent contains one or more files with a ?, which is a forbidden character. Please rename the files as necessary and recreate the .torrent file.';
            }
            if (preg_match('/\:/i', $Name)) {
                $Err = 'The torrent contains one or more files with a :, which is a forbidden character. Please rename the files as necessary and recreate the .torrent file.';
            }
            if (preg_match('/\.torrent/i', $Name)) {
                $Err = 'The torrent contains one or more .torrent files inside the torrent. Please remove all .torrent files from your upload and recreate the .torrent file.';
            }
            if (!preg_match('/\./i', $Name)) {
            //if ( strpos($Name, '.')===false) {
                $Err = "The torrent contains one or more files without a file extension. Please remove or rename the files as appropriate and recreate the .torrent file.<br/><strong>note: this can also be caused by selecting 'create encrypted' in some clients</strong> in which case please recreate the .torrent file without encryption selected.";
            }
            // Add file and size to array
            $TmpFileList [] = $Name . '{{{' . $Size . '}}}'; // Name {{{Size}}}
        }
        if(!empty($Err)) {
            echo "\n\n$Err\n\n";
            die();
        }

        $FileString = "'" . db_string(implode('|||', $TmpFileList)) . "'";
        $DB->query("UPDATE torrents SET FileCount=$NumFiles, FileList=$FileString, Size=$TotalSize, FreeTorrent=$FreeTorrent WHERE ID=$ID");


	echo "Torrent ($HexHash) imported\n";
    }
} else {
    echo "Torrent ($HexHash) not found!\n";
}
