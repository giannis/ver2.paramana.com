<?php
    if($_GET['temp'] == 'all') {
        ob_start();
        $files = array();
        $files = array_merge($files, scandirRecursive(dirname(__FILE__)));
        $last_modified_time = '';

        foreach($files as $file) {
            $filemtime = filemtime($file);
            if(empty($last_modified_time) || $last_modified_time < $filemtime)
                $last_modified_time = $filemtime;
        }
    }
    else {
        if(!file_exists($_GET['temp'] . '_temp.xml'))
            exit();
        $last_modified_time = filemtime($_GET['temp'] . '_temp.xml');
    }
    
    if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == $last_modified_time)) {
        // Client's cache IS current, so we just respond '304 Not Modified'.
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified_time).' GMT', true, 304);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 7 * 24 * 60 * 60) . ' GMT');
        exit();
    }
    else {
        if($_GET['temp'] == 'all') {
            $tempFile = array();
            foreach($files as $file) {
                array_push($tempFile, '<!%-%-temp=' . basename(preg_replace('/\.xml|\.html/i', '', $file)) . '-%-%>' . preg_replace('/(\r\n\s*)|\n\s*/', '', file_get_contents($file)));
            }
            $tempFile = implode('<!%-%-temp-%-%>', $tempFile);
        }
        else {
            $tempFile = preg_replace('/(\r\n\s*)|\n\s*/', '', file_get_contents($_GET['temp'] . '_temp.xml'));
            header('Content-Length: '. filesize($_GET['temp'] . '_temp.xml'));
        }
        
        // generate unique ID
        $hash = md5($tempFile);

        header("ETag: \"{$hash}\"");
        header('Content-Type: text/plain; charset=utf-8');
        header("Last-Modified: ". gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 7 * 24 * 60 * 60) . ' GMT');
        echo $tempFile;
        
        if($_GET['temp'] == 'all') {
            header('Content-Length: ' . ob_get_length());
            ob_end_flush();
        }
        exit();
    }
    /**
     * scans recursively a directory and returns the file names
     *
     * @param $str (string) the directory to scan
     * @return array of file names
     *
     */
    function scandirRecursive($dir) {
        $files = array();
        foreach (scandir($dir) as $file) {
            if ($file == '.' || $file == '..' || $file == '.svn' || $file == 'temp.php' || !preg_match('/.xml|.html/', $file))
                continue;
            if (is_dir($file))
                $files = array_merge($files, scandirRecursive($file));
            else
                $files[] = "$dir/$file";
        }
        return $files;
    }
?>