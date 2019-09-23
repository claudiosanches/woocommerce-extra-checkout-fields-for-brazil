<?php
/**
 * Brazilian Market on WooCommerce
 *
 * Inspired in https://github.com/mwpd/basic-scaffold
 *
 * @package ClaudioSanches\BrazilianMarket
 */

namespace ClaudioSanches\BrazilianMarket\Infrastructure;

use ClaudioSanches\BrazilianMarket\Exception\InvalidService;
use ClaudioSanches\BrazilianMarket\Infrastructure\ServiceContainer\LazilyInstantiatedService;

/**
 * This abstract base plugin provides all the boilerplate code for working with
 * the dependency injector and the service container.
 */
abstract class ServiceBasedPlugin implements Plugin {

	// Main filters to control the flow of the plugin from outside code.
	public const SERVICES_FILTER         = 'services';
	public const BINDINGS_FILTER         = 'bindings';
	public const ARGUMENTS_FILTER        = 'arguments';
	public const SHARED_INSTANCES_FILTER = 'shared_instances';
	public const DELEGATIONS_FILTER      = 'delegations';

	// Service identifier for the injector.
	public const INJECTOR_ID = 'injector';

	// WordPress action to trigger the service registration on.
	protected const REGISTRATION_ACTION = 'plugins_loaded';

	// Prefixes to use.
	protected const HOOK_PREFIX    = '';
	protected const SERVICE_PREFIX = '';

	/**
	 * If should enable filters.
	 *
	 * @var bool
	 */
	protected $enable_filters;

	/**
	 * Injector instance.
	 *
	 * @var Injector
	 */
	protected $injector;

	/**
	 * ServiceContainer instance.
	 *
	 * @var ServiceContainer
	 */
	protected $service_container;

	/**
	 * Instantiate a Plugin object.
	 *
	 * @param bool                  $enable_filters    Optional. Whether to
	 *                                                 enable filtering of the
	 *                                                 injector configuration.
	 * @param Injector|null         $injector          Optional. Injector
	 *                                                 instance to use.
	 * @param ServiceContainer|null $service_container Optional. Service
	 *                                                 container instance to
	 *                                                 use.
	 */
	public function __construct(
		$enable_filters = true,
		$injector = null,
		$service_container = null
	) {
		/*
		 * We use what is commonly referred to as a "poka-yoke" here.
		 *
		 * We need an injector and a container. We make them injectable so that
		 * we can easily provide overrides for testing, but we also make them
		 * optional and provide default implementations for easy regular usage.
		 */

		$this->enable_filters    = $enable_filters;
		$this->injector          = $injector ? $injector : new Injector\SimpleInjector();
		$this->injector          = $this->configure_injector( $this->injector );
		$this->service_container = $service_container ? $service_container : new ServiceContainer\SimpleServiceContainer();
	}

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public function activate() {
		$this->register_services();

		foreach ( $this->service_container as $service ) {
			if ( $service instanceof Activateable ) {
				$service->activate();
			}
		}

		\flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public function deactivate() {
		$this->register_services();

		foreach ( $this->service_container as $service ) {
			if ( $service instanceof Deactivateable ) {
				$service->deactivate();
			}
		}

		\flush_rewrite_rules();
	}

	/**
	 * Register the plugin with the WordPress system.
	 *
	 * @throws InvalidService If a service is not valid.
	 * @return void
	 */
	public function register() {
		\add_action(
			static::REGISTRATION_ACTION,
			[ $this, 'register_services' ]
		);
	}

	/**
	 * Register the individual services of this plugin.
	 *
	 * @throws InvalidService If a service is not valid.
	 * @return void
	 */
	public function register_services() {
		// Bail early so we don't instantiate services twice.
		if ( count( $this->service_container ) > 0 ) {
			return;
		}

		// Add the injector as the very first service.
		$this->service_container->put(
			static::SERVICE_PREFIX . static::INJECTOR_ID,
			$this->injector
		);

		$services = $this->get_service_classes();

		if ( $this->enable_filters ) {
			/**
			 * Filter the default services that make up this plugin.
			 *
			 * This can be used to add services to the service container for
			 * this plugin.
			 *
			 * @param array<string> $services Associative array of identifier =>
			 *                                class mappings. The provided
			 *                                classes need to implement the
			 *                                Service interface.
			 */
			$services = \apply_filters(
				static::HOOK_PREFIX . static::SERVICES_FILTER,
				$services
			);
		}

		foreach ( $services as $id => $class ) {
			$id    = $this->maybe_resolve( $id );
			$class = $this->maybe_resolve( $class );

			// Allow the services to delay their registration.
			if ( is_a( $class, Delayed::class, true ) ) {
				$registration_action = $class::get_registration_action();

				if ( did_action( $registration_action ) ) {
					$this->register_service( $id, $class );

					continue;
				}

				\add_action(
					$class::get_registration_action(),
					function () use ( $id, $class ) {
						$this->register_service( $id, $class );
					}
				);

				continue;
			}

			$this->register_service( $id, $class );
		}
	}

	/**
	 * Register a single service.
	 *
	 * @param string $id Service ID.
	 * @param string $class Service class.
	 */
	protected function register_service( $id, $class ) {
		// Only instantiate services that are actually needed.
		if ( is_a( $class, Conditional::class, true ) && ! $class::is_needed() ) {
			return;
		}

		$service = $this->instantiate_service( $class );

		$this->service_container->put( $id, $service );

		if ( $service instanceof Registerable ) {
			$service->register();
		}
	}

	/**
	 * Get the service container that contains the services that make up the
	 * plugin.
	 *
	 * @return ServiceContainer Service container of the plugin.
	 */
	public function get_container() {
		return $this->service_container;
	}

	/**
	 * Instantiate a single service.
	 *
	 * @throws InvalidService If the service could not be properly instantiated.
	 * @param string $class Service class to instantiate.
	 * @return Service Instantiated service.
	 */
	protected function instantiate_service( $class ) {
		/*
		 * If the service is not registerable, we default to lazily instantiated
		 * services here for some basic optimization.
		 *
		 * The services will be properly instantiated once they are retrieved
		 * from the service container.
		 */
		if ( ! is_a( $class, Registerable::class, true ) ) {
			return new LazilyInstantiatedService(
				function () use ( $class ) {
					return $this->injector->make( $class );
				}
			);
		}

		// The service needs to be registered, so instantiate right away.
		$service = $this->injector->make( $class );

		if ( ! $service instanceof Service ) {
			throw InvalidService::from_service( $service );
		}

		return $service;
	}

	/**
	 * Configure the provided injector.
	 *
	 * This method defines the mappings that the injector knows about, and the
	 * logic it requires to make more complex instantiations work.
	 *
	 * For more complex plugins, this should be extracted into a separate
	 * object
	 * or into configuration files.
	 *
	 * @param Injector $injector Injector instance to configure.
	 * @return Injector Configured injector instance.
	 */
	protected function configure_injector( Injector $injector ) {
		$bindings         = $this->get_bindings();
		$shared_instances = $this->get_shared_instances();
		$arguments        = $this->get_arguments();
		$delegations      = $this->get_delegations();

		if ( $this->enable_filters ) {
			/**
			 * Filter the default bindings that are provided by the plugin.
			 *
			 * This can be used to swap implementations out for alternatives.
			 *
			 * @param array<string> $bindings Associative array of interface =>
			 *                                implementation bindings. Both
			 *                                should be FQCNs.
			 */
			$bindings = (array) \apply_filters(
				static::HOOK_PREFIX . static::BINDINGS_FILTER,
				$bindings
			);

			/**
			 * Filter the default argument bindings that are provided by the
			 * plugin.
			 *
			 * This can be used to override scalar values.
			 *
			 * @param array<array> $arguments Associative array of class =>
			 *                                arguments mappings. The arguments
			 *                                array maps argument names to
			 *                                values.
			 */
			$arguments = (array) \apply_filters(
				static::HOOK_PREFIX . static::ARGUMENTS_FILTER,
				$arguments
			);

			/**
			 * Filter the instances that are shared by default by the plugin.
			 *
			 * This can be used to turn objects that were added externally into
			 * shared instances.
			 *
			 * @param array<string> $shared_instances Array of FQCNs to turn
			 *                                        into shared objects.
			 */
			$shared_instances = (array) \apply_filters(
				static::HOOK_PREFIX . static::SHARED_INSTANCES_FILTER,
				$shared_instances
			);

			/**
			 * Filter the instances that are shared by default by the plugin.
			 *
			 * This can be used to turn objects that were added externally into
			 * shared instances.
			 *
			 * @param array<string> $delegations Associative array of class =>
			 *                                   callable mappings.
			 */
			$delegations = (array) \apply_filters(
				static::HOOK_PREFIX . static::DELEGATIONS_FILTER,
				$delegations
			);
		}

		foreach ( $bindings as $from => $to ) {
			$from = $this->maybe_resolve( $from );
			$to   = $this->maybe_resolve( $to );

			$injector = $injector->bind( $from, $to );
		}

		foreach ( $arguments as $class => $argument_map ) {
			$class = $this->maybe_resolve( $class );

			foreach ( $argument_map as $name => $value ) {
				// We don't try to resolve the $value here, as we might want to
				// pass a callable as-is.
				$name = $this->maybe_resolve( $name );

				$injector = $injector->bind_argument( $class, $name, $value );
			}
		}

		foreach ( $shared_instances as $shared_instance ) {
			$shared_instance = $this->maybe_resolve( $shared_instance );

			$injector = $injector->share( $shared_instance );
		}

		foreach ( $delegations as $class => $callable ) {
			// We don't try to resolve the $callable here, as we want to pass it
			// on as-is.
			$class = $this->maybe_resolve( $class );

			$injector = $injector->delegate( $class, $callable );
		}

		return $injector;
	}

	/**
	 * Get the list of services to register.
	 *
	 * @return array<string> Associative array of identifiers mapped to fully
	 *                       qualified class names.
	 */
	protected function get_service_classes() {
		return [];
	}

	/**
	 * Get the bindings for the dependency injector.
	 *
	 * The bindings let you map interfaces (or classes) to the classes that
	 * should be used to implement them.
	 *
	 * @return array<string> Associative array of fully qualified class names.
	 */
	protected function get_bindings() {
		return [];
	}

	/**
	 * Get the argument bindings for the dependency injector.
	 *
	 * The argument bindings let you map specific argument values for specific
	 * classes.
	 *
	 * @return array<array> Associative array of arrays mapping argument names
	 *                      to argument values.
	 */
	protected function get_arguments() {
		return [];
	}

	/**
	 * Get the shared instances for the dependency injector.
	 *
	 * These classes will only be instantiated once by the injector and then
	 * reused on subsequent requests.
	 *
	 * This effectively turns them into singletons, without any of the
	 * drawbacks of the actual Singleton anti-pattern.
	 *
	 * @return array<string> Array of fully qualified class names.
	 */
	protected function get_shared_instances() {
		return [];
	}

	/**
	 * Get the delegations for the dependency injector.
	 *
	 * These are basically factories to provide custom instantiation logic for
	 * classes.
	 *
	 * @return array<callable> Associative array of callables.
	 */
	protected function get_delegations() {
		return [];
	}

	/**
	 * Maybe resolve a value that is a callable instead of a scalar.
	 *
	 * Values that are passed through this method can optionally be provided as
	 * callables instead of direct values and will be evaluated when needed.
	 *
	 * @param mixed $value Value to potentially resolve.
	 * @return mixed Resolved or unchanged value.
	 */
	protected function maybe_resolve( $value ) {
		if ( is_callable( $value ) ) {
			$value = $value( $this->injector, $this->service_container );
		}

		return $value;
	}
}
