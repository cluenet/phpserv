<?PHP
	class socks {
		protected static $socket;

		function connect ($ip,$port) {
			global $modules;
			$this->socket = stream_socket_client('tcp://'.$ip.':'.$port, $errno, $errstr, 30);
			if (!$this->socket) {
				logit('Error: '.$errno.': '.$errstr);
				return 0;
			} else {
				return 1;
			}
		}

		function write ($data) {
			global $modules;
			event('raw_out',$data);
			fwrite($this->socket, $data."\n");
		}

		function read () {
			$tmp = fgets($this->socket);
			return $tmp;
		}

		function timeout ($time) {
			stream_set_timeout($this->socket, $time);
		}

		function eof () {
			return feof($this->socket);
		}

		function disconnect () {
			fclose($this->socket);
		}
	}
?>
