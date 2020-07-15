<?php

namespace Dla\DlaOpacNg\ViewHelpers;


class CollectionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{


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
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://';
        $record = $this->arguments['record'];
        if ($record) {
            $url = $protocol . $_SERVER['HTTP_HOST'] . '/index.php?eID=collection&type=collection&action=getAllParents&id=' . $record;
            $jsonData = file_get_contents($url);
            $arrayData = json_decode($jsonData);

            if ($arrayData) {
                $breadcrumbArray[0] = [
                    'record_id' => $arrayData->record_id,
                    'uid' => $arrayData->uid,
                ];
                $childObject = $arrayData;
                $i = 1;
                while ($childObject->child) {

                    $breadcrumbArray[$i] = [
                        'record_id' => $childObject->child->record_id,
                        'uid' => $childObject->child->uid,
                    ];
                    $i++;
                    $childObject = $childObject->child;
                }
                $resultValue = $breadcrumbArray;
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
