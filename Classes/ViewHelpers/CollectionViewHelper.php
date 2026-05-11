<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use Dla\DlaOpacNg\Service\CollectionService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class CollectionViewHelper extends AbstractViewHelper
{

    private CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('record', 'string', 'record id', false, 'string');
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $this->collectionService->useCollection();

        $record = $this->arguments['record'];
        $arrayData = [];
        if ($record) {
            if (strpos($record, '␝') === false) {
                $records[0] = $record;
            } else {
                $records = explode('␝', $record);
            }

            foreach ($records as $record) {
                $arrayData[] = $this->collectionService->getAllParents($record);
            }

            if (!empty($arrayData[0])) {
                foreach ($arrayData as $recordJsonData) {
                    foreach ($recordJsonData as $value) {
                        $breadcrumbArray[0] = [
                            'record_id' => $value['record_id'],
                            'uid' => $value['uid'],
                            'displayTree' => $value['displayTree'],
                        ];
                        $childObject = $value;
                        $i = 1;
                        while ($childObject['child']) {

                            $breadcrumbArray[$i] = [
                                'record_id' => $childObject['child']['record_id'],
                                'uid' => $childObject['child']['uid'],
                                'displayTree' => $childObject['child']['displayTree'],
                            ];
                            $i++;
                            if (empty($childObject['child']['child'])) {
                                break;
                            }
                            $childObject = $childObject['child'];
                        }
                        $resultValue[] = $breadcrumbArray;
                    }
                }
            } else {
                $resultValue = NULL;
            }
        } else {
            $resultValue = NULL;
        }

        $valueName = $this->arguments['as'];
        if ($valueName !== null) {
            if ($this->templateVariableContainer->exists($valueName)) {
                $this->templateVariableContainer->remove($valueName);
            }
            $this->templateVariableContainer->add($valueName, $resultValue);
//            $result = $this->renderChildren();
        }

    }
}
?>
