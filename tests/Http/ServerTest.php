<?php

namespace Kirby\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kirby\Http\Server
 */
class ServerTest extends TestCase
{
    protected $_SERVER = null;

    public function setUp(): void
    {
        $this->_SERVER = $_SERVER;
        Server::$hosts = [];
        Server::$cli = null;
    }

    public function tearDown(): void
    {
        $_SERVER = $this->_SERVER;
        Server::$hosts = [];
        Server::$cli = null;
    }

    /**
     * @covers ::address
     */
    public function testAddress()
    {
        $_SERVER['SERVER_ADDR'] = $ip = '127.0.0.1';
        $this->assertSame($ip, Server::address());
    }

    /**
     * @covers ::address
     */
    public function testAddressOnCli()
    {
        $this->assertSame(null, Server::address());
    }

    /**
     * @covers ::cli
     */
    public function testCli()
    {
        $this->assertTrue(Server::cli());
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $_SERVER['TEST'] = 'foo';
        $this->assertSame('foo', Server::get('test'));
        $this->assertSame('foo', Server::get('TEST'));
    }

    /**
     * @covers ::get
     */
    public function testGetAll()
    {
        $this->assertSame($_SERVER, Server::get());
    }

    /**
     * @covers ::get
     */
    public function testGetFallback()
    {
        $this->assertSame('foo', Server::get('test', 'foo'));
    }

    /**
     * @covers ::host
     */
    public function testHostFromServerAddress()
    {
        $_SERVER['SERVER_ADDR'] = 'example.com';
        $this->assertSame('example.com', Server::host());
    }

    /**
     * @covers ::host
     */
    public function testHostFromServerName()
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $this->assertSame('example.com', Server::host());
    }

    /**
     * @covers ::host
     */
    public function testHostOnCli()
    {
        $this->assertSame(null, Server::host());
    }

    public function provideHttps()
    {
        return [
            [null, false],
            ['', false],
            ['0', false],
            [0, false],
            ['false', false],
            [false, false],
            ['off', false],
            [true, true],
            ['1', true],
            ['on', true],
            ['https', true],
        ];
    }

    /**
     * @dataProvider provideHttps
     * @covers ::https
     */
    public function testHttpsFromHeader($input, $expected)
    {
        $_SERVER['HTTPS'] = $input;
        $this->assertSame($expected, Server::https());
    }

    /**
     * @covers ::https
     */
    public function testHttpsFromForwardedPort()
    {
        Server::$hosts = Server::HOST_FROM_HEADER;

        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'example.com';
        $_SERVER['HTTP_X_FORWARDED_SSL'] = 'on';
        $this->assertTrue(Server::https());

        // HTTP_X_FORWARDED_PROTO = https
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertTrue(Server::https());
    }

    /**
     * @covers ::https
     */
    public function testHttpsFromForwardedProto()
    {
        $_SERVER['HTTP_X_FORWARDED_HOST']  = 'example.com';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        Server::$hosts = Server::HOST_FROM_HEADER;

        $this->assertTrue(Server::https());
    }

    /**
     * @covers ::isBehindProxy
     */
    public function testIsBehindProxy()
    {
        $this->assertFalse(Server::isBehindProxy());
    }

    /**
     * @covers ::port
     */
    public function testPortFromHost()
    {
        // HTTP_HOST
        $_SERVER['HTTP_HOST'] = 'localhost:8888';

        Server::$hosts = Server::HOST_FROM_HEADER;
        $this->assertSame(8888, Server::port());
    }

    /**
     * @covers ::port
     */
    public function testPortFromProxyHost()
    {
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'example.com:8888';

        Server::$hosts = Server::HOST_FROM_HEADER;
        $this->assertSame(8888, Server::port());
    }

    /**
     * @covers ::port
     */
    public function testPortFromProxyPort()
    {
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'example.com';
        $_SERVER['HTTP_X_FORWARDED_PORT'] = 8888;

        Server::$hosts = Server::HOST_FROM_HEADER;
        $this->assertSame(8888, Server::port());
    }

    /**
     * @covers ::port
     */
    public function testPortFromProxyProto()
    {
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'example.com';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        Server::$hosts = Server::HOST_FROM_HEADER;
        $this->assertSame(443, Server::port());
    }

    /**
     * @covers ::port
     */
    public function testPortFromServer()
    {
        // SERVER_PORT
        $_SERVER['SERVER_PORT'] = 777;
        $this->assertSame(777, Server::port());
    }

    /**
     * @covers ::port
     */
    public function testPortOnCli()
    {
        $this->assertSame(null, Server::port());
    }

    public function provideRequestUri(): array
    {
        return [
            [
                null,
                [
                    'path'  => null,
                    'query' => null
                ]
            ],
            [
                '/',
                [
                    'path'  => '/',
                    'query' => null
                ]
            ],
            [
                '/foo/bar',
                [
                    'path'  => '/foo/bar',
                    'query' => null
                ]
            ],
            [
                '/foo/bar?foo=bar',
                [
                    'path'  => '/foo/bar',
                    'query' => 'foo=bar'
                ]
            ],
            [
                '/foo/bar/page:2?foo=bar',
                [
                    'path'  => '/foo/bar/page:2',
                    'query' => 'foo=bar'
                ]
            ],
            [
                '/foo/bar/page;2?foo=bar',
                [
                    'path'  => '/foo/bar/page;2',
                    'query' => 'foo=bar'
                ]
            ],
            [
                'index.php?foo=bar',
                [
                    'path'  => null,
                    'query' => 'foo=bar'
                ]
            ],
            [
                'https://getkirby.com/foo/bar?foo=bar',
                [
                    'path'  => '/foo/bar',
                    'query' => 'foo=bar'
                ]
            ]
        ];
    }

    /**
     * @dataProvider provideRequestUri
     * @covers ::requestUri
     */
    public function testRequestUri($input, $expected)
    {
        $_SERVER['REQUEST_URI'] = $input;
        $this->assertSame($expected, Server::requestUri());
    }

    public function provideSanitize()
    {
        return [
            // needs no sanitizing
            [
                'HTTP_HOST',
                'getkirby.com',
                'getkirby.com'
            ],
            [
                'HTTP_HOST',
                'öxample.com',
                'öxample.com'
            ],
            [
                'HTTP_HOST',
                'example-with-dashes.com',
                'example-with-dashes.com'
            ],
            [
                'SERVER_PORT',
                9999,
                9999
            ],

            // needs sanitizing
            [
                'HTTP_HOST',
                '.somehost.com',
                'somehost.com'
            ],
            [
                'HTTP_HOST',
                '-somehost.com',
                'somehost.com'
            ],
            [
                'HTTP_HOST',
                '<script>foo()</script>',
                'foo'
            ],
            [
                'HTTP_X_FORWARDED_HOST',
                '<script>foo()</script>',
                'foo'
            ],
            [
                'HTTP_X_FORWARDED_HOST',
                '../some-fake-host',
                'some-fake-host'
            ],
            [
                'HTTP_X_FORWARDED_HOST',
                '../',
                null
            ],
            [
                'SERVER_PORT',
                'abc9999',
                9999
            ],
            [
                'HTTP_X_FORWARDED_PORT',
                'abc9999',
                9999
            ]
        ];
    }

    /**
     * @dataProvider provideSanitize
     */
    public function testSanitize($key, $value, $expected)
    {
        $this->assertSame($expected, Server::sanitize($key, $value));
    }

    public function provideScriptPath()
    {
        return [
            [
                null,
                ''
            ],
            [
                '',
                ''
            ],
            [
                '/index.php',
                ''
            ],
            [
                '/subfolder/index.php',
                'subfolder'
            ],
            [
                '/subfolder/test.php',
                'subfolder'
            ],
            [
                '\subfolder\subsubfolder\index.php',
                'subfolder/subsubfolder'
            ],
        ];
    }

    /**
     * @dataProvider provideScriptPath
     * @covers ::scriptPath
     */
    public function testScriptPath($scriptName, $expected)
    {
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        // switch off cli detection to simulate
        // script path detection on the server
        Server::$cli = false;
        $this->assertSame($expected, Server::scriptPath());
    }

    /**
     * @covers ::scriptPath
     */
    public function testScriptPathOnCli()
    {
        $this->assertSame('', Server::scriptPath());
    }
}
