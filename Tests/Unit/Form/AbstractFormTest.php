<?php
namespace FluidTYPO3\Flux\Tests\Unit\Form;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Form\FormInterface;
use FluidTYPO3\Flux\Tests\Unit\AbstractTestCase;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @package Flux
 */
abstract class AbstractFormTest extends AbstractTestCase {

	/**
	 * @var array
	 */
	protected $chainProperties = array('name' => 'test', 'label' => 'Test field');

	/**
	 * @return FormInterface
	 */
	protected function createInstance() {
		$className = $this->getObjectClassName();
		$instance = $this->objectManager->get($className);
		return $instance;
	}

	/**
	 * @test
	 */
	public function canGetAndSetExtensionName() {
		$form = $this->createInstance();
		$form->setExtensionName('Flux');
		$this->assertEquals('Flux', $form->getExtensionName());
	}

	/**
	 * @test
	 */
	public function canGetAndSetVariables() {
		$variables = array('test' => 'foobar');
		$this->assertGetterAndSetterWorks('variables', $variables, $variables, TRUE);
	}

	/**
	 * @test
	 */
	public function canGetAndSetSingleVariable() {
		$test = 'foobar';
		$instance = $this->createInstance();
		$instance->setVariable('test', $test);
		$this->assertEquals($test, $instance->getVariable('test'));
	}

	/**
	 * @test
	 */
	public function canGetLabel() {
		$className = $this->getObjectClassName();
		$instance = $this->objectManager->get($className);
		$instance->setName('test');
		if (TRUE === $instance instanceof FieldInterface || TRUE === $instance instanceof ContainerInterface) {
			$form = Form::create(array('extensionKey' => 'flux'));
			$form->add($instance);
		}
		$label = $instance->getLabel();
		$this->assertNotEmpty($label);
	}

	/**
	 * @test
	 */
	public function canGenerateRawLabelWhenLanguageLabelsDisabled() {
		$instance = $this->createInstance();
		$instance->setLabel(NULL);
		$instance->setDisableLocalLanguageLabels(TRUE);
		$this->assertNull($instance->getLabel());
	}

	/**
	 * @test
	 */
	public function canGenerateLocalisableLabel() {
		$instance = $this->createInstance();
		$instance->setLabel(NULL);
		$instance->setExtensionName('Flux');
		if (TRUE === $instance instanceof Form) {
			$instance->setName('testFormId');
			$instance->setExtensionName('Flux');
		} else {
			/** @var Form $form */
			$instance->setName('testFormId');
			$form = Form::create(array(
				'name' => 'test',
				'extensionName' => 'flux'
			));
			$form->add($instance);
		}
		$label = $instance->getLabel();
		$this->assertContains('testFormId', $label);
		$this->assertStringStartsWith('LLL:EXT:flux/Resources/Private/Language/locallang.xlf:flux', $label);
	}

	/**
	 * @return string
	 */
	protected function getObjectClassName() {
		$class = get_class($this);
		$class = substr($class, 0, -4);
		$class = str_replace('\\Tests\\Unit', '', $class);
		return $class;
	}

	/**
	 * @test
	 * @param array $chainPropertiesAndValues
	 * @return FieldInterface
	 */
	public function canChainAllChainableSetters($chainPropertiesAndValues = NULL) {
		if (NULL === $chainPropertiesAndValues) {
			$chainPropertiesAndValues = $this->chainProperties;
		}
		$instance = $this->createInstance();
		foreach ($chainPropertiesAndValues as $propertyName => $propertValue) {
			$setterMethodName = ObjectAccess::buildSetterMethodName($propertyName);
			$chained = call_user_func_array(array($instance, $setterMethodName), array($propertValue));
			$this->assertSame($instance, $chained, 'The setter ' . $setterMethodName . ' on ' . $this->getObjectClassName() . ' does not support chaining.');
			if ($chained === $instance) {
				$instance = $chained;
			}
		}
		$this->performTestBuild($instance);
		return $instance;
	}

	/**
	 * @test
	 */
	public function ifObjectIsFieldContainerItSupportsFetchingFields() {
		$instance = $this->createInstance();
		if (TRUE === $instance instanceof FieldContainerInterface) {
			$field = $instance->createField('Input', 'test');
			$instance->add($field);
			$fields = $instance->getFields();
			$this->assertNotEmpty($fields, 'The class ' . $this->getObjectClassName() . ' does not appear to support the required FieldContainerInterface implementation');
			$this->performTestBuild($instance);
		}
	}

	/**
	 * @test
	 */
	public function returnsNameInsteadOfEmptyLabelWhenFormsExtensionKeyAndLabelAreBothEmpty() {
		$instance = $this->createInstance();
		if (FALSE === $instance instanceof Form && TRUE === $instance instanceof FieldInterface) {
			/** @var Form $form */
			$form = $this->objectManager->get('FluidTYPO3\Flux\Form');
			$form->setExtensionName(NULL);
			$form->add($instance);
		}
		$instance->setName('test');
		$instance->setLabel(NULL);
		$this->performTestBuild($instance);

	}

	/**
	 * @test
	 */
	public function canCallAllGetterCounterpartsForChainableSetters() {
		$instance = $this->createInstance();
		foreach ($this->chainProperties as $propertyName => $propertValue) {
			ObjectAccess::getProperty($instance, $propertyName);
		}
		$this->performTestBuild($instance);
	}

	/**
	 * @param \FluidTYPO3\Flux\Form\FieldInterface
	 * @return array
	 */
	protected function performTestBuild($instance) {
		$configuration = $instance->build();
		$this->assertIsArray($configuration);
		return $configuration;
	}

	/**
	 * @test
	 */
	public function canBuildConfiguration() {
		$instance = $this->canChainAllChainableSetters();
		$this->performTestBuild($instance);
	}

	/**
	 * @test
	 */
	public function canCreateFromDefinition() {
		$properties = array($this->chainProperties);
		$class = $this->getObjectClassName();
		$type = implode('/', array_slice(explode('_', substr($class, 13)), 1));
		$properties['type'] = $type;
		$instance = call_user_func_array(array($class, 'create'), array($properties));
		$this->assertInstanceOf('FluidTYPO3\Flux\Form\FormInterface', $instance);
	}

	/**
	 * @test
	 */
	public function canUseShorthandLanguageLabel() {
		$className = $this->getObjectClassName();
		$instance = $this->getMock($className, array('getExtensionKey', 'getName', 'getRoot'));
		$instance->expects($this->never())->method('getExtensionKey');
		$instance->expects($this->once())->method('getRoot')->will($this->returnValue(NULL));
		$instance->expects($this->once())->method('getName')->will($this->returnValue('form'));
		$instance->setLabel('LLL:tt_content.tx_flux_container');
		$result = $instance->getLabel();
		$this->assertSame(LocalizationUtility::translate('tt_content.tx_flux_container', 'flux'), $result);
	}

	/**
	 * @disabledtest
	 * @dataProvider getLabelTranslationTestValues
	 * @param string $input
	 * @param string $extensionKey
	 * @param string $expectedOutput
	 */
	public function canTranslateLabelReference($input, $extensionKey, $expectedOutput) {
		$mock = $this->getMock($this->createInstanceClassName());
		$result = $this->callInaccessibleMethod($mock, 'translateLabelReference', $input, $extensionKey);
		$this->assertEquals($expectedOutput, $result);
	}

	/**
	 * @return array
	 */
	public function getLabelTranslationTestValues() {
		return array(
			array('label', NULL, 'label'),
			array('LLL:tt_content.tx_flux_container', 'flux', 'Content Container'),
			array('LLL:EXT:flux/Resources/Private/Language/locallang.xlf:tt_content.tx_flux_container', NULL, 'Content Container'),
			array('LLL:EXT:flux/Resources/Private/Language/locallang.xlf:tt_content.tx_flux_container', 'flux', 'Content Container'),
		);
	}

}
