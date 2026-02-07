<?php
/**
 * Library Autoloader
 * Simple autoloader for TCPDF and PhpSpreadsheet
 */

// TCPDF Autoloader
require_once __DIR__ . '/tcpdf/tcpdf.php';

// ZipStream Autoloader
require_once __DIR__ . '/zipstream/ZipStream.php';

// PhpSpreadsheet Autoloader
require_once __DIR__ . '/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';

// Set up basic autoloading for PhpSpreadsheet and ZipStream classes
spl_autoload_register(function ($class) {
    // Handle ZipStream classes
    if (strpos($class, 'ZipStream\\') === 0) {
        $relativePath = str_replace('ZipStream\\', '', $class);
        $filePath = __DIR__ . '/zipstream/' . str_replace('\\', '/', $relativePath) . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }

    // Only handle PhpOffice\PhpSpreadsheet classes
    if (strpos($class, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
        $relativePath = str_replace('PhpOffice\\PhpSpreadsheet\\', '', $class);
        $filePath = __DIR__ . '/phpspreadsheet/src/PhpSpreadsheet/' . str_replace('\\', '/', $relativePath) . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
});

// Define PSR SimpleCache interface if not available
if (!interface_exists('Psr\\SimpleCache\\CacheInterface')) {
    // Create PSR namespace and interface
    eval('
        namespace Psr\SimpleCache;
        interface CacheInterface {
            public function get($key, $default = null);
            public function set($key, $value, $ttl = null): bool;
            public function delete($key): bool;
            public function clear(): bool;
            public function getMultiple($keys, $default = null);
            public function setMultiple($keys, $ttl = null): bool;
            public function deleteMultiple($keys): bool;
            public function has($key): bool;
        }
    ');
}

// Create a simple cache implementation
class SimpleMemoryCache implements \Psr\SimpleCache\CacheInterface {
    private $cache = [];

    public function clear(): bool {
        $this->cache = [];
        return true;
    }

    public function delete($key): bool {
        unset($this->cache[$key]);
        return true;
    }

    public function deleteMultiple($keys): bool {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function get($key, $default = null) {
        return $this->cache[$key] ?? $default;
    }

    public function getMultiple($keys, $default = null) {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function has($key): bool {
        return isset($this->cache[$key]);
    }

    public function set($key, $value, $ttl = null): bool {
        $this->cache[$key] = $value;
        return true;
    }

    public function setMultiple($keys, $ttl = null): bool {
        foreach ($keys as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }
}

// Configure PhpSpreadsheet to use our simple cache implementation
\PhpOffice\PhpSpreadsheet\Settings::setCache(new SimpleMemoryCache());

// Set default value binder for PhpSpreadsheet
\PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
?>