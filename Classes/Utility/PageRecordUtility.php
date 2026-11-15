<?php

declare(strict_types=1);

namespace Dla\DlaOpacNg\Utility;

use Psr\Http\Message\ServerRequestInterface;

class PageRecordUtility
{
    /**
     * Datensatz der aktuellen Seite (in der Sprache der Anfrage): ab TYPO3 v13 aus dem Request-Attribut
     * »frontend.page.information«, in TYPO3 v12 aus dem TypoScriptFrontendController, den es ab
     * TYPO3 v14 nicht mehr gibt.
     */
    public static function fromRequest(ServerRequestInterface $request): array
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        if ($pageInformation !== null) {
            return $pageInformation->getPageRecord();
        }

        return $request->getAttribute('frontend.controller')?->page ?? [];
    }
}
