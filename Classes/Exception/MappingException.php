<?php

declare(strict_types=1);

namespace Porthd\Timer\Exception;

use Exception;
use Throwable;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porth <info@mobger.de>
 *
 *  All rights reserved
 *
 *  This script is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * An exception thrown if the return value type of a signal is not the expected one.
 */
class MappingException extends Exception
{
    /**
     * add some defaulttext to every erxception in this extension
     *
     * @param string $message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        string $message = "",
        int    $code = 0,
        $previous = null
    )
    {
        $message .= ' Make a Screenshot, write a short report of your last actions and inform the webmaster via email.';
        parent::__construct($message, $code, $previous);
    }

}
