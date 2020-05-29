<?php
/*!
 * paramana.com
 * Version: 1.1
 * Started: 20-10-2010
 * Updated: 20-10-2010
 * http://www.paramana.com
 *
 * Copyright (c) 2010 paramana.com
 *
 */

/**
 * include files
 */
//include_once(dirname(__FILE__) . '/../config/config_.php');
//include_once(dirname(__FILE__) . '/class/db_class.php');
//include_once(dirname(__FILE__) . '/functions.php');

/**
 * The php class of slim cms
 *
 * @version 1.0
 * @since 1.0
 * @author paramana.com <info@paramana.com>
 * @copyright 2010 paramana.com
 */
class SlimApp {
    /**
     * Execute the request
     *
     * @param array $args
     */
    static function execute($args) {
        if(empty($_GET['cmd']) && $_POST['cmd'])
            exit();
        global $time;
        $time = microtime(true);

        $command = !empty($_GET['cmd']) ? $_GET['cmd'] : (!empty($_POST['cmd']) ? $_POST['cmd'] : '');

        $ctl = new self($args);
        $ctl->$command();

        if (!headers_sent())
            header('X-Time: ' . (microtime(true) - $time));
    }

    /**
     * Returns the contents of the requested page
     *
     * @return json
     *
     */
    public function getpage() {
        $pageId = (isset($_GET['id'])   && !empty($_GET['id']))   ? $_GET['id']   : NULL;
        $lang   = (isset($_GET['lang']) && !empty($_GET['lang'])) ? $_GET['lang'] : 'gr';

        if(!$pageId) {
            header('Content-Type: text/plain; charset=utf-8');
            throw new Exception("command section");
            return;
        }
        if (file_exists(dirname(__FILE__) . '/../data/json/' . $lang . '-' . $pageId . '.json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo file_get_contents(dirname(__FILE__) . '/../data/json/' . $lang . '-' . $pageId . '.json');
        }
        else {
            header('Content-Type: text/plain; charset=utf-8');
            throw new Exception("command section");
            return;
        }
    }

    /**
     * Returns the contents of the requested page
     *
     * @return json
     *
     */
    public function getuilang() {
        $lang = (isset($_GET['lang']) && !empty($_GET['lang'])) ? $_GET['lang'] : 'gr';

        if (file_exists(dirname(__FILE__) . '/../data/json/' . $lang . '-ui_elemets.json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo file_get_contents(dirname(__FILE__) . '/../data/json/' . $lang . '-ui_elemets.json');
        }
        else {
            header('Content-Type: text/plain; charset=utf-8');
            throw new Exception("command section");
            return;
        }
    }
    
    /**
     * Returns an xml feed from the twitter account of delivericious
     *
     * @return xml the parsed xml of the twitter feed
     *
     */
    public function tweetfeed() {
        header('Content-Type: application/xml; charset=utf-8');
        $tweetNum  = 2;
        $tweetPage = 1;

        if (isset($_GET['num']))
            $tweetNum = $_GET['num'];

        if (isset($_GET['page']))
            $tweetPage = $_GET['page'];

        if (file_exists(dirname(__FILE__) . '/../data/tweeterXml/twitterFeed_' . $tweetNum . '_' . $tweetPage . '.xml')) {
            $cache = new SimpleXMLElement(file_get_contents(dirname(__FILE__) . '/../data/tweeterXml/twitterFeed_' . $tweetNum . '_' . $tweetPage . '.xml'));
            $time = $cache->attributes()->timestamp;
        }
        if (!isset($time) || time() >= ($time + 60 * 60)) {
            $url = 'http://twitter.com/statuses/user_timeline/paramanacom.xml?count=' . $tweetNum . '&page=' . $tweetPage;
            $feed = new SimpleXMLElement(file_get_contents($url));
            $feed->addAttribute('page', $tweetPage);
            $result = $feed->xpath("//user/statuses_count");
            if ($result && $result[0])
                $feed->addAttribute('pages', floor($result[0] / $tweetNum));
            $feed->addAttribute('timestamp', time());
            if (count($feed->children()) > 0)
                file_put_contents(dirname(__FILE__) . '/../data/tweeterXml/twitterFeed_' . $tweetNum . '_' . $tweetPage . '.xml', $feed->asXml());

            $hash = md5($feed->asXml());

            header("ETag: \"{$hash}\"");
            header('Content-Length: ' . filesize(dirname(__FILE__) . '/../data/tweeterXml/twitterFeed_' . $tweetNum . '_' . $tweetPage . '.xml'));
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60 * 60) . ' GMT');
            echo $feed->asXml();
        }
        else {
            $hash = md5($cache->asXml());
            $last_modified_time = filemtime(dirname(__FILE__) . '/../data/tweeterXml/twitterFeed_' . $tweetNum . '_' . $tweetPage . '.xml');

            header("ETag: \"{$hash}\"");
            header('Content-Length: ' . filesize(dirname(__FILE__) . '/../data/tweeterXml/twitterFeed_' . $tweetNum . '_' . $tweetPage . '.xml'));
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
            header('Expires: ' . gmdate('D, d M Y H:i:s', $last_modified_time + 60 * 60) . ' GMT');
            echo $cache->asXml();
        }
    }
}

if(!isset($slim)) {
    $slim = new SlimApp;
}
?>