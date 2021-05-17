<?php

namespace App\Http\Controllers;

class PersonController extends Controller {

	public function getAll() {
		// LDAP connection establishment
		$serverUrl = env('LDAP_SERVER_URL');
		$bindDn = env('LDAP_BIND_DN');
		$bindPassword = env('LDAP_BIND_PASSWORD');
		$connection = ldap_connect($serverUrl);
		if ( !$connection ) {
			exit();
		}
		ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		$bind = ldap_bind($connection, $bindDn, $bindPassword);
		if ( !$bind ) {
			exit();
		}
		// LDAP search
		$searchBaseDn = env('LDAP_SEARCH_BASE_DN');
		$filter = '(uid=*)';
		$result = ldap_search($connection, $searchBaseDn, $filter);
		$entries = ldap_get_entries($connection, $result);
		// LDAP connection closure
		ldap_close($connection);
		// Cleaning and return of result
		return $this->cleanUpEntry($entries);
	}

	private function cleanUpEntry($entry) {
		$retEntry = array();
		for ( $i=0; $i<$entry['count']; $i++ ) {
			if ( is_array($entry[$i]) ) {
				$subtree = $entry[$i];
				if ( !empty($subtree['dn']) and !isset($retEntry[$subtree['dn']]) ) {
					$retEntry[$subtree['dn']] = $this->cleanUpEntry($subtree);
				}
				else {
					$retEntry[] = $this->cleanUpEntry($subtree);
				}
			}
			else {
				$attribute = $entry[$i];
				if ( $entry[$attribute]['count']==1 ) {
					$retEntry[$attribute] = $entry[$attribute][0];
				} else {
					for ( $j=0; $j<$entry[$attribute]['count']; $j++ ) {
						$retEntry[$attribute][] = $entry[$attribute][$j];
					}
				}
			}
		}
		return $retEntry;
	}

}
