<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Psr7\Http\Stream;

/**
 * Description of String
 *
 * @author Pietro Celeste <pietro.celeste@gmail.com>
 */
class StreamString extends Base
{
    public function __construct(string $stream = '', $operations = 'r+')
    {
        parent::__construct(fopen('php://memory', $operations));
        $this->write($stream);
        $this->rewind();
    }
}
