<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use Symfony\Component\HttpFoundation\IpUtils;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Core\Environment;

class MediaPlayerViewHelper extends AbstractViewHelper
{


    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('urls', 'mixed', 'urls', true, []);
        $this->registerArgument('ext', 'mixed', 'ext', false, []);
        $this->registerArgument('access', 'mixed', 'access', true, []);
        $this->registerArgument('display', 'mixed', 'display labels', false, []);
        $this->registerArgument('as', 'string', 'name of the label result variable', true, 'string');
    }

    /**
     * Normalize Fluid input to an array. Item fields can come in as strings
     * and may contain the special record separator used by Solr exports.
     */
    protected function normalizeToArray($value): array
    {
        $normalized = [];
        $values = is_array($value) ? $value : [$value];

        foreach ($values as $entry) {
            if ($entry === null || $entry === '') {
                continue;
            }

            if (is_string($entry) && preg_match('/[␝\x1D]/u', $entry)) {
                $parts = preg_split('/[␝\x1D]/u', $entry);
                if (is_array($parts)) {
                    foreach ($parts as $part) {
                        $normalized[] = is_string($part) ? trim($part) : $part;
                    }
                }
                continue;
            }

            $normalized[] = is_string($entry) ? trim($entry) : $entry;
        }

        return $normalized;
    }

    /**
     * @return boolean
     */
    public function render()
    {
        $campusRanges = explode(',', getenv('campusRanges'));
        $sandboxRanges = explode(',', getenv('sandboxRanges'));
        $staffRanges = explode(',', getenv('staffRanges'));

        // ip-adress ranges
        $ipRanges = [
            'staff' => $staffRanges,
            'sandbox' => $sandboxRanges,
            'campus' => $campusRanges
        ];

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $currentGroup = [];
        foreach ($ipRanges as $group => $range) {
            if (IpUtils::checkIp($clientIp, $range)) {
                $currentGroup[$group] = 1;
            }
        }

//        $restrictionGroups = ['op_admin', 'op_campus', 'op_sandbox', 'op_staff'];
//        $userRestrictionGroup = 'public';

        $mediaPlayerExt = ['wav', 'mp4', 'mp2', 'mp3'];
        $linkExt = ['pdf', 'epub', 'rtf', 'jpg', 'txt', 'lnk'];

        $urls = $this->normalizeToArray($this->arguments['urls'] ?? null);
        $ext = $this->normalizeToArray($this->arguments['ext'] ?? null);
        $access = $this->normalizeToArray($this->arguments['access'] ?? null);
        $display = $this->normalizeToArray($this->arguments['display'] ?? null);
        $resultValue = ['links' => []];

//        $context = GeneralUtility::makeInstance(Context::class);
//        $userAspect = $context->getAspect('frontend.user');
//
//        if ($userAspect->isLoggedIn()) {
//            $userId = $userAspect->get('id');
//            $userGroupIds = $userAspect->get('groupIds');
//        }

        // frontend user restriction
//        $countGroupDataFE = $GLOBALS['TSFE']->fe_user->fetchGroupData();
//        if ($countGroupDataFE > 0) {
//            foreach ($GLOBALS['TSFE']->fe_user->groupData['title'] as $groupId => $groupTitle) {
//                if(in_array($groupTitle, $restrictionGroups)) {
//                    $userRestrictionGroup = $groupTitle;
//                }
//            }
//        }

        $i = 0;
        foreach ($access as $value) {
            // Show file data with information if file is forbidden
            $urlAccess = [
                'url' => $urls[$i] ?? null,
                'ext' => $ext[$i] ?? null,
                'display' => $display[$i] ?? null,
                'access' => $value,
                'forbidden' => 1,
            ];
            if ((is_string($value) && array_key_exists($value, $currentGroup)) || $value === 'public') {
                $urlAccess['forbidden'] = 0;
            }

            $resultValue['links'][] = $urlAccess;

//            if (in_array($ext[$i], $mediaPlayerExt)) {
//                $resultValue['mediaplayer'][] = $urlAccess;
//            }
//            if (in_array($ext[$i], $linkExt)) {
//                $resultValue['links'][] = $urlAccess;
//            }
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
