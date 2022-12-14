<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Psr7\Response;

/**
 * Description of ResponseTest
 *
 * @author pietro
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
