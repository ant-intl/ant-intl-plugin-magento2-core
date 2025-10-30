<?php
declare(strict_types=1);

// 设置错误报告级别，减少不必要的警告
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);

// 1. 引入 Composer 自动加载
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];

$autoloaderFound = false;
foreach ($autoloadPaths as $file) {
    if (file_exists($file)) {
        require_once $file;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    throw new RuntimeException('Composer autoloader not found. Run "composer install" first.');
}

// 2. 设置 Magento generated 目录
$generatedPaths = [
    __DIR__ . '/../generated/code',
    __DIR__ . '/../../generated/code'
];

foreach ($generatedPaths as $generatedPath) {
    if (is_dir($generatedPath)) {
        set_include_path(get_include_path() . PATH_SEPARATOR . $generatedPath);
        break;
    }
}

// 3. 定义 Mock 类和接口创建函数
function createMockClass(string $fullClassName, array $methods = [], bool $isInterface = false): void
{
    if (class_exists($fullClassName) || interface_exists($fullClassName)) {
        return;
    }

    $lastSlash = strrpos($fullClassName, '\\');
    $namespace = substr($fullClassName, 0, $lastSlash);
    $className = substr($fullClassName, $lastSlash + 1);

    $methodsCode = '';
    foreach ($methods as $method => $returnValue) {
        if (is_string($returnValue)) {
            $returnCode = "'$returnValue'";
        } elseif (is_null($returnValue)) {
            $returnCode = 'null';
        } else {
            $returnCode = var_export($returnValue, true);
        }

        if ($isInterface) {
            $methodsCode .= "    public function $method();\n";
        } else {
            $methodsCode .= "    public function $method() { return $returnCode; }\n";
        }
    }

    $type = $isInterface ? 'interface' : 'class';
    $extraMethods = '';

    if (!$isInterface) {
        $extraMethods = "
    public function __call(\$name, \$arguments) {
        return \$this;
    }
    public static function __callStatic(\$name, \$arguments) {
        return new static();
    }";
    }

    $classCode = "
namespace $namespace;
$type $className {
$methodsCode$extraMethods
}";

    try {
        eval($classCode);
    } catch (ParseError $e) {
        // 忽略解析错误，可能类已经存在
    } catch (Error $e) {
        // 忽略其他错误，如重复声明
    }
}

// 4. 创建必要的接口（简化版本，避免参数冲突）
$mockInterfaces = [
    'Magento\Framework\App\Config\ScopeConfigInterface' => [
        'getValue' => null,
        'isSetFlag' => false
    ],
    'Magento\Payment\Gateway\Validator\ResultInterfaceFactory' => [
        'create' => null
    ]
];

foreach ($mockInterfaces as $interfaceName => $methods) {
    createMockClass($interfaceName, $methods, true);
}

// 5. 创建必要的 Mock 类
$mockClasses = [
    'Magento\Sales\Model\Order' => [
        'loadByIncrementId' => null,
        'getId' => null,
        'getPayment' => null,
        'setId' => null,
        'setPayment' => null,
        'getIncrementId' => null,
        'getState' => null,
        'setState' => null,
        'getStatus' => null,
        'setStatus' => null,
        'save' => null
    ],
    'Magento\Sales\Model\OrderFactory' => [
        'create' => null
    ],
    'Magento\Sales\Model\Order\Payment' => [
        'getAdditionalInformation' => null,
        'setAdditionalInformation' => null,
        'getMethod' => 'mock_method',
        'getMethodInstance' => null,
        'setMethod' => null,
        'getOrder' => null,
        'setOrder' => null
    ],
    'Magento\Quote\Model\QuoteFactory' => [
        'create' => null
    ],
    'Magento\Quote\Model\Quote' => [
        'getId' => null,
        'getReservedOrderId' => null,
        'setReservedOrderId' => null,
        'collectTotals' => null,
        'save' => null
    ],
    'Magento\Framework\DB\TransactionFactory' => [
        'create' => null
    ],
    'Magento\Framework\DB\Transaction' => [
        'addObject' => null,
        'save' => null
    ],
    'Magento\Framework\Controller\ResultFactory' => [
        'create' => null
    ],
    'Magento\Framework\Controller\Result\Json' => [
        'setData' => null,
        'setHttpResponseCode' => null
    ],
    'Magento\Framework\Controller\Result\Redirect' => [
        'setUrl' => null,
        'setPath' => null
    ]
];

foreach ($mockClasses as $className => $methods) {
    createMockClass($className, $methods, false);
}

// 6. 手动创建具体的实现类（用正确的方法签名）
if (interface_exists('Magento\Framework\App\Config\ScopeConfigInterface')) {
    if (!class_exists('MockScopeConfig')) {
        eval('
        class MockScopeConfig implements Magento\Framework\App\Config\ScopeConfigInterface {
            public function getValue($path, $scopeType = null, $scopeCode = null) { return null; }
            public function isSetFlag($path, $scopeType = null, $scopeCode = null) { return false; }
        }
        ');
    }
}

// 7. 为 StoreManagerInterface 创建正确的 Mock 实现
if (!interface_exists('Magento\Store\Model\StoreManagerInterface') && !class_exists('Magento\Store\Model\StoreManagerInterface')) {
    eval('
    namespace Magento\Store\Model;
    interface StoreManagerInterface {
        public function getStore($storeId = null);
        public function getStores($withDefault = false, $codeKey = false);
        public function getWebsite($websiteId = null);
        public function getWebsites($withDefault = false, $codeKey = false);
        public function getDefaultStoreView();
        public function getGroup($groupId = null);
        public function getGroups($withDefault = false);
        public function hasSingleStore();
        public function isSingleStoreMode();
        public function setCurrentStore($store);
        public function setIsSingleStoreModeAllowed($value);
        public function reinitStores();
    }
    ');
}

if (!class_exists('MockStoreManager')) {
    eval('
    class MockStoreManager implements Magento\Store\Model\StoreManagerInterface {
        public function getStore($storeId = null) { return null; }
        public function getStores($withDefault = false, $codeKey = false) { return []; }
        public function getWebsite($websiteId = null) { return null; }
        public function getWebsites($withDefault = false, $codeKey = false) { return []; }
        public function getDefaultStoreView() { return null; }
        public function getGroup($groupId = null) { return null; }
        public function getGroups($withDefault = false) { return []; }
        public function hasSingleStore() { return false; }
        public function isSingleStoreMode() { return false; }
        public function setCurrentStore($store) { return $this; }
        public function setIsSingleStoreModeAllowed($value) { return $this; }
        public function reinitStores() { return $this; }
    }
    ');
}

// 8. 创建其他必要的 Mock 类
$additionalMockClasses = [
    'Magento\Store\Model\Store',
    'Magento\Store\Model\Website',
    'Magento\Store\Model\Group',
    'Magento\Framework\App\Request\Http',
    'Magento\Framework\App\Response\Http',
    'Magento\Framework\Registry',
    'Magento\Framework\Event\Manager',
    'Magento\Framework\App\State'
];

foreach ($additionalMockClasses as $className) {
    if (!class_exists($className)) {
        $lastSlash = strrpos($className, '\\');
        $namespace = substr($className, 0, $lastSlash);
        $classname = substr($className, $lastSlash + 1);

        eval("
        namespace $namespace;
        class $classname {
            public function __call(\$name, \$arguments) { return \$this; }
            public static function __callStatic(\$name, \$arguments) { return new static(); }
        }
        ");
    }
}

// 9. 设置全局配置
date_default_timezone_set('UTC');

// 10. 定义测试常量
if (!defined('BP')) {
    define('BP', __DIR__ . '/..');
}

if (!defined('TESTS_TEMP_DIR')) {
    define('TESTS_TEMP_DIR', __DIR__ . '/../build/tmp');
}

// 11. 创建必要的目录
$tempDir = TESTS_TEMP_DIR;
if (!is_dir($tempDir)) {
    @mkdir($tempDir, 0755, true);
}

// 恢复正常的错误报告
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

echo "✅ Bootstrap completed successfully\n";