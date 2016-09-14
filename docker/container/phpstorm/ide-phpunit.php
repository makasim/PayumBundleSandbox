<?php

class IDE_PHPUnit_Loader
{
    public static $supportedVersions = array(
        '4.1.0' => '41',
        '4.0.0' => '40',
        '3.8.0' => '38',
        '3.7.0' => '37',
        '3.6.0' => '36',
        '3.5.0' => '35',
        '3.4.0' => '34',
        'dev' => 'dev');

    const SUCCESS_EXIT = 0;
    const FAILURE_EXIT = 1;
    const EXCEPTION_EXIT = 2;

    public static $PHPUnitVersionId = null;

    /**
     * Will try to find file by $relativePath in include_path from ini file.
     * If $currentDir is null will check all include_path otherwise only directories with the specified name
     *
     * @param $relativePath
     * @param $currentDir
     * @return null|string
     */
    private static function findFileInIncludePath($relativePath, $currentDir = null)
    {
        $pathArray = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($pathArray as $path)
        {
            if (is_null($currentDir) || IDE_PHPUnit_Loader::endsWith($path, $currentDir)) {
                $filename = $path . DIRECTORY_SEPARATOR . $relativePath;
                if (file_exists($filename)) {
                    return $filename;
                }
            }
        }
        return null;
    }

    private static function endsWith($str, $tail) {
        $length = strlen($str);
        $tailLength = strlen($tail);
        if ($length >= $tailLength) {
            return substr_compare(strtolower($str), $tail, -$tailLength) === 0;
        }
        return false;
    }

    /**
     * Detect current phpunit version and convert it into version id
     *
     * @return boolean
     */
    private  static function detectPHPUnitVersionId()
    {
        if (!class_exists('PHPUnit_Runner_Version', true)) {
            $autoload = self::findFileInIncludePath('PHPUnit/Autoload.php');
            if (!is_null($autoload)) {
                require_once $autoload;
            }


            $version = self::findFileInIncludePath('PHPUnit/Runner/Version.php');
            if (is_null($version)) {
                return false;
            }
            require_once $version;
        }
        $PHPUnitVersion = PHPUnit_Runner_Version::id();

        if ($PHPUnitVersion === "@package_version@") {
            self::$PHPUnitVersionId = "37";
        }
        else {
            foreach (IDE_PHPUnit_Loader::$supportedVersions as $fullVersion => $shortVersion) {
                if (version_compare($PHPUnitVersion, $fullVersion) >= 0) {
                    self::$PHPUnitVersionId = $shortVersion;
                    break;
                }
            }

            if (self::$PHPUnitVersionId == null) {
                echo "Unsupported PHPUnit version:  $PHPUnitVersion";
                exit(IDE_PHPUnit_Loader::FAILURE_EXIT);
            }
        }
        return true;
    }

    /**
     * @return void
     */
    private static function load()
    {
        //require_once 'PHPUnit/Autoload.php';
    }

    /**
     * @return void
     */
    private static function load36()
    {
        define('PHPUnit_MAIN_METHOD', 'IDE_PHPUnit_TextUI_Command::main');
        //require 'PHPUnit/Autoload.php';
    }

    /**
     * @return void
     */
    private static function load35()
    {
        require_once 'PHP/CodeCoverage/Filter.php';
        PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'PHPUNIT');

        //require_once 'PHPUnit/Autoload.php';

        define('PHPUnit_MAIN_METHOD', 'IDE_PHPUnit_TextUI_Command::main');
    }

    /**
     * @return void
     */
    private static function load34()
    {
        if (extension_loaded('xdebug')) {
            xdebug_disable();
        }

        require_once 'PHPUnit/Util/Filter.php';

        PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

        require 'PHPUnit/TextUI/Command.php';

        define('PHPUnit_MAIN_METHOD', 'IDE_PHPUnit_TextUI_Command::main');
    }

    /**
     * @return void
     */
    private  static function loadFromIncludePath()
    {
        switch (self::$PHPUnitVersionId) {
            case "34": {
                self::load34();
                break;
            }
            case "35": {
                self::load35();
                break;
            }
            case "36": {
                self::load36();
                break;
            }
            default: {
                self::load();
                break;
            }
        }
    }

    /**
     * @return void
     */
    public static function init()
    {
        if (isset($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER'])) {
            self::loadByAutoloader($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER']);
        } else if (isset($_SERVER['IDE_PHPUNIT_PHPUNIT_PHAR'])) {
            $path = $_SERVER['IDE_PHPUNIT_PHPUNIT_PHAR'];
            if (!file_exists($path)) {
                echo "The value \$_SERVER['IDE_PHPUNIT_PHPUNIT_PHAR'] is specified, but file doesn't exist '$path'\n";
                exit(IDE_PHPUnit_Loader::FAILURE_EXIT);
            }
            $path = realpath($path);
            $phar = new Phar($path);
            $alias = $phar->getAlias();
            Phar::loadPhar($path, $alias);
            //awful hack start (but I don't know a better way to do it)
            $stub = $phar->getStub();
            $i = strpos($stub, "<?php\n");
            $stub = substr($stub, $i + 6);
            $i = strpos($stub, "Phar::mapPhar");
            $stub_prefix = substr($stub, 0, $i);
            if (!isset($_SERVER['IDE_PHPUNIT_VERSION']) ||  $_SERVER['IDE_PHPUNIT_VERSION'] < "4.6") {
                eval($stub_prefix);
            }
            else {
                $mapEnd = strpos($stub, ")", $i);
                $stub_suffix = substr($stub, $mapEnd + 2);
                eval($stub_prefix . $stub_suffix);
            }
            //awful hack end
        } else {
            $include = ini_get('include_path');
            if (isset($_SERVER['IDE_PHPUNIT_PHPUNIT_INCLUDE'])) {
                $old_include = explode(PATH_SEPARATOR, $include);
                $new_include = explode(PATH_SEPARATOR, $_SERVER['IDE_PHPUNIT_PHPUNIT_INCLUDE']);
                $include = implode(PATH_SEPARATOR, array_unique(array_merge($old_include, $new_include)));
                ini_set('include_path', $include);
            }

            $succeed = IDE_PHPUnit_Loader::tryToLoadByClasses();
            if (!$succeed) {
                // load phpunit 4.* by composer autoloader (since pear is not supported for phpunit 4.*)
                // for /path_to/project/vendor/phpunit
                $composerAutoloader = self::findFileInIncludePath('../../vendor/autoload.php', '/phpunit');
                if (is_null($composerAutoloader)) {
                    // for /path_to/project/vendor/phpunit/phpunit
                    $composerAutoloader = self::findFileInIncludePath('../../../vendor/autoload.php', '/phpunit');
                }

                if (is_null($composerAutoloader)) {
                    echo "Cannot find PHPUnit in include path (" . $include . ")";
                    exit(IDE_PHPUnit_Loader::FAILURE_EXIT);
                }
                IDE_PHPUnit_Loader::loadByAutoloader($composerAutoloader);
            }
        }
        IDE_PHPUnit_Loader::detectPHPUnitVersionId();
    }

    private static function tryToLoadByClasses() {
        if (!is_null(self::findFileInIncludePath('PHPUnit/'))) {
            // load old phpunit versions directly by classes (for pear installation)
            $succeed = IDE_PHPUnit_Loader::detectPHPUnitVersionId();
            if ($succeed) {
                IDE_PHPUnit_Loader::loadFromIncludePath();
                return true;
            }
      }
      return false;
    }

    public static function loadByAutoloader($path)
    {
        if (!file_exists($path)) {
            echo "The value of autoloader is specified, but file doesn't exist '$path'\n";
            exit(IDE_PHPUnit_Loader::FAILURE_EXIT);
        }
        require_once $path;
    }
}

IDE_PHPUnit_Loader::init();

//load custom implementation of the PHPUnit_TextUI_ResultPrinter
class IDE_Base_PHPUnit_TextUI_ResultPrinter extends PHPUnit_TextUI_ResultPrinter
{
    /**
     * @param PHPUnit_Util_Printer $printer
     */
    function __construct($printer, $out)
    {
        parent::__construct($out);
        if (!is_null($printer) && $printer instanceof PHPUnit_TextUI_ResultPrinter) {
            $this->out = $printer->out;
            $this->outTarget = $printer->outTarget;
        }
    }

    protected function writeProgress($progress)
    {
        //ignore
    }
}

if (IDE_PHPUnit_Loader::$PHPUnitVersionId == "34") {
    class IDE_PHPUnit_TextUI_ResultPrinter extends IDE_Base_PHPUnit_TextUI_ResultPrinter
    {
        public function printResult(PHPUnit_Framework_TestResult $result)
        {
            $this->printHeader($result->time());
            $this->printFooter($result);
        }
    }
} else {
    class IDE_PHPUnit_TextUI_ResultPrinter extends IDE_Base_PHPUnit_TextUI_ResultPrinter
    {
        public function printResult(PHPUnit_Framework_TestResult $result)
        {
            $this->printHeader();
            $this->printFooter($result);
        }
    }
}

//loading of IDE_PHPUnit_TextUI_Command
class IDE_Base_PHPUnit_TextUI_Command extends PHPUnit_TextUI_Command
{
    public static function main($exit = TRUE)
    {
        $command = new IDE_PHPUnit_TextUI_Command();
        $command->run($_SERVER['argv'], $exit);
    }

    protected function handleArguments(array $argv)
    {
        parent::handleArguments($argv);
        if (isset($this->arguments['printer'])) {
            $printer = $this->arguments['printer'];
        } else {
            $printer = null;
        }

        $out = isset($this->arguments['stderr']) ? 'php://stderr' : null;
        $printer = new IDE_PHPUnit_TextUI_ResultPrinter($printer, $out);
        $this->arguments['printer'] = $printer;
        $this->arguments['listeners'][] = new IDE_PHPUnit_Framework_TestListener($printer);
    }
}

if (IDE_PHPUnit_Loader::$PHPUnitVersionId == "34" || IDE_PHPUnit_Loader::$PHPUnitVersionId == "35") {
    class IDE_PHPUnit_TextUI_Command extends IDE_Base_PHPUnit_TextUI_Command
    {
    }
} else {
    class IDE_PHPUnit_TextUI_Command extends IDE_Base_PHPUnit_TextUI_Command
    {
        protected function createRunner()
        {
            $coverage_Filter = new PHP_CodeCoverage_Filter();
            if (method_exists($coverage_Filter, "addFileToBlacklist")) {
                $coverage_Filter->addFileToBlacklist(__FILE__);
            }
            else if (method_exists($coverage_Filter, "removeFileFromWhitelist")) {
                $coverage_Filter->removeFileFromWhitelist(__FILE__);
            }
            $runner = new PHPUnit_TextUI_TestRunner($this->arguments['loader'], $coverage_Filter);
            return $runner;
        }
    }
}

class IDE_PHPUnit_Framework_TestListener implements PHPUnit_Framework_TestListener
{
    private $previousTestName;
    private $isSummaryTestCountPrinted = false;

    /** @var PHPUnit_Util_Printer $printer*/
    private $printer = false;

    /**
     * @param PHPUnit_Util_Printer $printer
     */
    function __construct($printer)
    {
        $this->printer = $printer;
    }

    private function printEvent($eventName, $params = array())
    {
        $this->printer->write("\n##teamcity[$eventName");
        foreach ($params as $key => $value) {
            $escapedValue = self::escapeValue($value);
            $this->printer->write(" $key='$escapedValue'");
        }
        $this->printer->write("]\n");
    }

    private static function escapeValue($text) {
        $text = str_replace("|", "||", $text);
        $text = str_replace("'", "|'", $text);
        $text = str_replace("\n", "|n", $text);
        $text = str_replace("\r", "|r", $text);
        $text = str_replace("]", "|]", $text);
        $text = str_replace("[", "|[", $text);
        return $text;
    }

    private static function getMessage(Exception $e)
    {
        $message = "";
        if (!($e instanceof PHPUnit_Framework_Exception)) {
            if (strlen(get_class($e)) != 0) {
                $message = $message . get_class($e);
            }
            if (strlen($message) != 0 && strlen($e->getMessage()) != 0) {
                $message = $message . " : ";
            }
        }
        return $message . $e->getMessage();
    }

    private static function getDetails(Exception $e)
    {
        if (IDE_PHPUnit_Loader::$PHPUnitVersionId == "34" ||
            IDE_PHPUnit_Loader::$PHPUnitVersionId == "35" ||
            IDE_PHPUnit_Loader::$PHPUnitVersionId == "36") {
            return $e->getTraceAsString();
        }
        else {
            $stackTrace = PHPUnit_Util_Filter::getFilteredStacktrace($e);

            $previous = $e->getPrevious();
            while ($previous) {
                $stackTrace .= "\nCaused by\n" .
                    PHPUnit_Framework_TestFailure::exceptionToString($previous) . "\n" .
                    PHPUnit_Util_Filter::getFilteredStacktrace($previous);
                $previous = $previous->getPrevious();
            }
            return " " . str_replace("\n", "\n ", $stackTrace);
        }
    }

    private static function getPrimitiveValueAsString($value)
    {
        if (is_null($value)) {
            return "null";
        }
        else if (is_bool($value)) {
            return $value == true ? "true" : "false";
        }
        else if (is_scalar($value)){
            return print_r($value, true);
        }
        return null;
    }

    private static function getFileName($className)
    {
        $reflectionClass = new ReflectionClass($className);
        $fileName = $reflectionClass->getFileName();
        return $fileName;
    }

    public function addWarning(PHPUnit_Framework_Test $test, PHPUnit_Framework_Warning $e, $time)
    {
        $this->addError($test, $e, $time);
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->printEvent("testFailed", array(
            "name" => $test->getName(),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->addError($test, $e, $time);
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $params = array(
            "name" => $test->getName(),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        );
        if ($e instanceof PHPUnit_Framework_ExpectationFailedException) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure ||
                $comparisonFailure instanceof \SebastianBergmann\Comparator\ComparisonFailure) {
                $expectedString = null;
                if (method_exists($comparisonFailure, "getExpectedAsString")) {
                    $expectedString = $comparisonFailure->getExpectedAsString();
                }
                if (is_null($expectedString) || empty($expectedString)) {
                    $expectedString = self::getPrimitiveValueAsString($comparisonFailure->getExpected());
                }
                $actualString = null;
                if (method_exists($comparisonFailure, "getActualAsString")) {
                    $actualString = $comparisonFailure->getActualAsString();
                }
                if (is_null($actualString) || empty($actualString)) {
                    $actualString = self::getPrimitiveValueAsString($comparisonFailure->getActual());
                }
                if (!is_null($actualString) && !is_null($expectedString)) {
                    $params['actual'] = $actualString;
                    $params['expected'] = $expectedString;
                }
            }
        }
        $this->printEvent("testFailed", $params);
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->printIgnoredTest($test->getName(), $e);
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $testName = $test->getName();
        if ($this->previousTestName != $testName) {
            // wrap tests failed with dependency into "testStarted"/"testFinished"
            $this->startTest($test);
            $this->printIgnoredTest($testName, $e);
            $this->endTest($test, $time);
        }
        else {
            $this->printIgnoredTest($testName, $e);
        }
    }

    public function printIgnoredTest($testName, Exception $e) {
        $this->printEvent("testIgnored", array(
            "name" => $testName,
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
        $testName = $test->getName();
        $this->previousTestName = $testName;
        $params = array(
            "name" => $testName
        );
        if ($test instanceof PHPUnit_Framework_TestCase) {
            $className = get_class($test);
            $fileName = self::getFileName($className);
            $params['locationHint'] = "php_qn://$fileName::\\$className::$testName";
        }
        $this->printEvent("testStarted", $params);
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $this->printEvent("testFinished", array(
            "name" => $test->getName(),
            "duration" => (int)(round($time, 2) * 1000)
        ));
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if (!$this->isSummaryTestCountPrinted) {
            $this->isSummaryTestCountPrinted = true;
            //print tests count
            $this->printEvent("testCount", array(
                "count" => count($suite)
            ));
        }

        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }
        $params = array(
            "name" => $suiteName,
        );
        if (class_exists($suiteName, false)) {
            $fileName = self::getFileName($suiteName);
            $params['locationHint'] = "php_qn://$fileName::\\$suiteName";
        }
        else {
            $split = preg_split("/::/", $suiteName);
            if (sizeof($split) == 2 && method_exists($split[0], $split[1])) {
                $fileName = self::getFileName($split[0]);
                $params['locationHint'] = "php_qn://$fileName::\\$suiteName";
                $params['name'] = $split[1];
            }
        }
        $this->printEvent("testSuiteStarted", $params);
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }

        $params = array(
            "name" => $suiteName,
        );

        if (!class_exists($suiteName, false)) {
            $split = preg_split("/::/", $suiteName);
            if (sizeof($split) == 2 && method_exists($split[0], $split[1])) {
                $params['name'] = $split[1];
            }
        }
        $this->printEvent("testSuiteFinished", $params);
    }

}

IDE_PHPUnit_TextUI_Command::main();
