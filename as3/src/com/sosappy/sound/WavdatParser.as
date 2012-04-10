package com.sosappy.sound
{
	import flash.utils.ByteArray;

	public class WavdatParser
	{
		static public function ratios(data : ByteArray) : Vector.<Number>
		{
			var r : Vector.<Number> = new Vector.<Number>(data.length, true);
			data.position = 0;
			while(data.position < data.length) {
				r[data.position] = data.readUnsignedByte() / 255;
			}
			return r;
		}
	}
}