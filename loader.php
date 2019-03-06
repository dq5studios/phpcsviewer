<?php

// get file list
// crawl file list
// include file
// get_declared_classes
// ReflectionClass
// getProperties(ReflectionProperty::IS_PUBLIC)
// build property list
// ref = namespace + class - PHP_CodeSniffer\Standards
//

spl_autoload_register(
    function ($class) {
        $class = str_replace("PHP_CodeSniffer", ".", $class);
        $class .= ".php";
        $class = str_replace("\\", "/", $class);
        include_once $class;
    }
);


$class_list = get_declared_classes();
$sniff_list = glob("Standards/*/Sniffs/*/*.php");
$doc_list = glob("Standards/*/Docs/*/*.xml");

foreach ($sniff_list as $filename) {
    include_once $filename;
    $class_check = get_declared_classes();
    $sniff_class = array_diff($class_check, $class_list);
    $sniff_class = array_pop($sniff_class);

    $reflect = new ReflectionClass($sniff_class);
    $param_list = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

    echo $sniff_class, PHP_EOL;
    // var_dump($param_list);
    // exit;
}
