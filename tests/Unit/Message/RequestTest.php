<?php

namespace Rexlabs\HyperHttp\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Rexlabs\HyperHttp\Message\Request;

class RequestTest extends TestCase
{
    public function test_upgrade_request()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', '/');
        $upgradedRequest = Request::fromRequest($request);
        $this->assertInstanceOf(Request::class, $upgradedRequest);

        $request = new Request('GET', '/');
        $upgradedRequest = Request::fromRequest($request);
        $this->assertInstanceOf(Request::class, $upgradedRequest);
        $this->assertEquals($request, $upgradedRequest);
    }

    public function test_request_can_output_curl()
    {
        $request = new Request('GET', '/');
        $curlCmd = $request->getCurl();
        $this->assertEquals("curl '/'", $curlCmd);

        $request = new Request('GET', '/test', ['X-Some-Header' => 'value']);
        $curlCmd = $request->getCurl();
        $this->assertEquals("curl '/test' -H 'X-Some-Header: value'", $curlCmd);

        $request = new Request('POST', '/api/v1/people',
            [
                'Authorization' => 'Bearer FAKE',
            ],
            \GuzzleHttp\json_encode([
                'name'  => 'Bob',
                'email' => 'bob@example.com',
            ])
        );
        $curlCmd = $request->getCurl();
        $this->assertEquals(
            "curl '/api/v1/people' -H 'Authorization: Bearer FAKE' -X POST \\\n".
            "  -d '{\"name\":\"Bob\",\"email\":\"bob@example.com\"}'",
            $curlCmd);
    }

    public function test_is_form()
    {
        $request = (new Request('POST', '/api/v1/people', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]))->setOptions([
            'form_data' => [
                'name' => 'Walter',
                'age'  => 21,
            ],
        ]);
        $this->assertTrue($request->isForm());
        $this->assertTrue($request->isUrlEncodedForm());
    }

    public function test_can_get_data_from_form()
    {
        $request = (new Request('POST', '/api/v1/people', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]))->setOptions([
            'form_data' => [
                'name' => 'Walter',
                'age'  => 21,
            ],
        ]);
        $this->assertTrue($request->isForm());
        $this->assertTrue($request->isUrlEncodedForm());
    }

    public function test_is_multipart_form()
    {
        $request = (new Request('POST', '/api/v1/people', [
            'Content-Type' => 'multipart/form-data',
        ]))->setOptions([
            'multipart' => [
                [
                    'field_name' => 'name',
                    'contents'   => 'Walter',
                ],
                [
                    'field_name' => 'age',
                    'contents'   => 21,
                ],
            ],
        ]);
        $this->assertTrue($request->isForm());
        $this->assertTrue($request->isMultipartForm());
    }

    public function test_can_get_data_from_multipart_form()
    {
        $request = (new Request('POST', '/api/v1/people', [
            'Content-Type' => 'multipart/form-data',
        ]))->setOptions([
            'multipart' => [
                [
                    'field_name' => 'name',
                    'contents'   => 'Walter',
                ],
                [
                    'field_name' => 'age',
                    'contents'   => 21,
                ],
            ],
        ]);
        $this->assertEquals([
            [
                'field_name' => 'name',
                'contents'   => 'Walter',
            ],
            [
                'field_name' => 'age',
                'contents'   => 21,
            ],
        ], $request->getFormData());
    }

    public function test_can_get_data_from_invalid_form_throws_exception()
    {
        $request = new Request('POST', '/api/v1/people');
        $this->assertFalse($request->isForm());
        $this->expectException(\RuntimeException::class);
        $request->getFormData();
    }
}
