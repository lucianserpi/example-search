<?php declare(strict_types=1);

namespace lucianserpi\exampleSearch;

require_once __DIR__ . '/vendor/autoload.php';

use Josantonius\Request\Request;
use Location\Coordinate;
use Location\Distance\Vincenty;

$httpRequest = Request::input('GET');
$serviceName = $httpRequest('service')->asString();
$position = $httpRequest('position')->asArray([
    'lng' => 'float',
    'lat' => 'float'
]);

try {
    if (!validate($serviceName, $position)) {
      throw new \Exception("Bad request", 400);
    }

    $filePath = __DIR__ . '/data.json';
    $calculator = new Vincenty();

    $searchService = new SearchService(
      $filePath,
      $calculator
    );

    $coordinate = new Coordinate($position['lat'], $position['lng']);

    $result = $searchService->find($serviceName, $coordinate);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
} catch (\Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($e->getCode());
    echo json_encode(
      ["error" => $e->getMessage()]
    );
}

function validate(?string $message, ?array $position): bool
{
  return !empty($message) &&
    !empty($position['lng']) &&
    !empty($position['lat']);
}
