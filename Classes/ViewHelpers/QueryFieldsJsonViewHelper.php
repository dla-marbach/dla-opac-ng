<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class QueryFieldsJsonViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('queryFields', 'array', 'Query fields configuration from TypoScript settings', true);
    }

    public function render(): string
    {
        $fields = [];
        foreach ($this->arguments['queryFields'] as $queryField) {
            if (!empty($queryField['id']) && !empty($queryField['query'])) {
                $fields[] = [
                    'id' => $queryField['id'],
                    'query' => $queryField['query'],
                ];
            }
        }
        return (string)json_encode($fields, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
}
