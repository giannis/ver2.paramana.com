<?php
$locPath = dirname(__FILE__) . '/../../public/';

$css    = $locPath . "css/style.css";
$cssMin = $locPath . "css/style-min.css";

if(!file_exists($cssMin) || (filemtime($css) > filemtime($cssMin))) {
//    include_once(dirname(__FILE__) . '/cssmin.php');
//    $css = file_get_contents($css);
//    $css = CssMin::minify($css);
//    file_put_contents($cssMin, $css);
//
    $yuiC = new YUICompressor(dirname(__FILE__) . '/yuicompressor-2.4.2.jar', dirname(__FILE__) . '/tmp', '"' . $cssMin . '"', array('type'=>'css'));
    $yuiC->addFile($css);
    $yuiC->compress();
}

$jsbMin = $locPath . "js/jsb-min.js";

$minifyFiles    = array();
$doWeNeedMinify = false;
$jsFiles        = preg_split('/(?:\r\n?|\n)/', file_get_contents(dirname(__FILE__) . '/jsTemplate.txt'));
foreach($jsFiles as $jsFile) {
    $jsFile = $locPath . $jsFile;
    $minifyFiles[] = $jsFile;
    if(!file_exists($jsbMin) || (filemtime($jsFile) > filemtime($jsbMin)))
        $doWeNeedMinify = true;
}

if($doWeNeedMinify) {
    $yuiC = new YUICompressor(dirname(__FILE__) . '/yuicompressor-2.4.2.jar', dirname(__FILE__) . '/tmp', '"' . $jsbMin . '"');
    foreach($minifyFiles as $jsFile) {
        $yuiC->addFile($jsFile);
    }
    $yuiC->compress();
}

class YUICompressor {

    // absolute path to YUI jar file.
    private $JAR_PATH;
    private $TEMP_FILES_DIR;
    private $options = array('type' => 'js',
        'linebreak' => false,
        'verbose' => false,
        'nomunge' => false,
        'semi' => false,
        'nooptimize' => false);
    private $files = array();
    private $string = '';

    // construct with a path to the YUI jar and a path to a place to put temporary files
    function __construct($JAR_PATH, $TEMP_FILES_DIR, $OUTPUT_FILE, $options = array()) {
        $this->JAR_PATH = $JAR_PATH;
        $this->TEMP_FILES_DIR = $TEMP_FILES_DIR;
        $this->OUTPUT_FILE    = $OUTPUT_FILE;

        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }
    }

    // set one of the YUI compressor options
    function setOption($option, $value) {
        $this->options[$option] = $value;
    }

    // add a file (absolute path) to be compressed
    function addFile($file) {
        array_push($this->files, $file);
    }

    // add a strong to be compressed
    function addString($string) {
        $this->string .= ' ' . $string;
    }

    // the meat and potatoes, executes the compression command in shell
    function compress() {

        // read the input
        foreach ($this->files as $file) {
            $this->string .= file_get_contents($file) . "\n" or die("Cannot read from uploaded file");
        }

        // create single file from all input
        $input_hash = sha1($this->string);
        $file = $this->TEMP_FILES_DIR . '/' . $input_hash . '.txt';
        $fh = fopen($file, 'w') or die("Can't create new file");
        fwrite($fh, $this->string);

        // start with basic command
        $cmd = "java -Xmx32m -jar " . $this->JAR_PATH . ' ' . $file . " -o " . $this->OUTPUT_FILE . " --charset UTF-8";

        // set the file type
        $cmd .= " --type " . (strtolower($this->options['type']) == "css" ? "css" : "js");

        // and add options as needed
        if ($this->options['linebreak'] && intval($this->options['linebreak']) > 0) {
            $cmd .= ' --line-break ' . intval($this->options['linebreak']);
        }

        if ($this->options['verbose']) {
            $cmd .= " -v";
        }

        if ($this->options['nomunge']) {
            $cmd .= ' --nomunge';
        }

        if ($this->options['semi']) {
            $cmd .= ' --preserve-semi';
        }

        if ($this->options['nooptimize']) {
            $cmd .= ' --disable-optimizations';
        }

        // execute the command
        exec($cmd . ' 2>&1', $raw_output);

        if(!empty($raw_output))
            var_dump($raw_output);

        // add line breaks to show errors in an intelligible manner
        $flattened_output = implode("\n", $raw_output);

        // clean up (remove temp file)
        unlink($file);

        // return compressed output
        return $flattened_output;
    }
}
?>