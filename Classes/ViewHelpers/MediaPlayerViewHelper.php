<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MediaPlayerViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('urls', 'array', 'urls', true, 'array');
        $this->registerArgument('ext', 'array', 'ext', true, 'array');
        $this->registerArgument('access', 'array', 'access', true, 'array');
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * @return boolean
     */
    public function render()
    {
        //        "campus"
        //        "public"
        //        "sandbox"
        //        "staff"

        //         "wav",16,
        //        "pdf",15,
        //        "jpg",14,
        //        "mp4",4,
        //        "00043378",2,
        //        "10",2,
        //        "de",2,
        //        "object",2,
        //        "viewer",2,
        //        "epub",1,
        //        "mp2",1,
        //        "rtf",1

        $restrictionGroups = ['public', 'campus', 'sandbox', 'staff'];
        $userRestrictionGroup = 'public';

        $mediaPlayerExt = ['wav', 'mp4', 'mp2'];

        $linkExt = ['pdf', 'epub', 'rtf', 'jpg', 'txt', 'lnk'];

        $urls = $this->arguments['urls'];
        $ext = $this->arguments['ext'];
        $access = $this->arguments['access'];

        // frontend user restriction
        $countGroupDataFE = $GLOBALS['TSFE']->fe_user->fetchGroupData();
        if ($countGroupDataFE > 0) {
            foreach ($GLOBALS['TSFE']->fe_user->groupData['title'] as $groupId => $groupTitle) {
                if(in_array($groupTitle, $restrictionGroups)) {
                    $userRestrictionGroup = $groupTitle;
                }
            }
        }
        $i = 0;
        foreach ($access as $value) {
            // Show file data with information if file is forbidden
            $urlAccess = ['url' => $urls[$i], 'ext' => $ext[$i], 'forbidden' => 1];
            if ($value == $userRestrictionGroup || $value == 'public') {
                $urlAccess['forbidden'] = 0;
            }

            if (in_array($ext[$i], $mediaPlayerExt)) {
                $resultValue['mediaplayer'][] = $urlAccess;
            }
            if (in_array($ext[$i], $linkExt)) {
                $resultValue['links'][] = $urlAccess;
            }
            $i++;
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
