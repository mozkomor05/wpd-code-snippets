<?php

use Psy\Configuration;
use Psy\Exception\ErrorException;
use Psy\Shell as PsyShell;


class Code_Snippets_Shell extends PsyShell {

	/**
	 * Return value indicator override
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const RETVAL = '';

	/**
	 * Shell Configuration
	 *
	 * @since 1.1.0
	 *
	 * @var \Psy\Configuration
	 */
	private $config;

	/**
	 * Create a new Psy Shell.
	 *
	 * @param \Psy\Configuration|null $config
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function __construct( Configuration $config = null ) {
		$this->config = $config;
		parent::__construct( $config );
	}

	/**
	 * Helper for throwing an ErrorException.
	 *
	 * @param int    $errno   Error type.
	 * @param string $errstr  Message.
	 * @param string $errfile Filename.
	 * @param int    $errline Line number.
	 *
	 * @return void
	 * @since 1.2.0 Using $errfile and remove line number increament
	 *
	 * @since 1.1.0
	 */
	public function handleError( $errno, $errstr, $errfile, $errline ) {
		$this->config->getOutput()->exception = new ErrorException( $errstr, 0, $errno, $errfile, $errline );
	}
}
