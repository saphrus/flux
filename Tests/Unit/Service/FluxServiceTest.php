<?php
namespace FluidTYPO3\Flux\Tests\Unit\Service;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Service\FluxService;
use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Tests\Unit\AbstractTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package Flux
 */
class FluxServiceTest extends AbstractTestCase {

	/**
	 * Teardown
	 */
	public function setup() {
		$providers = Core::getRegisteredFlexFormProviders();
		if (TRUE === in_array('FluidTYPO3\Flux\Service\FluxService', $providers)) {
			Core::unregisterConfigurationProvider('FluidTYPO3\Flux\Service\FluxService');
		}
	}

	/**
	 * @test
	 * @dataProvider getSortObjectsTestValues
	 * @param array $input
	 * @param string $sortBy
	 * @param string $direction
	 * @param array $expectedOutput
	 */
	public function testSortObjectsByProperty($input, $sortBy, $direction, $expectedOutput) {
		$service = new FluxService();
		$sorted = $service->sortObjectsByProperty($input, $sortBy, $direction);
		$this->assertEquals($expectedOutput, $sorted);
	}

	/**
	 * @return array
	 */
	public function getSortObjectsTestValues() {
		return array(
			array(
				array(array('foo' => 'b'), array('foo' => 'a')),
				'foo', 'ASC',
				array(array('foo' => 'a'), array('foo' => 'b'))
			),
			array(
				array('a1' => array('foo' => 'b'), 'a2' => array('foo' => 'a')),
				'foo', 'ASC',
				array('a2' => array('foo' => 'a'), 'a1' => array('foo' => 'b')),
			),
		);
	}

	/**
	 * @test
	 */
	public function dispatchesMessageOnInvalidPathsReturned() {
		$className = str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4));
		$instance = $this->getMock($className, array('getDefaultViewConfigurationForExtensionKey', 'getTypoScriptSubConfiguration'));
		$instance->expects($this->once())->method('getTypoScriptSubConfiguration')->will($this->returnValue(NULL));
		$instance->expects($this->once())->method('getDefaultViewConfigurationForExtensionKey')->will($this->returnValue(NULL));
		$instance->getViewConfigurationForExtensionName('Flux');
	}

	/**
	 * @test
	 */
	public function throwsExceptionWhenResolvingInvalidConfigurationProviderInstances() {
		$instance = $this->createInstance();
		$record = array('test' => 'test');
		Core::registerConfigurationProvider('FluidTYPO3\Flux\Service\FluxService');
		$this->setExpectedException('RuntimeException', NULL, 1327173536);
		$instance->flushCache();
		$instance->resolveConfigurationProviders('tt_content', 'pi_flexform', $record);
		Core::unregisterConfigurationProvider('FluidTYPO3\Flux\Service\FluxService');
	}

	/**
	 * @test
	 */
	public function canInstantiateFluxService() {
		$service = $this->createFluxServiceInstance();
		$this->assertInstanceOf('FluidTYPO3\Flux\Service\FluxService', $service);
	}

	/**
	 * @test
	 */
	public function canFlushCache() {
		$service = $this->createFluxServiceInstance();
		$service->flushCache();
	}

	/**
	 * @test
	 */
	public function canCreateExposedViewWithoutExtensionNameAndControllerName() {
		$service = $this->createFluxServiceInstance();
		$view = $service->getPreparedExposedTemplateView();
		$this->assertInstanceOf('FluidTYPO3\Flux\View\ExposedTemplateView', $view);
	}

	/**
	 * @test
	 */
	public function canCreateExposedViewWithExtensionNameWithoutControllerName() {
		$service = $this->createFluxServiceInstance();
		$view = $service->getPreparedExposedTemplateView('Flux');
		$this->assertInstanceOf('FluidTYPO3\Flux\View\ExposedTemplateView', $view);
	}

	/**
	 * @test
	 */
	public function canCreateExposedViewWithExtensionNameAndControllerName() {
		$service = $this->createFluxServiceInstance();
		$view = $service->getPreparedExposedTemplateView('Flux', 'API');
		$this->assertInstanceOf('FluidTYPO3\Flux\View\ExposedTemplateView', $view);
	}

	/**
	 * @test
	 */
	public function canCreateExposedViewWithoutExtensionNameWithControllerName() {
		$service = $this->createFluxServiceInstance();
		$view = $service->getPreparedExposedTemplateView(NULL, 'API');
		$this->assertInstanceOf('FluidTYPO3\Flux\View\ExposedTemplateView', $view);
	}

	/**
	 * @test
	 */
	public function canResolvePrimaryConfigurationProviderWithEmptyArray() {
		$service = $this->createFluxServiceInstance();
		$result = $service->resolvePrimaryConfigurationProvider('tt_content', NULL);
		$this->assertNull($result);
	}

	/**
	 * @test
	 */
	public function canResolveConfigurationProvidersWithEmptyArrayAndTriggerCache() {
		$service = $this->createFluxServiceInstance();
		$result = $service->resolvePrimaryConfigurationProvider('tt_content', NULL);
		$this->assertNull($result);
		$result = $service->resolvePrimaryConfigurationProvider('tt_content', NULL);
		$this->assertNull($result);
	}

	/**
	 * @test
	 */
	public function canGetTypoScriptSubConfigurationWithNonexistingExtensionNameAndReturnEmptyArray() {
		$service = $this->createFluxServiceInstance();
		$config = $service->getTypoScriptSubConfiguration('settings', 'view', 'flux');
		$this->assertNull($config);
	}

	/**
	 * @test
	 */
	public function canGetFormWithPaths() {
		$templatePathAndFilename = $this->getAbsoluteFixtureTemplatePathAndFilename(self::FIXTURE_TEMPLATE_BASICGRID);
		$service = $this->createFluxServiceInstance();
		$paths = array(
			'templateRootPath' => 'EXT:flux/Resources/Private/Templates',
			'partialRootPath' => 'EXT:flux/Resources/Private/Partials',
			'layoutRootPath' => 'EXT:flux/Resources/Private/Layouts'
		);
		$form1 = $service->getFormFromTemplateFile($templatePathAndFilename, 'Configuration', 'form', $paths, 'flux');
		$form2 = $service->getFormFromTemplateFile($templatePathAndFilename, 'Configuration', 'form', $paths, 'flux');
		$this->assertInstanceOf('FluidTYPO3\Flux\Form', $form1);
		$this->assertInstanceOf('FluidTYPO3\Flux\Form', $form2);
	}

	/**
	 * @test
	 */
	public function getFormReturnsNullOnInvalidFile() {
		$templatePathAndFilename = '/void/nothing';
		$service = $this->createFluxServiceInstance();
		$form = $service->getFormFromTemplateFile($templatePathAndFilename);
		$this->assertNull($form);
	}

	/**
	 * @test
	 */
	public function canGetFormWithPathsAndTriggerCache() {
		$path = ExtensionManagementUtility::extPath('flux');
		$templatePathAndFilename = GeneralUtility::getFileAbsFileName(self::FIXTURE_TEMPLATE_BASICGRID);
		$service = $this->createFluxServiceInstance();
		$paths = array(
			'templateRootPath' => $path . 'Tests/Unit/Fixtures/Templates',
			'partialRootPath' => $path . 'Tests/Unit/Fixtures/Partials',
			'layoutRootPath' => $path . 'Resources/Private/Layouts'
		);
		$form = $service->getFormFromTemplateFile($templatePathAndFilename, 'Configuration', 'form', $paths, 'flux');
		$this->assertInstanceOf('FluidTYPO3\Flux\Form', $form);
		$readAgain = $service->getFormFromTemplateFile($templatePathAndFilename, 'Configuration', 'form', $paths, 'flux');
		$this->assertInstanceOf('FluidTYPO3\Flux\Form', $readAgain);
	}

	/**
	 * @test
	 */
	public function canReadGridFromTemplateWithoutConvertingToDataStructure() {
		$templatePathAndFilename = $this->getAbsoluteFixtureTemplatePathAndFilename(self::FIXTURE_TEMPLATE_BASICGRID);
		$form = $this->performBasicTemplateReadTest($templatePathAndFilename);
		$this->assertInstanceOf('FluidTYPO3\Flux\Form', $form);
	}

	/**
	 * @test
	 */
	public function canRenderTemplateWithCompactingSwitchedOn() {
		$backup = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['compact'];
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['compact'] = '1';
		$templatePathAndFilename = $this->getAbsoluteFixtureTemplatePathAndFilename(self::FIXTURE_TEMPLATE_COMPACTED);
		$service = $this->createFluxServiceInstance();
		$form = $service->getFormFromTemplateFile($templatePathAndFilename);
		$this->assertInstanceOf('FluidTYPO3\Flux\Form', $form);
		$stored = $form->build();
		$this->assertIsArray($stored);
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['compact'] = $backup;
	}

	/**
	 * @test
	 */
	public function canRenderTemplateWithCompactingSwitchedOff() {
		$backup = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['compact'];
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['compact'] = '0';
		$templatePathAndFilename = $this->getAbsoluteFixtureTemplatePathAndFilename(self::FIXTURE_TEMPLATE_SHEETS);
		$this->performBasicTemplateReadTest($templatePathAndFilename);
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['flux']['setup']['compact'] = $backup;
	}

	/**
	 * @test
	 */
	public function canGetBackendViewConfigurationForExtensionName() {
		$service = $this->createFluxServiceInstance();
		$config = $service->getBackendViewConfigurationForExtensionName('noname');
		$this->assertNull($config);
	}

	/**
	 * @test
	 */
	public function canGetViewConfigurationForExtensionNameWhichDoesNotExistAndConstructDefaults() {
		$expected = array(
			'templateRootPath' => 'EXT:void/Resources/Private/Templates',
			'partialRootPath' => 'EXT:void/Resources/Private/Partials',
			'layoutRootPath' => 'EXT:void/Resources/Private/Layouts',
		);
		$service = $this->createFluxServiceInstance();
		$config = $service->getViewConfigurationForExtensionName('void');
		$this->assertSame($expected, $config);
	}

	/**
	 * @disabledtest
	 */
	public function templateWithErrorReturnsFormWithErrorReporter() {
		$badSource = '<f:layout invalid="TRUE" />';
		$temp = tempnam($_SERVER['TEMPDIR'], 'badtemplate') . '.html';
		// @todo: use vfs
		$form = $this->createFluxServiceInstance()->getFormFromTemplateFile($temp);
		$this->assertInstanceOf('FluidTYPO3\Flux\Form', $form);
		$this->assertInstanceOf('FluidTYPO3\Flux\Form\Field\UserFunction', reset($form->getFields()));
		$this->assertEquals('FluidTYPO3\Flux\UserFunction\ErrorReporter->renderField', reset($form->getFields())->getFunction());
	}

}
