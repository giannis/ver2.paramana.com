<?php
if(!isset($_POST["section"])) {
    header('Content-Type: text/plain; charset=utf-8');
    throw new Exception("command set");
    exit;
}

$section = strtolower($_POST["section"]);

if ($section === "contactform") {
    include(dirname(__FILE__) . "/../lib/contactForm.php");
}
?>
