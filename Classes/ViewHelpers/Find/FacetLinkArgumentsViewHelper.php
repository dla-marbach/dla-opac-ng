<?php
namespace Subugoe\Find\ViewHelpers\Find;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013
 *      Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Sven-S. Porst <porst@sub.uni-goettingen.de>
 *      Göttingen State and University Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns additional parameters needed to create links for facets.
 *
 * Arguments:
 *  - facetID: ID of the facet to create the link for
 *  - facetTerm: the value of the facet’s item in question [optional in remove mode]
 *  - activeFacets: the array of active facets
 *  - mode: return an array for
 *      - add: f.link.action’s »arguments«, adding a facet selection
 *       - remove: f.link.action’s »argumentsToBeExcludedFromQueryString«, removing a facet selection
 *              leaving out the facetTerm parameter removes all selected items for the facet facetID
 */
class FacetLinkArgumentsViewHelper extends AbstractViewHelper {

	/**
	 * Create the return array required to add/remove the URL parameters by
	 * passing it to f.link.action’s »arguments«
	 * or »argumentsToBeExcludedFromQueryString«.
	 *
	 * @param string $facetID The name of the facet to create the link for
	 * @param string $facetTerm Term of the facet item to create the link for
	 * @param array $activeFacets Array of active facets
	 * @param string $mode One of »add« or »remove« depending on whether the result is used with »arguments« or with »argumentsToBeExcludedFromQueryString«
	 * @return array
	 */
	public function render($facetID, $facetTerm = '', $activeFacets = [], $mode = 'add') {
		$result = [];

		if ($mode === 'remove' && $activeFacets) {

			if (array_key_exists($facetID, $activeFacets)) {
				$itemToRemove = 'tx_find_find[facet][' . $facetID . ']';

				if (array_key_exists($facetTerm, $activeFacets[$facetID])) {
					$itemToRemove .= '[' . $facetTerm . ']';
				}
				$result[] = $itemToRemove;
			}
			// Go back to page 1.
			$result[] = 'tx_find_find[page]';
		} else if ($mode === 'add') {
			$result['facet'] = [
				 $facetID => [$facetTerm => 1]
			];
		}

		return $result;
	}
}
