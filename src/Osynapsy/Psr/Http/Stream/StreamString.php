<?php
namespace Osynapsy\Psr\Http\Stream;

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
