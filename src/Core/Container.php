<?php

/**
 * PSR-11 compatible service container.
 *
 * @package BePlusSmartSearch
 * @subpackage Core
 */

namespace BePlusSmartSearch\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight service container.
 */
class Container {

	/**
	 * Registered service definitions.
	 *
	 * @var array<string, callable|object>
	 */
	private $definitions = array();

	/**
	 * Resolved singleton instances.
	 *
	 * @var array<string, object>
	 */
	private $instances = array();

	/**
	 * Register a service definition.
	 *
	 * @param string          $id    Service identifier.
	 * @param callable|object $value Factory or instance.
	 *
	 * @return void
	 */
	public function set( string $id, $value ): void {
		$this->definitions[ $id ] = $value;
		unset( $this->instances[ $id ] );
	}

	/**
	 * Retrieve a service instance.
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $id Service identifier.
	 *
	 * @return T
	 */
	public function get( string $id ): object {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->definitions[ $id ] ) ) {
			$instance               = new $id( $this );
			$this->instances[ $id ] = $instance;
			return $instance;
		}

		$definition = $this->definitions[ $id ];

		if ( is_callable( $definition ) ) {
			$instance = $definition( $this );
		} else {
			$instance = $definition;
		}

		$this->instances[ $id ] = $instance;
		return $instance;
	}

	/**
	 * Check if a service is registered.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->definitions[ $id ] );
	}

	/**
	 * Register multiple services.
	 *
	 * @param array<string, callable|object> $services Services map.
	 *
	 * @return void
	 */
	public function register( array $services ): void {
		foreach ( $services as $id => $definition ) {
			$this->set( $id, $definition );
		}
	}
}
