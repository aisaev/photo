<?php
namespace photo\edit;

require_once __DIR__ . '/../application/ToolsAPI.class.php';

try {
    $o_api = new ToolsAPI();
    echo (isset($_GET['v']) ? 'var ' . $_GET['v'] . '=' : '') . json_encode([
        'e' => 0,
        'r' => $o_api->process()
    ], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    echo (isset($_GET['v']) ? 'var ' . $_GET['v'] . '=' : '') . json_encode([
        'e' => 1,
        'm' => $e->getMessage(),
        'd' => isset($_POST['d']) ? $_POST['d'] : false
    ], JSON_UNESCAPED_UNICODE);
}

?>