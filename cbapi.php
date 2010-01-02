<?PHP
	$port = 58945;

	function getdb() {
		return unserialize( file_get_contents( '/home/phpserv/phpserv/cb_users.db' ) );
	}

	function printheader() {
		return 'Nick:Points:IgnoreFlag:AdminFlag:VerboseSetting:VDeductionsSetting:VLogSetting:FirstSeenTime:NormalSentences:AbnormalSentences:NoVowels:OLDLameAbbreviations:AdministrativelyChanged:CluefulSentences:AllCaps:LameAbbreviations:LowercaseI:Profanity:NonprintableASCII' . "\n";
	}

	function printusage() {
		return "Usage:\n"
			. "  cluebot dump [Nick1,Nick2,...]\n"
			. "  cluebot points [Nick1,Nick2,...]\n"
			. "  cluebot shortpoints <Nick>\n"
			. "  cluebot dumpheader\n";
	}

	function printfullentry( $nick, $entry ) {
		return $nick
			. ':' . $entry[ 'points' ]
			. ':' . $entry[ 'ignore' ]
			. ':' . $entry[ 'admin' ]
			. ':' . $entry[ 'verbose' ]
			. ':' . $entry[ 'vdedo' ]
			. ':' . $entry[ 'vlog' ]
			. ':' . $entry[ 'creation' ]
			. ':' . $entry[ 'log' ][ 'Normal sentence +1' ]
			. ':' . $entry[ 'log' ][ 'Abnormal sentence -1' ]
			. ':' . $entry[ 'log' ][ 'No vowels -30' ]
			. ':' . $entry[ 'log' ][ 'Use of i, r, R, u, or U -40' ]
			. ':' . $entry[ 'log' ][ 'Administratively changed' ]
			. ':' . $entry[ 'log' ][ 'Clueful sentence +2' ]
			. ':' . $entry[ 'log' ][ 'All caps -20' ]
			. ':' . $entry[ 'log' ][ 'Use of r, R, u, or U -40' ]
			. ':' . $entry[ 'log' ][ 'Lower-case personal pronoun -5' ]
			. ':' . $entry[ 'log' ][ 'Use of profanity -20' ]
			. ':' . $entry[ 'log' ][ 'Use of non-printable ascii characters -5' ]
			. "\n";
	}

	function printpointsentry( $nick, $entry ) {
		return $nick
			. ':' . $entry[ 'points' ]
			. "\n";
	}

	function printshortpointsentry( $nick, $entry ) {
		$p = $entry[ 'points' ];
		if( !isset( $entry[ 'points' ] ) or !is_numeric( $entry[ 'points' ] ) or $entry[ 'points' ] == '')
			$p = 0;
		return $p . "\n";
	}

	function printentry( $nick, $what, $entry ) {
		switch( $what ) {
			case 'full':
				return printfullentry( $nick, $entry );
			case 'points':
				return printpointsentry( $nick, $entry );
			case 'shortpoints':
				return printshortpointsentry( $nick, $entry );
			case 'header':
				return printheader();
			case 'usage':
				return printusage();
		}
	}

	function dumpentries( $sock, $what, $nicks ) {
		$data = getdb();

		if( $nicks == NULL )
			foreach( $data as $nick => $entry )
				fwrite( $sock, printentry( $nick, $what, $entry ) );
		elseif( is_array( $nicks ) )
			foreach( $nicks as $nick )
				fwrite( $sock, printentry( $nick, $what, $data[ strtolower( $nick ) ] ) );
		else
			dumpentries( $sock, $what, explode( ',', $nicks ) );
	}

	$serversock = stream_socket_server( 'tcp://0.0.0.0:'.$port, $errno, $errstr );
	$clients = array();

	while( true ) {
		$read = array_merge( array( $serversock ), $clients );
		$waiting = stream_select( $read, $write = NULL, $except = NULL, NULL );
		if( $waiting !== FALSE ) {
			foreach( $read as $key => $sock ) {
				if( ( !is_resource( $sock ) or feof( $sock ) ) and in_array( $sock, $clients, true ) ) {
					fclose( $sock );
					unset( $read[ $key ] );
					foreach( $clients as $k => $client )
						if( $sock === $client )
							unset( $clients[ $k ] );
				}
				if( $sock === $serversock )
					$clients[] = stream_socket_accept( $sock );
				elseif( in_array( $sock, $clients, true ) ) {
					$line = str_replace( array( "\r", "\n" ), '', fgets( $sock, 4096 ) );
					$parts = explode( ' ', $line );
					$command = $parts[ 0 ];

					switch( strtolower( $command ) ) {
						case 'dump':
							if( isset( $parts[ 1 ] ) )
								dumpentries( $sock, 'full', $parts[ 1 ] );
							else
								dumpentries( $sock, 'full', NULL );
							break;
						case 'points':
							if( isset( $parts[ 1 ] ) )
								dumpentries( $sock, 'points', $parts[ 1 ] );
							else
								dumpentries( $sock, 'points', NULL );
							break;
						case 'shortpoints':
							if( isset( $parts[ 1 ] ) )
								dumpentries( $sock, 'shortpoints', $parts[ 1 ] );
							else
								fwrite( $sock, printentry( NULL, 'usage', NULL ) );
							break;
						case 'dumpheader':
							fwrite( $sock, printentry( NULL, 'header', NULL ) );
							break;
						default:
							fwrite( $sock, printentry( NULL, 'usage', NULL ) );
							break;
					}
					
					foreach( $clients as $k => $client )
						if( $sock === $client )
							unset( $clients[ $k ] );

					fclose( $sock );
					unset( $read[ $key ] );
				}
			}
		}
	}
?>
