<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Psr\Http\Response;

/**
 * Description of ResponseTest
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class ResponseTest extends TestCase
{
    public function responseFactory($initText = 'prova')
    {
        return new Response(200, [], $initText);
    }

    public function testFactory()
    {
        $this->assertNotEmpty($this->responseFactory());
    }
}
