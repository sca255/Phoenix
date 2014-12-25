<?php

// load tracker core
require './phoenix.php';

// Verify Request //////////////////////////////////////////////////////////////////////////////////

// strip auto-escaped data
if (get_magic_quotes_gpc())
{
	$_GET['info_hash'] = stripslashes($_GET['info_hash']);
	$_GET['peer_id'] = stripslashes($_GET['peer_id']);
}

// 20-bytes - info_hash
// sha-1 hash of torrent metainfo
if (!isset($_GET['info_hash']) || strlen($_GET['info_hash']) != 20) exit;

// 20-bytes - peer_id
// client generated unique peer identifier
if (!isset($_GET['peer_id']) || strlen($_GET['peer_id']) != 20) exit;

// integer - port
// port the client is accepting connections from
if (!(isset($_GET['port']) && is_numeric($_GET['port']))) tracker_error('client listening port is invalid');

// integer - left
// number of bytes left for the peer to download
if (isset($_GET['left']) && is_numeric($_GET['left'])) $_SERVER['tracker']['seeding'] = ($_GET['left'] > 0 ? 0 : 1); else tracker_error('client data left field is invalid');

// integer boolean - compact - optional
// send a compact peer response
// http://bittorrent.org/beps/bep_0023.html
if (!isset($_GET['compact']) || $_SERVER['tracker']['force_compact']) $_GET['compact'] = 1; else $_GET['compact'] += 0;

// integer boolean - no_peer_id - optional
// omit peer_id in dictionary announce response
if (!isset($_GET['no_peer_id'])) $_GET['no_peer_id'] = 0; else $_GET['no_peer_id'] += 0;

// string - ip - optional
// ip address the peer requested to use
if (isset($_GET['ip']) && $_SERVER['tracker']['external_ip'])
{
	// dotted decimal only
	$_GET['ip'] = trim($_GET['ip'],'::ffff:');
	if (!ip2long($_GET['ip'])) tracker_error('invalid ip, dotted decimal only');
}
// set ip to connected client
elseif (isset($_SERVER['REMOTE_ADDR'])) $_GET['ip'] = trim($_SERVER['REMOTE_ADDR'],'::ffff:');
// cannot locate suitable ip, must abort
else tracker_error('could not locate clients ip');

// integer - numwant - optional
// number of peers that the client has requested
if (!isset($_GET['numwant'])) $_GET['numwant'] = $_SERVER['tracker']['default_peers'];
elseif (($_GET['numwant']+0) > $_SERVER['tracker']['max_peers']) $_GET['numwant'] = $_SERVER['tracker']['max_peers'];
else $_GET['numwant'] += 0;

// Handle Request //////////////////////////////////////////////////////////////////////////////////

// open database
peertracker::open();

// make info_hash & peer_id SQL friendly
$_GET['info_hash'] = peertracker::$api->escape_sql($_GET['info_hash']);
$_GET['peer_id']   = peertracker::$api->escape_sql($_GET['peer_id']);

// announce peers
peertracker::peers();

// track client
peertracker::event();

// garbage collection
peertracker::clean();

// close database
peertracker::close();

?>