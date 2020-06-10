#!/usr/bin/php-cgi -q
<?php
//Set time limit
set_time_limit(300);

//Include PHPAGI
require('phpagi.php');
error_reporting(0);

$date = date('Y/m/d'); #today's date to form the directory path
$dirpath = "/var/spool/asterisk/monitor/".$date."/*.wav"; #forming call records file path
$files = array(); #files - array with all recordings
$play_files = array(); #play_files - array after excluding empty recordings (less than 44 bytes)
$counter = 0; #counter for iterating through array
$files = glob($dirpath); # forming array with call recordings


// sorting from new to old
usort($files, function($x, $y) {
    return filemtime($x) < filemtime($y);
});

// removing internal calls and copies from the array
$files = array_filter($files,
  function($item) {
    return strpos($item, 'internal') === false;
  });
$files = array_filter($files,
  function($item) {
    return strpos($item, 'external') === false;
  });

// removing empty calls from the array and forming clean array with files to play
foreach ($files as $file){
    if (filesize($file)>44){
        array_push($play_files, $file);
    }
}

// AGI part
$agi = new AGI(); # creating new AGI session
$agi->answer(); # answering call
$rec_count=count($play_files); # rec_count - count of records
for ($i = 1; $i < $rec_count; $i++) { # uterating through records
    $agi->say_number($i); 
    $path = substr($play_files[$i], 0, -4); # removing file extension
    $buffer=''; # buffer - variable for storing numbers recieved by DTMF
    $dtmfwait=30; # wait time after file playback
    $agi->fastpass_get_data($buffer,$path,$dtmfwait,1); # waiting for DTMF while playing file
    switch ($buffer){ # 1 - previous; 3 - next; 4 - return by 5; 6 - skip 5; 7 - return by 10; 9 - skip 10
        case 1:
            $go=-2;
            break;
        case 4:
            $go=-6;
            break;
        case 6:
            $go=4;
            break;
        case 7:
            $go=-11;
            break;
        case 9:
            $go=9;
            break;
        default:
            $go=0;
            break;
    }
    if (($i + $go) >= 0) { # check if next counter value will be in array
        $i = $i + $go;
    }
}
$agi->hangup(); # hanging up call
?>