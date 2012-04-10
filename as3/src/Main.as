package
{
	import com.sosappy.sound.WavdatParser;
	
	import flash.display.Shape;
	import flash.display.Sprite;
	import flash.display.StageAlign;
	import flash.display.StageScaleMode;
	import flash.events.Event;
	import flash.net.URLLoader;
	import flash.net.URLLoaderDataFormat;
	import flash.net.URLRequest;
	import flash.utils.ByteArray;
	import flash.utils.setTimeout;
	
	public class Main extends Sprite
	{
		private var _spectrum:Shape;
		private var _ratios:Vector.<Number>;
		private var _currentH:Number;
		
		public function Main()
		{
			stage.align = StageAlign.TOP_LEFT;
			stage.scaleMode = StageScaleMode.NO_SCALE;
			
			var urlLoader : URLLoader = new URLLoader();
			urlLoader.dataFormat = URLLoaderDataFormat.BINARY;
			urlLoader.addEventListener(Event.COMPLETE, onComplete);
			urlLoader.load(new URLRequest("test.wavdat"));
		}
		
		protected function onComplete(event:Event):void
		{
			var urlLoader : URLLoader = event.target as URLLoader;
			urlLoader.removeEventListener(Event.COMPLETE, onComplete);
			var data : ByteArray = urlLoader.data;
			_ratios = WavdatParser.ratios(data);
			
			_spectrum = new Shape();
			addChild(_spectrum);
			_spectrum.x = 100;
			_spectrum.y = 100;
			
			_currentH = 0;
			
			var w : Number = 900;
			var h : Number = 300;
			
			_spectrum.graphics.lineStyle(1, 0x0000ff);
			_spectrum.graphics.moveTo(0, h * .5);
			_spectrum.graphics.lineTo(w, h * .5);
			_spectrum.graphics.lineStyle(0);
			
			setTimeout(start, 2000);
		}
		
		private function start() : void {
			addEventListener(Event.ENTER_FRAME, redraw);
		}
		
		private function redraw(event : Event) : void {
			var w : Number = 900;
			var h : Number = 300;
			
			_currentH += (300 - _currentH) / 20;
			
			_spectrum.graphics.clear();
			
			_spectrum.graphics.lineStyle(1, 0x0000ff);
			_spectrum.graphics.moveTo(0, h * .5);
			
			var i : Number = 0;
			var index : int;
			// the smaller the number is, more details we have
			var precision : Number = .5;
			for(; i <= w; i += precision) {
				index = i / w * (_ratios.length - 1);
				_spectrum.graphics.lineTo(i, (_ratios[index] - .5) * _currentH + h * .5);
			}
			
			_spectrum.graphics.lineTo(w, h * .5);
			_spectrum.graphics.lineStyle(0);
		}
	}
}