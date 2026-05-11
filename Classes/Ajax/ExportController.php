<?php

namespace Dla\DlaOpacNg\Ajax;

use Dla\DlaOpacNg\Service\EntityService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\CsvUtility;

class ExportController implements MiddlewareInterface
{
    private const PLUGIN_PATH = '/find/opac/id/';
    private const DELIMITER = ';';

    private EntityService $entityService;

    public function __construct(EntityService $entityService)
    {
        $this->entityService = $entityService;
    }

    private function normalizeCsvValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(';', array_map(static fn($item): string => htmlspecialchars_decode((string)$item), $value));
        }

        if ($value === null) {
            return '';
        }

        return htmlspecialchars_decode((string)$value);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (empty($queryParams['watchlistCsv'])) {
            return $handler->handle($request);
        }

        $ids = '';
        if (!empty($queryParams['ids']) && is_string($queryParams['ids'])) {
            $ids = $queryParams['ids'];
        } elseif (!empty($request->getCookieParams()['list']) && is_string($request->getCookieParams()['list'])) {
            $ids = $request->getCookieParams()['list'];
        }

        $uri = $request->getUri();
        $protocol = $uri->getScheme() !== '' ? $uri->getScheme() . '://' : 'http://';
        $host = $uri->getHost();
        $port = $uri->getPort();
        $portPart = '';
        if ($port !== null && !in_array($port, [80, 443], true)) {
            $portPart = ':' . $port;
        }

        $baseUrl = $protocol . $host . $portPart;
        $entityIds = trim($ids, ',');
        $entities = $this->entityService->getEntities($entityIds);

        $output = "\xEF\xBB\xBF";

        $objDateTime = new \DateTime('NOW');
        $line = ['Deutsches Literaturarchiv Marbach. Auszug Katalog. ' . $objDateTime->format('d.m.Y')];
        $output .= CsvUtility::csvValues($line, self::DELIMITER) . PHP_EOL;

        $line = ['ID', 'Link', 'Titelbeschreibung', 'Medientyp', 'Form und Inhalt', 'Medium', 'Zeit', 'Personen', 'Thema', 'Sprache', 'Ort', 'Datenbestand', 'Bibliografie', 'Sammlung', 'Digital'];
        $output .= CsvUtility::csvValues($line, self::DELIMITER) . PHP_EOL;

        $detailBaseUrl = rtrim($baseUrl, '/');
        foreach ($entities as $entry) {
            $line = [
                $entry['id'],
                $detailBaseUrl . self::PLUGIN_PATH . $entry['id'],
                $entry['title'],
                $this->normalizeCsvValue($entry['filterType_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterFormContent_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterMedium_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterDateRange_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterAuthority_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterSubject_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterLanguage_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterLocation_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterSource'] ?? ''),
                $this->normalizeCsvValue($entry['filterBibliography_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterCollection_mv'] ?? ''),
                $this->normalizeCsvValue($entry['filterDigital'] ?? ''),
            ];
            $output .= CsvUtility::csvValues($line, self::DELIMITER) . PHP_EOL;
        }

        $csvResponse = new Response();
        $csvResponse = $csvResponse
            ->withAddedHeader('Content-Encoding', 'UTF-8')
            ->withAddedHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withAddedHeader('Content-Disposition', 'attachment; filename="marbach.csv"');
        $csvResponse->getBody()->write($output);

        return $csvResponse;
    }
}
