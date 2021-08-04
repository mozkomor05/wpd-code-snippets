<?php
use Psy\Output\PassthruPager;
use Psy\Output\ShellOutput as PsyShellOutput;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
class Code_Snippets_ShellOutput extends PsyShellOutput {

/**
 * Output message
 *
 * @since 1.0.0
 *
 * @var string
 */
public $outputMessage = null;

/**
 * Holds exceptions
 *
 * @since 1.1.0
 *
 * @var null|\Exception
 */
public $exception = null;

/**
 * Construct a ShellOutput instance.
 *
 * @since 1.0.0
 *
 * @param mixed                    $verbosity (default: self::VERBOSITY_NORMAL)
 * @param bool                     $decorated (default: null)
 * @param OutputFormatterInterface $formatter (default: null)
 * @param null|string|OutputPager  $pager     (default: null)
 *
 * @return void
 */
public function __construct( $verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null, $pager = null ) {
    ConsoleOutput::__construct( $verbosity, $decorated, $formatter );
    $this->pager = new PassthruPager( $this );
}

/**
 * Writes a message to the output.
 *
 * @since 1.0.0
 *
 * @param string $message A message to write to the output
 * @param bool   $newline Whether to add a newline or not
 *
 * @return void
 */
public function doWrite( $message, $newline ) {
    $this->outputMessage .= $message;
}
}