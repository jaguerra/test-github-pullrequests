<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Site Import Service
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class SiteImportService {

	/**
	 * @inject
	 * @var F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @inject
	 * @var F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @inject
	 * @var F3\TYPO3\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var \F3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @inject
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Checks for the presence of Content.xml in the given package and imports
	 * it if found.
	 *
	 * @param string $packageKey
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Christian Müller <christian@kitsunet.de>
	 */
	public function importPackage($packageKey) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			throw new \F3\FLOW3\Exception('Error: Package "' . $packageKey . '" is not active.');
		} elseif (!file_exists('resource://' . $packageKey . '/Private/Content/Sites.xml')) {
			throw new \F3\FLOW3\Exception('Error: No content found in package "' . $packageKey . '".');
		} else {
			$this->nodeRepository->removeAll();
			$this->workspaceRepository->removeAll();
			$this->domainRepository->removeAll();
			$this->siteRepository->removeAll();

			$this->persistenceManager->persistAll();

			try {
				$this->importSitesFromPackage($packageKey);
			} catch (\Exception $e) {
				throw new \F3\FLOW3\Exception('Error: During import an exception occured. ' . $e->getMessage());
			}
		}
	}

	/**
	 * Parses the Content.xml in the given package and imports the content into TYPO3.
	 *
	 * @param string $packageKey
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function importSitesFromPackage($packageKey) {
		$contentContext = $this->objectManager->create('F3\TYPO3\Domain\Service\ContentContext', 'live');

		$xml = new \SimpleXMLElement(file_get_contents('resource://' . $packageKey . '/Private/Content/Sites.xml'));
		foreach ($xml->site as $siteXml) {
			$site = $this->objectManager->create('F3\TYPO3\Domain\Model\Site', (string)$siteXml['nodeName']);
			$site->setName((string)$siteXml->properties->name);
			$site->setState((integer)$siteXml->properties->state);
			$site->setSiteResourcesPackageKey($packageKey);
			$this->siteRepository->add($site);

			$rootNode = $contentContext->getWorkspace()->getRootNode();
			$siteNode = $rootNode->createNode('sites')->createNode($site->getNodeName());
			$siteNode->setContentObject($site);

			$this->parseNodes($siteXml, $siteNode);
		}
	}

	/**
	 * Iterates over the nodes and adds them to the workspace.
	 *
	 * @param \SimpleXMLElement $parentXml
	 * @param \F3\TYPO3CR\Domain\Model\Node $parentNode
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseNodes(\SimpleXMLElement $parentXml, $parentNode) {
		foreach ($parentXml->node as $childNodeXml) {
			$locale = $this->objectManager->create('F3\FLOW3\I18n\Locale', (string)$childNodeXml['locale']);
			$childNode = $parentNode->createNode((string)$childNodeXml['nodeName']);
			$childNode->setContentType((string)$childNodeXml['type']);
			if ($childNodeXml->properties) {
				foreach ($childNodeXml->properties->children() as $childXml) {
					$childNode->setProperty($childXml->getName(), (string)$childXml);
				}
			}
			if ($childNodeXml->node) {
				$this->parseNodes($childNodeXml, $childNode);
			}
		}
	}
}
?>