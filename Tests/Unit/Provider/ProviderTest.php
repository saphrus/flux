<?php
namespace FluidTYPO3\Flux\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Records;

/**
 * @package Flux
 */
class ProviderTest extends AbstractProviderTest {

	/**
	 * @var array
	 */
	protected $definition = array(
		'name' => 'test',
		'label' => 'Test provider',
		'tableName' => 'tt_content',
		'fieldName' => 'pi_flexform',
		'form' => array(
			'sheets' => array(
				'foo' => array(
					'fields' => array(
						'test' => array(
							'type' => 'Input',
						)
					)
				),
				'bar' => array(
					'fields' => array(
						'test2' => array(
							'type' => 'Input',
						)
					)
				),
			),
			'fields' => array(
				'test3' => array(
					'type' => 'Input',
				)
			),
		),
		'grid' => array(
			'rows' => array(
				'foo' => array(
					'columns' => array(
						'bar' => array(
							'areas' => array(

							)
						)
					)
				)
			)
		)
	);

	/**
	 * @test
	 */
	public function canGetName() {
		$provider = $this->getConfigurationProviderInstance();
		$provider->loadSettings($this->definition);
		$this->assertSame($provider->getName(), $this->definition['name']);
	}

	/**
	 * @test
	 */
	public function canCreateInstanceWithListType() {
		$definition = $this->definition;
		$definition['listType'] = 'felogin_pi1';
		$provider = $this->getConfigurationProviderInstance();
		$provider->loadSettings($definition);
		$this->assertSame($provider->getName(), $definition['name']);
		$this->assertSame($provider->getListType(), $definition['listType']);
	}

	/**
	 * @test
	 */
	public function canReturnExtensionKey() {
		Core::registerConfigurationProvider('FluidTYPO3\\Flux\\Provider\\ContentProvider');
		$record = Records::$contentRecordWithoutParentAndWithoutChildren;
		$service = $this->createFluxServiceInstance();
		$provider = $service->resolvePrimaryConfigurationProvider('tt_content', 'pi_flexform', array(), 'flux');
		$this->assertInstanceOf('FluidTYPO3\Flux\Provider\ProviderInterface', $provider);
		$extensionKey = $provider->getExtensionKey($record);
		$this->assertNotEmpty($extensionKey);
		$this->assertRegExp('/[a-z_]+/', $extensionKey);
		Core::unregisterConfigurationProvider('FluidTYPO3\\Flux\\Provider\\ContentProvider');
	}

	/**
	 * @test
	 */
	public function canReturnPathSetByRecordWithoutParentAndWithoutChildren() {
		Core::registerConfigurationProvider('FluidTYPO3\\Flux\\Provider\\ContentProvider');
		$row = Records::$contentRecordWithoutParentAndWithoutChildren;
		$service = $this->createFluxServiceInstance();
		$provider = $service->resolvePrimaryConfigurationProvider('tt_content', 'pi_flexform', $row);
		$this->assertInstanceOf('FluidTYPO3\Flux\Provider\ProviderInterface', $provider);
		$paths = $provider->getTemplatePaths($row);
		$this->assertIsArray($paths);
		Core::unregisterConfigurationProvider('FluidTYPO3\\Flux\\Provider\\ContentProvider');
	}

	/**
	 * @test
	 */
	public function canCreateFormFromDefinitionWithAllSupportedNodes() {
		/** @var ProviderInterface $instance */
		$provider = $this->getConfigurationProviderInstance();
		$record = $this->getBasicRecord();
		$provider->loadSettings($this->definition);
		$form = $provider->getForm($record);
		$this->assertInstanceOf('FluidTYPO3\Flux\Form', $form);
	}

	/**
	 * @test
	 */
	public function canCreateGridFromDefinitionWithAllSupportedNodes() {
		/** @var ProviderInterface $instance */
		$provider = $this->getConfigurationProviderInstance();
		$record = $this->getBasicRecord();
		$provider->loadSettings($this->definition);
		$grid = $provider->getGrid($record);
		$this->assertInstanceOf('FluidTYPO3\Flux\Form\Container\Grid', $grid);
	}
	/**
	 * @test
	 */
	public function getParentFieldValueLoadsRecordFromDatabaseIfRecordLacksParentFieldValue() {
		$row = Records::$contentRecordWithoutParentAndWithoutChildren;
		$row['uid'] = 2;
		$rowWithPid = $row;
		$rowWithPid['pid'] = 1;
		$className = str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4));
		$instance = $this->getMock($className, array('getParentFieldName', 'getTableName', 'loadRecordFromDatabase'));
		$instance->expects($this->once())->method('loadRecordFromDatabase')->with($row['uid'])->will($this->returnValue($rowWithPid));
		$instance->expects($this->once())->method('getParentFieldName')->with($row)->will($this->returnValue('pid'));
		$result = $this->callInaccessibleMethod($instance, 'getParentFieldValue', $row);
		$this->assertEquals($rowWithPid['pid'], $result);
	}

}
