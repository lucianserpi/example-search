<?php declare(strict_types=1);

namespace lucianserpi\exampleSearch;

use Location\Coordinate;
use Location\Distance\DistanceInterface;

class SearchService
{
  private string $filePath;
  private DistanceInterface $calculator;

  public function __construct(
    string $filePath,
    DistanceInterface $calculator
  ) {
      $this->filePath = $filePath;
      $this->calculator = $calculator;
  }

  public function find(
    string $serviceName,
    Coordinate $coordinate
  ): array {
      $jsonData = file_get_contents($this->filePath);
      $services = json_decode($jsonData, true);

      $results = $this->filterByServiceName($services, $serviceName);

      $results = $this->calculateDistance($results, $coordinate);

      $results = $this->calculateScorring($results, $serviceName);

      return [
          "totalHits" => count($results),
          "totalDocuments" => count($services),
          "results" => $results,
      ];
  }

  private function filterByServiceName(
    array $services,
    string $serviceName
  ): array {
      return array_filter($services, function($service) use ($serviceName) {
          return strstr(
            $this->sanitizeName($service['name']),
            $this->sanitizeName($serviceName)
          );
      });
  }

  private function calculateDistance(
    array $services,
    Coordinate $coordinate
  ): array {
      return array_map(function ($service) use ($coordinate) {
        $serviceCoordinate = new Coordinate(
          $service['position']['lat'],
          $service['position']['lng']
        );

        $distance = $this->calculator->getDistance($coordinate, $serviceCoordinate);

        return $service + [
          'distance' => $this->formatDistance($distance),
        ];
      }, $services);
  }

  private function calculateScorring(array $services, string $serviceName): array
  {
      return array_map(function ($service) use ($serviceName) {
        return $service + [
          'score' => levenshtein($service['name'], $serviceName),
        ];
      }, $services);
  }

  public function sanitizeName(string $serviceName): string
  {
      $serviceName = strtolower($serviceName);
      $serviceName = trim($serviceName);
      return preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities($serviceName));
  }

  public function formatDistance(float $distance): string
  {
      if ($distance < 1000) {
        return round($distance) . 'm';
      } else {
        return number_format($distance/1000, 2, '.', '') . 'Km';
      }
  }
}
