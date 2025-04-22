<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Psr\Http\Request;

/**
 * Description of ResponseTest
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class RequestTest extends TestCase
{
    public function requestFactory($initText = 'prova')
    {
        return new Request('GET', '', [], $initText);
    }

    public function testFactory()
    {
        $this->assertNotEmpty($this->requestFactory());
    }

    public function testRequestTargetDefault()
    {
        $request = $this->requestFactory();
        $this->assertEquals($request->getRequestTarget(), '/');
    }

    public function testRequestMethod()
    {
        $request = $this->requestFactory();
        $this->assertEquals($request->getMethod(), 'GET');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRequestValidateMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        $request = $this->requestFactory();
        $request->withMethod('GETERR');
    }

    public function testRequestWithMethod()
    {
        $request = $this->requestFactory();
        $newRequest = $request->withMethod('POST');
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('POST', $newRequest->getMethod());
    }
}
