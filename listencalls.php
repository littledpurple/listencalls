#!/usr/bin/php-cgi -q
<?php
//Set time limit
set_time_limit(300);
//Include PHPAGI
require('phpagi.php');
//Development version. Set to none on production.
error_reporting(E_ALL);

$date = date('Y/m/d');
$dirpath = "/var/spool/asterisk/monitor/".$date."/*.wav";
$files = array();
$files = glob($dirpath);

#сортируем от новых к старым
usort($files, function($x, $y) {
    return filemtime($x) < filemtime($y);
});

#убираем внутренние звонки
$files = array_filter($files,
  function($item) {
    return strpos($item, 'internal') === false;
  });
$files = array_filter($files,
  function($item) {
    return strpos($item, 'external') === false;
  });

#передаём список файлов в астериск
$agi = new AGI();
$agi->answer();

foreach($files as $path){
    #$agi->exec()
    #$agi->exec('Playback','$item');
    $path = substr($path, 0, -4);
    $agi->exec("Playback",$path);
}
?>

