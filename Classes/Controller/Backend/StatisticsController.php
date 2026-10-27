<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Controller\Backend;

use Dla\DlaOpacNg\Statistics\StatisticsConfiguration;
use Dla\DlaOpacNg\Statistics\StatisticsRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Backend-Modul »Katalog-Statistik«: liefert die Seite (HTML, CSS, Vanilla-JS) und die aggregierten Daten als JSON.
 */
#[AsController]
final class StatisticsController
{
    private const LANGUAGE_FILE = 'EXT:dla_opac_ng/Resources/Private/Language/locallang_statistics.xlf';

    /**
     * Maximal abfragbarer Zeitraum in Tagen.
     */
    private const MAX_RANGE_DAYS = 3660;

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly UriBuilder $uriBuilder,
    ) {
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->addCssFile('EXT:dla_opac_ng/Resources/Public/CSS/Backend/statistics.css');
        $this->pageRenderer->addInlineLanguageLabelFile(self::LANGUAGE_FILE, 'js.', 'js.');
        $this->pageRenderer->loadJavaScriptModule('@dla/dla-opac-ng/statistics.js');

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($this->getLanguageService()->sL('LLL:' . self::LANGUAGE_FILE . ':mlang_tabs_tab'));
        $view->assignMultiple([
            'dataUrl' => (string)$this->uriBuilder->buildUriFromRoute('dlaopacng_statistics.data'),
            'enabled' => StatisticsConfiguration::isEnabled(),
            'termsMinCount' => StatisticsConfiguration::getTermsMinCount(),
        ]);

        return $view->renderResponse('Backend/Statistics');
    }

    /**
     * Aggregierte Daten für den Zeitraum ?from=YYYY-MM-DD&to=YYYY-MM-DD (Standard: die letzten 30 Tage).
     */
    public function dataAction(ServerRequestInterface $request): ResponseInterface
    {
        $timezone = StatisticsConfiguration::getTimezone();
        $today = new \DateTimeImmutable('today', $timezone);
        $params = $request->getQueryParams();

        $to = $this->parseDate($params['to'] ?? null, $timezone) ?? $today;
        $from = $this->parseDate($params['from'] ?? null, $timezone) ?? $to->modify('-29 days');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        if ((int)$from->diff($to)->days >= self::MAX_RANGE_DAYS) {
            $from = $to->modify('-' . (self::MAX_RANGE_DAYS - 1) . ' days');
        }

        $data = (new StatisticsRepository())->load($from, $to, StatisticsConfiguration::getTermsMinCount());

        return new JsonResponse($data, 200, ['Cache-Control' => 'no-store']);
    }

    private function parseDate(mixed $value, \DateTimeZone $timezone): ?\DateTimeImmutable
    {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);

        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value ? $date : null;
    }

    private function getLanguageService(): \TYPO3\CMS\Core\Localization\LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
