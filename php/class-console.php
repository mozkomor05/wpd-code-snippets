<?php

use \Psy\Configuration;

class Code_Snippets_Console
{

    public function __construct()
    {
        add_action('wp_ajax_wpd_evaluate_code', array($this, 'evaluate_wpd_console'));
    }


    /**
     * Generates output array with both - original html and encoded text
     *
     * @param string $msg Output message.
     *
     * @return array
     */
    protected function output_array($msg)
    {
        return array(
            'html' => $msg,
            'text' => htmlspecialchars($msg),
        );
    }

    /**
     * Evaluates PHP code
     *
     * @param string $input PHP code input.
     */
    public function evaluate_code($input, $strict = false)
    {
        $config = new Configuration(array(
            'configDir' => WP_CONTENT_DIR,
        ));

        $output = new Code_Snippets_ShellOutput(Code_Snippets_ShellOutput::VERBOSITY_DEBUG, true);

        $config->setOutput($output);
        $config->setColorMode(Configuration::COLOR_MODE_AUTO);
        $config->setYolo(true);

        $psysh = new Code_Snippets_Shell($config);
        $psysh->setOutput($output);

        try {
            $psysh->addCode($input);
            $timer = microtime(true);

            extract($psysh->getScopeVariablesDiff(get_defined_vars()));
            ob_start(array($psysh, 'writeStdout'), 1);
            set_error_handler(array($psysh, 'handleError'));

            if (!$strict) {
                $code = $psysh->onExecute($psysh->flushCode() ?: $input);
            } else {
                $code = $input;
            }

            $_ = eval($code);

            restore_error_handler();

            $psysh->setScopeVariables(get_defined_vars());
            $psysh->writeReturnValue($_);

            ob_end_flush();

            if ($output->exception) {
                throw $output->exception;
            }

            $execution_time = microtime(true) - $timer;

            return (array(
                'output' => $this->output_array($output->outputMessage),
                'execution_time' => number_format($execution_time, 3, '.', ''),
            ));
        } catch (Throwable $e) {
            ob_end_flush();

            return array(
                'output' => $this->output_array($output->outputMessage),
                'error' => array(
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                ),
            );
        }
    }

    /**
     * AJAX - Evaluates console
     */
    public function evaluate_wpd_console()
    {
        $input = stripslashes($_POST['input']);
        $macros = $_POST['macros'] ?? null;

        if (!empty($macros)) {
            $input = process_snippet_macros($input, $macros);
        }

        $output = $this->evaluate_code($input);

        if (!isset($output['error'])) {
            wp_send_json_success($output);
        } else {
            $output['status'] = 422;
            wp_send_json_error($output);
        }
    }
}
