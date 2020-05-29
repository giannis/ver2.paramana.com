<?php
if(!isset($_GET["section"])) {
    header('Content-Type: text/plain; charset=utf-8');
    throw new Exception("command section");
    exit;
}

require_once(dirname(__FILE__) . "/../lib/slimApp.php");

$section = strtolower($_GET["section"]);

$_GET['data_format'] = false;//clear it please
$_GET['cmd']         = '';

switch ($section) {
    case 'get_page':
        $_GET['cmd']         = 'getpage';
        $_GET['data_format'] = 'json';
        $slim->execute($_GET);
        break;
    case 'get_uilang':
        $_GET['cmd']         = 'getuilang';
        $_GET['data_format'] = 'json';
        $slim->execute($_GET);
        break;
    case 'tweetfeed':
        global $slim;
        $_GET['cmd']         = 'tweetfeed';
        $slim->execute($_GET);
        break;
}
?>
