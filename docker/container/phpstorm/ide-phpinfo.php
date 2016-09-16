<?php

error_reporting(0);
ini_set("xdebug.halt_level", "0");
ini_set("xdebug.force_error_reporting", "0");
ini_set("xdebug.force_display_errors", "0");

const HHVM_PHP_INI = "/etc/hhvm/php.ini";
const HHVM_SERVER_INI = "/etc/hhvm/server.ini";

function createXmlHeader()
{
    return "<?xml version=\"1.0\"?>";
}

function createXmlElement($tagName, $attributes, $content = null)
{
    $result = "";
    $result .= "<{$tagName}";
    foreach ($attributes as $attributeName => $attributeValue) {
        $result .= " {$attributeName}=\"$attributeValue\"";
    }
    if (!empty($content)) {
        $result .= ">";
        $result .= $content;
        $result .= "</{$tagName}>";
    } else {
        $result .= "/>";
    }
    return $result;
}

function getPathToPhpExecutable() {
    if (defined('PHP_BINARY')) {
        return PHP_BINARY;
    }
    else {
        // for PHP 5.3
        $phpExe = PHP_BINDIR . DIRECTORY_SEPARATOR . "php";
        if (!isset($phpExe) || !file_exists($phpExe)) {
            $phpExe .= ".exe";
        }
        return $phpExe;
    }
}

function getPathToPhpDirectory() {
    if (defined('PHP_BINARY')) {
        return dirname(PHP_BINARY) . DIRECTORY_SEPARATOR;
    }
    else {
        // for PHP 5.3
        return PHP_BINDIR . DIRECTORY_SEPARATOR;
    }
}

function detectSAPI($name) {
    $phpDir = getPathToPhpDirectory();
    $phpsapi = $phpDir . $name;
    if (!isset($phpsapi) || !file_exists($phpsapi)) {
        $phpsapi .= ".exe";
    }
    return $phpsapi;
}

function getPhpInfoHash()
{
    $element = array();
    $element['path_separator'] = PATH_SEPARATOR;
    $element['version'] = phpversion();
    $element['extensions'] = get_loaded_extensions();
    $element['configuration_options'] = ini_get_all();
    if (array_key_exists("SSH_CLIENT", $_SERVER)) {
        $element['ssh'] = $_SERVER["SSH_CLIENT"];
    }
    else {
        $element['ssh'] = null;
    }
    return $element;
}

function hhvmVersion() {
    if (defined('HHVM_VERSION')) {
        return HHVM_VERSION;
    }
    return null;
}


$hhvm = hhvmVersion();
$warning = error_get_last();
$hash = getPhpInfoHash();
$result = '';
$result .= createXmlHeader();

$file = php_ini_loaded_file();
if ((is_null($file) || !$file) && !is_null($hhvm) && file_exists(HHVM_PHP_INI)) {
    $file = HHVM_PHP_INI;
}

$parsedFiles = createXmlElement(
    "path_to_ini",
    array(
        "path" => htmlspecialchars($file)
    ));

$scannedFiles = php_ini_scanned_files();
if ((is_null($scannedFiles) || !$scannedFiles) && !is_null($hhvm) && file_exists(HHVM_SERVER_INI)) {
    $scannedFiles = HHVM_SERVER_INI;
}

if (!is_null($scannedFiles)) {
    $prepared = "";
    $allScannedFiles = explode(',', $scannedFiles);
    $count = count($allScannedFiles);
    if ($count > 0) {
        $prepared .= trim($allScannedFiles[0]);
        for ($i = 1; $i < $count; $i++) {
            $prepared .= ", ";
            $prepared .= trim($allScannedFiles[$i]);
        }
        $parsedFiles .= createXmlElement("additional_php_ini",
            array(
                "files" => htmlspecialchars($prepared)
            )
        );
    }
}

$extensions = "";
$phpdbgExtension = "";
foreach ($hash['extensions'] as $extensionName) {
    if (strcasecmp($extensionName, "xdebug") == 0 ||
        strcasecmp($extensionName, "Zend Debugger") == 0) {
        $debugExtension = $extensionName;
    }
    else if (strcasecmp($extensionName, "phpdbg_webhelper") == 0) {
        $phpdbgExtension = $extensionName;
    }

    $extensions .= createXmlElement(
        "extension",
        array(
            "name" => htmlspecialchars($extensionName)
        ));
}
$configurationOptions = "";
foreach ($hash['configuration_options'] as $configurationOptionName => $configurationOptionValue) {
    $configurationOptions .= createXmlElement(
        "configuration_option",
        array(
            "name" => htmlspecialchars($configurationOptionName),
            "local_value" => htmlspecialchars($configurationOptionValue['local_value']),
            "global_value" => htmlspecialchars($configurationOptionValue['global_value'])
        )
    );

}

$serverVariable = "";
if (isset($hash['ssh'])) {
    $serverVariable .= createXmlElement(
        "ssh",
        array(
            "host" => htmlspecialchars($hash['ssh'])
        )
    );
}
$content = $parsedFiles . $extensions . $configurationOptions . $serverVariable;

if (isset($debugExtension)) {
    $debugVersion = phpversion($debugExtension);
    $debugger = createXmlElement(
        "debugger",
        array(
            "name" => htmlspecialchars($debugExtension),
            "version" => htmlspecialchars($debugVersion),
        ));
    $content .= $debugger;
}

$phpcli = getPathToPhpExecutable();
if (isset($phpcli) && file_exists($phpcli)) {
    $phpcliElement = createXmlElement(
        "php-cli",
        array(
            "path" => htmlspecialchars($phpcli),
        ));
    $content .= $phpcliElement;
}

$phpcgi = detectSAPI("php-cgi");
if (isset($phpcgi) && file_exists($phpcgi)) {
    $phpcgiElement = createXmlElement(
        "php-cgi",
        array(
            "path" => htmlspecialchars($phpcgi),
        ));
    $content .= $phpcgiElement;
}

if (isset($warning) && is_array($warning) &&
    strcasecmp($warning["message"], "Xdebug MUST be loaded as a Zend extension") == 0) {
    $content .= createXmlElement(
        "warning",
        array(
            "message" => "Xdebug must be loaded by 'zend_extension' instead of 'extension'"
        ));
}

if (!is_null($hhvm)) {
    $content .= createXmlElement(
        "hhvm",
        array(
            "version" => htmlspecialchars($hhvm)
        ));
}

$result .= createXmlElement(
    "php",
    array(
        "version" => htmlspecialchars($hash['version']),
        "path_separator" => htmlspecialchars($hash['path_separator'])
    ), $content);

echo $result;

