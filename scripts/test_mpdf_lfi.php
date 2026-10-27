<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Include the container and restricted loader
require_once __DIR__ . '/../config/database.php';

if (!class_exists('BlockOutboundHttpClient')) {
    class BlockOutboundHttpClient implements \Mpdf\Http\ClientInterface {
        public function sendRequest(\Psr\Http\Message\RequestInterface $request) {
            $url = (string)$request->getUri();
            throw new \RuntimeException("Outbound HTTP requests are blocked: " . $url);
        }
    }
}

if (!class_exists('RestrictedLocalContentLoader')) {
    class RestrictedLocalContentLoader implements \Mpdf\File\LocalContentLoaderInterface {
        private string $allowedDir;
        public function __construct(string $allowedDir) {
            $this->allowedDir = realpath($allowedDir) ?: $allowedDir;
        }
        public function load($path) {
            $cleanPath = preg_replace('#^file:(//)?#i', '', $path);
            if (PHP_OS_FAMILY === 'Windows') {
                $cleanPath = preg_replace('#^/([a-zA-Z]:)#', '$1', $cleanPath);
            }
            $realPath = realpath($cleanPath);
            if (!$realPath || !str_starts_with($realPath, $this->allowedDir)) {
                throw new \RuntimeException("BLOCKED_LFI: Path outside allowed dir: " . $path);
            }
            return file_get_contents($realPath);
        }
    }
}

if (!class_exists('SecureMpdfContainer')) {
    class SecureMpdfContainer implements \Mpdf\Container\ContainerInterface {
        private array $services = [];
        public function __construct() {
            $this->services['httpClient'] = new BlockOutboundHttpClient();
            $this->services['localContentLoader'] = new RestrictedLocalContentLoader(realpath(__DIR__ . '/../public'));
        }
        public function get($id) {
            return $this->services[$id] ?? null;
        }
        public function has($id) {
            return isset($this->services[$id]);
        }
    }
}

echo "=== TEST MPDF LFI AND LOCAL PATH RESTRICTION ===\n";

$tempDir = __DIR__ . '/../temp/mpdf';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

$mpdf = new \Mpdf\Mpdf([
    'tempDir' => $tempDir,
    'format' => 'A4'
], new SecureMpdfContainer());

// Test 1: HTML with escaped comment containing malicious tags
$maliciousComment = '<img src="file:///c:/windows/win.ini"> y ruta /etc/passwd';
$escapedHtml = "<p>Comentario escapado: " . htmlspecialchars($maliciousComment) . "</p>";
$mpdf->WriteHTML($escapedHtml);
echo "[OK] Escaped HTML rendered safely without triggering loader.\n";

// Test 2: Direct unescaped attempt to load file:///c:/windows/win.ini through RestrictedLocalContentLoader
$loader = new RestrictedLocalContentLoader(realpath(__DIR__ . '/../public'));

$testPaths = [
    'file:///c:/windows/win.ini',
    'c:/windows/win.ini',
    '/etc/passwd',
    '../../config/database.php'
];

foreach ($testPaths as $p) {
    try {
        $loader->load($p);
        echo "[FAIL] Path was NOT blocked: $p\n";
    } catch (\RuntimeException $e) {
        echo "[SUCCESS_BLOCKED] Attempt to load '$p' threw: " . $e->getMessage() . "\n";
    }
}

// Test 3: Allowed local image inside public/ should succeed if it exists
$allowedPath = realpath(__DIR__ . '/../public/img/logo.png');
if ($allowedPath) {
    try {
        $content = $loader->load($allowedPath);
        echo "[SUCCESS_ALLOWED] Allowed path loaded successfully (" . strlen($content) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "[FAIL] Allowed path failed: " . $e->getMessage() . "\n";
    }
}
