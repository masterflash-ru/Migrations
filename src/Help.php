<?php
/**
 */

namespace Mf\Migrations;

class Help
{
    /**
     * @var string
     */
    private $message = <<< EOH
Clear storage.

Usage:

clear-srorage [-h|--help]

--help|-h                    Print this usage message.
EOH;

    /**
     * Emit the help message.
     *
     * @param null|resource $stream Defaults to STDOUT
     */
    public function __invoke($stream = null)
    {
        if (! is_resource($stream)) {
            echo $this->message;
            return;
        }

        fwrite($stream, $this->message);
    }
}
