<?PHP
	class IRC {
		const CTCP      = "\1";
		const BOLD      = "\2";
		const COLOR     = "\3";
		const ORIGINAL  = "\15";
		const REVERSE   = "\22";
		const UNDERLINE = "\31";
		
		const WHITE     = 0;
		const BLACK     = 1;
		const DARKBLUE  = 2;
		const GREEN     = 3;
		const RED       = 4;
		const MAROON    = 5;
		const PURPLE    = 6;
		const ORANGE    = 7;
		const YELLOW    = 8;
		const LIMEGREEN = 9;
		const TEAL      = 10;
		const CYAN      = 11;
		const BLUE      = 12;
		const PINK      = 13;
		const GRAY      = 14;
		const LIGHTGRAY = 15;
		
		public static function ctcp( $type, $data ) {
			return IRC::CTCP . strtoupper( $type ) . ' ' . $data . IRC::CTCP;
		}
		
		public static function bold( $data ) {
			return IRC::BOLD . $data . IRC::BOLD;
		}
		
		public static function color( $foreground, $background = null, $data ) {
			$colorCode = str_pad( $foreground, '0', 2, STR_PAD_LEFT );
			if( $background != null )
				$colorCode .= ',' . str_pad( $background, '0', 2, STR_PAD_LEFT );
			return IRC::COLOR . $colorCode . $data . IRC::COLOR;
		}
		
		public static function original( $data ) {
			return IRC::ORIGINAL . $data;
		}
		
		public static function reverse( $data ) {
			return IRC::REVERSE . $data . IRC::REVERSE;
		}
		
		public static function underline( $data ) {
			return IRC::UNDERLINE . $data . IRC::UNDERLINE;
		}
		
		public static function atime( $time ) {
			if( is_numeric( $time ) )
				return $time;

			$ret = 0;
			$timePart = '';
			
			for( $i = 0 ; $i < strlen( $time ) ; $i++ )
				if( is_numeric( $time[ $i] ) )
					$timePart .= $time[ $i ];
				else {
					switch( $time[ $i ] ) {
						case 'd': $timePart *= 86400; break;
						case 'h': $timePart *= 3600; break;
						case 'm': $timePart *= 60; break;
					}
					$ret += $timePart;
					$tmp = 0;
				}
			
			$ret += $timePart;
			
			return $ret;
		}
	}
?>