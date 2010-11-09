<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\ViewHelpers\Link;

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
 * Testcase for the Link Node view helper
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NodeViewHelperTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\TYPO3\ViewHelpers\Link\NodeViewHelper
	 */
	protected $viewHelper;

	/**
	 * Set up common mocks and object under test
	 */
	public function setUp() {
		$this->request = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$this->request->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('MyPackage'));
		$this->uriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$this->controllerContext = $this->getMock('F3\FLOW3\MVC\Controller\ControllerContext', array(), array(), '', FALSE);
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
		$this->controllerContext->expects($this->any())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
		$this->tagBuilder = $this->getMock('F3\Fluid\Core\ViewHelper\TagBuilder');
		$this->viewHelper = $this->getAccessibleMock('F3\TYPO3\ViewHelpers\Link\NodeViewHelper', array('renderChildren'));
		$this->viewHelper->setControllerContext($this->controllerContext);
		$this->viewHelper->injectTagBuilder($this->tagBuilder);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderWithNodeGeneratesLinkToHtmlFrontend() {
		$node = $this->getMock('F3\TYPO3CR\Domain\Model\Node', array(), array(), '', FALSE);

		$this->uriBuilder->expects($this->once())->method('reset')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(FALSE)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setFormat')->with('html')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('uriFor')->with(NULL, array('node' => $node, 'service' => 'Frontend'), 'Node', 'TYPO3', 'Service\Rest\V1')->will($this->returnValue('http://someuri/path'));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('href', 'http://someuri/path');
		$this->tagBuilder->expects($this->once())->method('render')->will($this->returnValue('tag output'));

		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child content'));

		$output = $this->viewHelper->render($node);
		$this->assertEquals('tag output', $output);
	}

}
?>