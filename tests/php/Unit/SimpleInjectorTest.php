<?php
/**
 * Brazilian Market on WooCommerce
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Tests\Unit;

use ClaudioSanches\BrazilianMarket\Exception\FailedToMakeInstance;
use ClaudioSanches\BrazilianMarket\Infrastructure\Injector;
use ClaudioSanches\BrazilianMarket\Infrastructure\Injector\SimpleInjector;
use ClaudioSanches\BrazilianMarket\Tests\Fixture;
use stdClass;

/**
 * Simple Injector Test.
 */
final class SimpleInjectorTest extends TestCase {

	/**
	 * Test if it can be initialized.
	 *
	 * @return void
	 */
	public function test_it_can_be_initialized() {
		$injector = new SimpleInjector();

		$this->assertInstanceOf( SimpleInjector::class, $injector );
	}

	/**
	 * Test if it implements the interface.
	 *
	 * @return void
	 */
	public function test_it_implements_the_interface() {
		$injector = new SimpleInjector();

		$this->assertInstanceOf( Injector::class, $injector );
	}

	/**
	 * Test if it can instantiate a concrete class.
	 *
	 * @return void
	 */
	public function test_it_can_instantiate_a_concrete_class() {
		$object = ( new SimpleInjector() )
			->make( Fixture\DummyClass::class );

		$this->assertInstanceOf( Fixture\DummyClass::class, $object );
	}

	/**
	 * Test if it can autowire a class with a dependency.
	 *
	 * @return void
	 */
	public function test_it_can_autowire_a_class_with_a_dependency() {
		$object = ( new SimpleInjector() )
			->make( Fixture\DummyClassWithDependency::class );

		$this->assertInstanceOf( Fixture\DummyClassWithDependency::class, $object );
		$this->assertInstanceOf( Fixture\DummyClass::class, $object->get_dummy() );
	}

	/**
	 * Test if it can instantiate a bound interface.
	 *
	 * @return void
	 */
	public function test_it_can_instantiate_a_bound_interface() {
		$injector = ( new SimpleInjector() )
			->bind(
				Fixture\DummyInterface::class,
				Fixture\DummyClassWithDependency::class
			);
		$object   = $injector->make( Fixture\DummyInterface::class );

		$this->assertInstanceOf( Fixture\DummyInterface::class, $object );
		$this->assertInstanceOf( Fixture\DummyClassWithDependency::class, $object );
		$this->assertInstanceOf( Fixture\DummyClass::class, $object->get_dummy() );
	}

	/**
	 * Test if it returns separated instances by default.
	 *
	 * @return void
	 */
	public function test_it_returns_separate_instances_by_default() {
		$injector = new SimpleInjector();
		$object_a = $injector->make( Fixture\DummyClass::class );
		$object_b = $injector->make( Fixture\DummyClass::class );

		$this->assertNotSame( $object_a, $object_b );
	}

	/**
	 * Test if it returns same instances if shared.
	 *
	 * @return void
	 */
	public function test_it_returns_same_instances_if_shared() {
		$injector = ( new SimpleInjector() )
			->share( Fixture\DummyClass::class );
		$object_a = $injector->make( Fixture\DummyClass::class );
		$object_b = $injector->make( Fixture\DummyClass::class );

		$this->assertSame( $object_a, $object_b );
	}

	/**
	 * Test if it can instantiate a class with named arguments.
	 *
	 * @return void
	 */
	public function test_it_can_instantiate_a_class_with_named_arguments() {
		$object = ( new SimpleInjector() )
			->make(
				Fixture\DummyClassWithNamedArguments::class,
				[
					'argument_a' => 42,
					'argument_b' => 'Mr Alderson',
				]
			);

		$this->assertInstanceOf( Fixture\DummyClassWithNamedArguments::class, $object );
		$this->assertEquals( 42, $object->get_argument_a() );
		$this->assertEquals( 'Mr Alderson', $object->get_argument_b() );
	}

	/**
	 * Test if it allows for skipping named arguments with default values.
	 *
	 * @return void
	 */
	public function test_it_allows_for_skipping_named_arguments_with_default_values() {
		$object = ( new SimpleInjector() )
			->make(
				Fixture\DummyClassWithNamedArguments::class,
				[ 'argument_a' => 42 ]
			);

		$this->assertInstanceOf( Fixture\DummyClassWithNamedArguments::class, $object );
		$this->assertEquals( 42, $object->get_argument_a() );
		$this->assertEquals( 'Mr Meeseeks', $object->get_argument_b() );
	}

	/**
	 * Test if it throws if a required named arguments is missing.
	 *
	 * @return void
	 */
	public function test_it_throws_if_a_required_named_arguments_is_missing() {
		$this->expectException( FailedToMakeInstance::class );

		( new SimpleInjector() )
			->make( Fixture\DummyClassWithNamedArguments::class );
	}

	/**
	 * Test if it throws if a circular reference is detected.
	 *
	 * @return void
	 */
	public function test_it_throws_if_a_circular_reference_is_detected() {
		$this->expectException( FailedToMakeInstance::class );
		$this->expectExceptionCode( FailedToMakeInstance::CIRCULAR_REFERENCE );

		( new SimpleInjector() )
			->bind(
				Fixture\DummyClass::class,
				Fixture\DummyClassWithDependency::class
			)
			->make( Fixture\DummyClassWithDependency::class );
	}

	/**
	 * Test if it can delegate instantiation.
	 *
	 * @return void
	 */
	public function test_it_can_delegate_instantiation() {
		$injector = ( new SimpleInjector() )
			->delegate(
				Fixture\DummyInterface::class,
				function ( string $class ) {
					$object             = new stdClass();
					$object->class_name = $class;
					return $object;
				}
			);
		$object   = $injector->make( Fixture\DummyInterface::class );

		$this->assertInstanceOf( stdClass::class, $object );
		$this->assertObjectHasAttribute( 'class_name', $object );
		$this->assertEquals( Fixture\DummyInterface::class, $object->class_name );
	}

	/**
	 * Test if delegation works across resolution.
	 *
	 * @return void
	 */
	public function test_delegation_works_across_resolution() {
		$injector = ( new SimpleInjector() )
			->bind(
				Fixture\DummyInterface::class,
				Fixture\DummyClassWithDependency::class
			)
			->delegate(
				Fixture\DummyClassWithDependency::class,
				function ( string $class ) {
					$object             = new stdClass();
					$object->class_name = $class;
					return $object;
				}
			);
		$object   = $injector->make( Fixture\DummyInterface::class );

		$this->assertInstanceOf( stdClass::class, $object );
		$this->assertObjectHasAttribute( 'class_name', $object );
		$this->assertEquals( Fixture\DummyClassWithDependency::class, $object->class_name );
	}

	/**
	 * Test if arguments can be bound.
	 *
	 * @return void
	 */
	public function test_arguments_can_be_bound() {
		$object = ( new SimpleInjector() )
			->bind_argument(
				Fixture\DummyClassWithNamedArguments::class,
				'argument_a',
				42
			)
			->bind_argument(
				SimpleInjector::GLOBAL_ARGUMENTS,
				'argument_b',
				'Mr Alderson'
			)
			->make( Fixture\DummyClassWithNamedArguments::class );

		$this->assertInstanceOf( Fixture\DummyClassWithNamedArguments::class, $object );
		$this->assertEquals( 42, $object->get_argument_a() );
		$this->assertEquals( 'Mr Alderson', $object->get_argument_b() );
	}
}
