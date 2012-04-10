<?php

require_once("MP3Waveform.php");

$w = new MP3Waveform("sound.mp3");
MP3Waveform::$VERBOSE = true;
$w->start();
$w->writePacketsInFile("test.wavdat");

?>