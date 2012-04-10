<?php

/**
* REQUIRED : lame library
* From : https://github.com/afreiday/php-waveform-png
*/
class MP3Waveform
{
	public static $LAME_PATH = "/usr/local/bin/lame";
	public static $DETAIL = 1;
	public static $VERBOSE = false;

	private $_file_path;
	private $_tmp_name;
	private $_packets;
	private $_autostart;
	
	function __construct($file_path, $autostart = true)
	{
		$this->_file_path = $file_path;
		$this->_autostart = $autostart;

		$this->init();
	}

	private function init()
	{
		$this->_packets = array();

		if($this->_autostart)
		{
			$this->start();
		}
	}

	public function start()
	{
		if(self::$VERBOSE) {
			$time_start = microtime(true);
		}
		$this->convertToWav();
		if(self::$VERBOSE) {
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			error_log("convertToWav duration : " . $time) . "s";
			$time_start = microtime(true);
		}
		$this->process();
		if(self::$VERBOSE) {
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			error_log("process duration : " . $time) . "s";
		}
	}

	private function convertToWav()
	{
		$this->copy_original_mp3();
		self::convertMP3ToWav($this->_tmp_name);
		$this->deleteTemporaryFiles();
	}

	private function copy_original_mp3()
	{
		$this->_tmp_name = substr(md5(time()), 0, 10);
		copy($this->_file_path, $this->_tmp_name . "_o.mp3");
	}

	private function deleteTemporaryFiles()
	{
		unlink($this->_tmp_name . "_o.mp3");
		unlink($this->_tmp_name . ".mp3");
	}

	private function process()
	{
		$filename = $this->_tmp_name . ".wav";

		/**
		 * Below as posted by "zvoneM" on
		 * http://forums.devshed.com/php-development-5/reading-16-bit-wav-file-318740.html
		 * as findValues() defined above
		 * Translated from Croation to English - July 11, 2011
		 */
		$handle = fopen($filename, "r");
		// wav file header retrieval
		$heading[] = fread($handle, 4);
		$heading[] = bin2hex(fread($handle, 4));
		$heading[] = fread($handle, 4);
		$heading[] = fread($handle, 4);
		$heading[] = bin2hex(fread($handle, 4));
		$heading[] = bin2hex(fread($handle, 2));
		$heading[] = bin2hex(fread($handle, 2));
		$heading[] = bin2hex(fread($handle, 4));
		$heading[] = bin2hex(fread($handle, 4));
		$heading[] = bin2hex(fread($handle, 2));
		$heading[] = bin2hex(fread($handle, 2));
		$heading[] = fread($handle, 4);
		$heading[] = bin2hex(fread($handle, 4));

		// wav bitrate 
		$peek = hexdec(substr($heading[10], 0, 2));
		$byte = $peek / 8;

		// checking whether a mono or stereo wav
		$channel = hexdec(substr($heading[6], 0, 2));

		$ratio = ($channel == 2 ? 40 : 80);

		// start putting together the initial canvas
		// $data_size = (size_of_file - header_bytes_read) / skipped_bytes + 1
		$data_size = floor((filesize($filename) - 44) / ($ratio + $byte) + 1);
		$data_point = 0;

		while(!feof($handle) && $data_point < $data_size){
			if ($data_point++ % self::$DETAIL == 0) {
				$bytes = array();

				// get number of bytes depending on bitrate
				for ($i = 0; $i < $byte; $i++)
				{
					$bytes[$i] = fgetc($handle);
				}

				switch($byte){
					
					// get value for 8-bit wav
					case 1:
						$data = findValues($bytes[0], $bytes[1]);
						break;
					
					// get value for 16-bit wav
					case 2:
						if(ord($bytes[1]) & 128)
						{
							$temp = 0;
						}
						else
						{
							$temp = 128;
						}
		
						$temp = chr((ord($bytes[1]) & 127) + $temp);
						$data = floor($this->findValues($bytes[0], $temp) / 256);
						break;
				}

				// skip bytes for memory optimization
				fseek($handle, $ratio, SEEK_CUR);

				// data values can range between 0 and 255
				$this->_packets[] = $data;
			}
			else
			{
				// skip this one due to lack of detail
				fseek($handle, $ratio + $byte, SEEK_CUR);
			}
		}

		// close and cleanup
		fclose($handle);

		// delete the processed wav file
		$a = unlink($filename);
		error_log($a ? "deleted" : "not deleted");
	}

	private function findValues($byte1, $byte2)
	{
		$byte1 = hexdec(bin2hex($byte1));
		$byte2 = hexdec(bin2hex($byte2));
		return ($byte1 + ($byte2*256));
	}

	public function writePacketsInFile($file)
	{
		if(self::$VERBOSE) {
			$time_start = microtime(true);
		}
		$fp = fopen($file, 'w');
		foreach($this->_packets as $p)
		{
			$packet = pack("C*", $p);
			fwrite($fp, $packet);
		}
		fclose($fp);
		if(self::$VERBOSE) {
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			error_log("writePacketsInFile duration : " . $time) . "s";
		}
	}

    /**
     * convert mp3 to wav using lame decoder
     * First, resample the original mp3 using as mono (-m m), 16 bit (-b 16), and 8 KHz (--resample 8)
     * Secondly, convert that resampled mp3 into a wav
     * We don't necessarily need high quality audio to produce a waveform, doing this process reduces the WAV
     * to it's simplest form and makes processing significantly faster
     */
	static public function convertMP3ToWav($filename)
	{
		exec(self::$LAME_PATH . " {$filename}_o.mp3 -m m -S -f -b 16 --resample 8 {$filename}.mp3 && " . self::$LAME_PATH . " -S --decode {$filename}.mp3 {$filename}.wav");
	}

}

?>