<?php 
    $page        = (isset($_GET['page']) && !empty($_GET['page']) && $_GET['page'] != 'index') ? $_GET['page'] : NULL;
    $parentpage  = (isset($_GET['parentpage']) && !empty($_GET['parentpage']) && $_GET['parentpage'] != 'index') ? $_GET['parentpage'] : 'home';
    $lang        = (isset($_GET['lang']) && !empty($_GET['lang'])) ? $_GET['lang'] : NULL;

    if(!$page) {
        $page   = $parentpage;
        $tempid = $page;
    }
    else if($parentpage && $page) {
        $tempid = 'subpage-' . $parentpage;
    }

    if(!file_exists(dirname(__FILE__) . '/temp/' . $tempid . '_page.html')) {
        $page   = '404';
        $tempid = '404';
        header("HTTP/1.0 404 Not Found");
    }
    
    $temp  = trim(file_get_contents(dirname(__FILE__) . '/temp/' . $tempid . '_page.html'));
    $temp  = preg_replace('/[\r\t\n]/', '', $temp);
    $temp  = preg_replace('/%(\=|@)([^%]*)%/', '%$1 $2%', $temp);
    
//demo should be removed when we take data from db
    if(!$lang)
        $lang = 'gr';
    
    $json   = file_get_contents(dirname(__FILE__) . '/../data/json/' . $lang . '-' . $page . '.json');
    $uiLang = file_get_contents(dirname(__FILE__) . '/../data/json/' . $lang . '-ui_elemets.json');

    $langs = json_decode('{
        "gr": {"id":"gr","link": {"title": "ελληνική έκδοση", "text": "γλώσσα Ελληνικά"}},
        "en" : {"id":"en","link": {"title": "switch to english", "text": "language English"}}
    }');

    $defaultLang    = 'gr';
    
    $last_mod = filemtime(dirname(__FILE__) . '/../data/json/' . $lang . '-' . $page . '.json');
    if($_SERVER['SERVER_NAME'] != 'localhost' && file_exists(dirname(__FILE__) . '/cache_pages/' . $page) && $last_mod < filemtime(dirname(__FILE__) . '/cache_pages/' . $page)) {
        echo file_get_contents(dirname(__FILE__) . '/cache_pages/' . $page);
        return;
    }
        
///////////////////////
    $jsonData = json_decode($json);
    $uiLang = json_decode($uiLang);

    $langsLinks = array();
    foreach($langs as $langel) {
        if($langel->id != $lang)
            $langsLinks[] = '<a title="' . $langel->link->title . '" href="/' . ($langel->id != $defaultLang ? $langel->id . '/' : '') . ($page != 'home' ? (strrpos($tempid, 'subpage-') !== false ? $parentpage . '/' : '' ) . $page . '.html' : '') . '" class="lang georgia italic" id="lang_' . $langel->id . '">' . $langel->link->text . '</a>';
    }

    $eachMatches = array();
    
    $temp = preg_replace_callback('/(%= ([a-zA-Z\-_=\|]+_widget)%)/', 'addWidget', $temp);

    //collect the content of the each
    $temp = preg_replace_callback('#%each\((\[[a-zA-Z\-_|]+\])\)%(.*?)%/each%#', 'eachesCollecct', $temp);
    /*
     * Call the currying function, tell it to accept one
     * 
     */
    $callback = curry('template', 2);
    $result = '<div id="' . $page . 'Page">' . preg_replace_callback('#%(\/?)(\w+|.)(?:\(((?:[^\%]|\%(?!\%))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\%]|\%(?!\%))*?)\))?\s*%#', $callback($jsonData), $temp) . '</div>';

//    if($parentpage != $page) {
//        $result = preg_replace('%href=(["\'])(\.\.\/)*(' . ($lang != $defaultLang ? $lang . '/' : '') . ')*' . $parentpage . '/%s', 'href=$1$2$3', $result);
//    }

    ob_start();
    include dirname(__FILE__) . '/index.php';
    $index = ob_get_contents();
    ob_end_clean();

    $index = preg_replace('/<meta name="description" content="(.*)" \/>/', '<meta name="description" content="' . $jsonData->description . '" />', $index);
    $index = preg_replace('/<meta name="keywords" content="(.*)" \/>/', '<meta name="keywords" content="' . $jsonData->keywords . '" />', $index);
    $index = preg_replace("/<title>(.*)<\/title>/", "<title>" . $jsonData->title . " | Paramana.com</title>", $index);
    $index = preg_replace('%<div id="contentHook"></div>%', $result, $index);
    $body  = preg_replace('%.*(<body.*\/body>).*%s', '$1', $index);
    $body  = preg_replace('%href="(?!\#)(?!mailto:)(?!ftp:\/\/)(?!http:\/\/)(?!https:\/\/)(?:\.\.\/)*(?:\/)*(?:' . $lang . '\/)*([^ <>\'"{}|\\^`[\]]*)"%', 'href="/' . ($lang != $defaultLang ? $lang . '/' : '') . '$1"', $body);
    $body  = preg_replace('%href="/(\w+\.html)*"%s', 'href="/' . ($lang != $defaultLang ? $lang . '/' : '') .'$1"', $body);
    $index = preg_replace('%(<body.*\/body>)%s', $body, $index);
    $index = preg_replace('%<div id="langHook"></div>%', '<div id="langHook">' . implode('', $langsLinks) . '</div>', $index);
    $index = str_replace('activeBtn', '', $index);

    file_put_contents(dirname(__FILE__) . '/cache_pages/' . $page, $index);

    echo $index;

    //Functions
    function template($context, $matches){
        global $eachMatches;
        $output = '';
        //var_dump($matches);
        $all    = isset($matches[0]) ? $matches[0] : NULL;
        $slash  = isset($matches[1]) ? $matches[1] : NULL;
        $type   = isset($matches[2]) ? $matches[2] : NULL;
        $fnargs = isset($matches[3]) ? $matches[3] : NULL;
        $target = isset($matches[4]) ? $matches[4] : NULL;
        $parens = isset($matches[5]) ? $matches[5] : NULL;
        $args   = isset($matches[6]) ? $matches[6] : NULL;
        
        $target = preg_replace('/^this\./', '', $target);
        if (strrpos($target, 'ui_') !== false) {
            global $uiLang;
            $context = $uiLang;
        }
        
        if(strrpos($target, '.') && !$parens)
            $target = explode('.', $target);

        switch ($type) {
            case 'each':
                $fnargs   = preg_replace('/\[(.*)\]/', '$1', $fnargs);
                $foo      = explode('|', $fnargs);
                $fnargs   = $foo[0];
                $eachData = $eachMatches[$foo[1]];
                if(isset($eachData) && isset($context->$fnargs)) {
                    foreach($context->$fnargs as $item) {
                        /*
                         * Call the currying function, tell it to accept two
                         * arguments, and only pass it one: the array containing
                         * values to replace the tags with.
                         */
                        $callback = curry('template', 2);
                        $output .= preg_replace_callback('#%(\/?)(\w+|.)(?:\(((?:[^\%]|\%(?!\%))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\%]|\%(?!\%))*?)\))?\s*%#', $callback($item), $eachData);
                    }
                }
                break;
            case 'root':
                global $jsonData;
                $fnargs = preg_replace('/\[(.*)\]/', '$1', $fnargs);
                if(isset($jsonData->$fnargs))
                    $output = $jsonData->$fnargs;
                break;
            case 'output':
            case '=':
            default:
                if((array)$target === $target) {
                    $foo = objectify($target, $context);
                    if(isset($foo))
                        $output = $foo;
                }
                else {
                    if (preg_match('/(.*)\[(\d+)\]/', $target, $m)) {
                        $foo = $context->$m[1];
                        if(isset($foo[$m[2]]))
                            $output = $foo[$m[2]];
                    }
                    else if (preg_match('/\$value/', $target)) {
                        if(isset($context))
                            $output = $context;
                    }
                    else if (preg_match('/\$index/', $target)) {
                        //@todo
                    }
                    else {
                        if(isset($context->$target))
                            $output = $context->$target;
                        else {
                            if($parens) {
                                $foo = preg_replace('/\.\w+$/', '', $target);
                                $foo = objectify(explode('.', $target), $context);
                                if(isset($foo)) {
                                    $output = $foo;
                                }
                            }
                        }
                    }
                }
                break;

        }
        return $output;
    }

    function addWidget($widgeMatch){
        $widgetName = $widgeMatch[2];
        $widgetTemp = trim(file_get_contents(dirname(__FILE__) . '/temp/' . $widgetName . '.html'));
        $widgetTemp  = preg_replace('/[\r\t\n]/', '', $widgetTemp);
        $widgetTemp  = preg_replace('/%(\=|@)([^%]*)%/', '%$1 $2%', $widgetTemp);
        return $widgetTemp;
    }

    function eachesCollecct($eachMatch){
        global $eachMatches;
        $eachMatches[] = $eachMatch[2];
        return '%each(' . $eachMatch[1] . '|' . (count($eachMatches) - 1) . ')%';
    }

    function curry($func, $arity) {
        /*
         * Creates an anonymous function to accept incomplete
         * argument lists and allow the rest of the arguments
         * to be supplied later
         */
        return create_function('', "
            // Get an array of the arguments passed to \$func
            \$args = func_get_args();

            /*
             * If the number of arguments supplied to the passed
             * function is greater than or equal to the number of
             * arguments defined in \$arity, call the function and
             * return its output
             */
            if(count(\$args) >= $arity)
            {
                return call_user_func_array('$func', \$args);
            }

            /*
             * Otherwise, save the function paramters in a variable
             * and return a function that will accept more parameters
             * while preserving the original passed arguments
             */
            \$args = 'unserialize('.var_export(serialize(\$args), true).')';

            return create_function('','
                \$a = func_get_args();
                \$z = ' . \$args . ';
                \$a = array_merge(\$z,\$a);
                return call_user_func_array(\'$func\', \$a);
            ');
        ");
    }

    //converts the javascript of accessing objects to php ex: test.test to test->test
    function objectify($targetObj, $context){
        $foo = $context->$targetObj[0];
        for($i = 1; $i < count($targetObj); $i++){
            if(isset($foo->$targetObj[$i]))
                $foo = $foo->$targetObj[$i];
        }
        return $foo;
    }
    //echo $index;
?>