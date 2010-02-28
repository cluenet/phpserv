<?php
	class AccountProperties {
		protected $account;
		
		public static function newFromAccount( $account ) {
			return new self( $account );
		}
		
		protected function __construct( $account ) {
			$this->account = $account;
		}
	
		public function search( $key = null, $value = null, $visibility = null, $section = null ) {
			
			$sql = 'SELECT `id` FROM `access_properties` WHERE';
			
			$sql .= ' `uid` = ' . MySQL::escape( $this->account->id );
			
			if( $key !== null )
				$sql .= ' AND `key` = ' . MySQL::escape( $key );
			
			if( $value !== null )
				$sql .= ' AND `value` = ' . MySQL::escape( $value );
			
			if( $visibility !== null )
				$sql .= ' AND `visibility` = ' . MySQL::escape( $visibility );
			
			if( $section !== null )
				$sql .= ' AND `section` = ' . MySQL::escape( $section );
			
			$result = MySQL::sql( $sql );
			$results = Array();
			
			while( $row = MySQL::get( $result ) )
				$results[] = AccountProperty::newFromId( $row[ 'id' ] );
			
			return $results;
		}
	
		public function get( $key, $section = 'core' ) {
			$data = $this->listaccountproperties( $key, null, null, $section );
			return $data[ 0 ];
		}
	}
?>