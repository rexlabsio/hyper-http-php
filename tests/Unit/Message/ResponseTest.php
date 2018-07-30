<?php

namespace Rexlabs\HyperHttp\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Rexlabs\HyperHttp\Message\Response;

class ResponseTest extends TestCase
{
    // isJson() // both content types
    public function test_is_json()
    {
        $response = new Response(200, [
            'Content-Type' => 'text/html',
        ], '<html></html>');
        $this->assertFalse($response->isJson());

        $response = new Response(200, [
            'Content-Type' => 'text/plain',
        ], 'Hello world!');
        $this->assertFalse($response->isJson());

        // Json API
        $response = new Response(200, [
            'Content-Type' => 'application/vnd.api+json',
        ], \GuzzleHttp\json_encode('Hello world!'));
        $this->assertTrue($response->isJson());

        // application/calendar+json RFC7265
        $response = new Response(200, [
            'Content-Type' => 'application/calendar+json',
        ], \GuzzleHttp\json_encode('Hello world!'));
        $this->assertTrue($response->isJson());

        // collection+json
        $response = new Response(200, [
            'Content-Type' => 'application/vnd.collection+json',
        ], \GuzzleHttp\json_encode(['Hello world!']));
        $this->assertTrue($response->isJson());

        // application/json; charset=UTF-8
        $response = new Response(200, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ], \GuzzleHttp\json_encode(['Hello world!']));
        $this->assertTrue($response->isJson());
    }

    // toArray()
    public function test_to_array()
    {
        $response = new Response(200, [], 'body');
        $this->assertEquals([], $response->toArray());

        $response = new Response(200, [
            'Content-Type' => 'application/json',
        ], \GuzzleHttp\json_encode('Hello world!'));
        $this->assertEquals([], $response->toArray());

        $response = new Response(200, [
            'Content-Type' => 'application/vnd.collection+json',
        ], \GuzzleHttp\json_encode(['item']));
        $this->assertEquals(['item'], $response->toArray());

        $response = new Response(200, [
            'Content-Type' => 'application/json',
        ], \GuzzleHttp\json_encode(['item']));
        $this->assertEquals(['item'], $response->toArray());

        $response = new Response(200, [
            'Content-Type' => 'text/plain',
        ], 'Hello world!');
        $this->assertEquals([], $response->toArray());
    }

    public function test_can_set_property_on_object()
    {
        $response = new Response(200, [
            'Content-Type' => 'application/json',
        ], \GuzzleHttp\json_encode([
            'id'   => 1,
            'name' => 'Book 1',
        ]));
        $this->assertTrue($response->has('name'));
        $response->name = 'Pride and Prejudice';
        $this->assertEquals([
            'id'   => 1,
            'name' => 'Pride and Prejudice',
        ], $response->toArray());
    }

    public function test_can_isset_property()
    {
        $response = new Response(200, [
            'Content-Type' => 'application/json',
        ], \GuzzleHttp\json_encode([
            'id'   => 1,
            'name' => 'Book 1',
        ]));
        $this->assertTrue(isset($response->id));
    }

    // toObject()
    public function test_to_object()
    {
        $response = new Response(200, [
            'Content-Type' => 'application/json',
        ], \GuzzleHttp\json_encode([
            'id'   => 1,
            'name' => 'Book 1',
        ]));
        $obj = $response->toObject();
        $this->assertEquals(1, $obj->get('id'));
        $this->assertEquals('Book 1', $obj->get('name'));
    }

    // toJson() // add
    public function test_to_json()
    {
        $response = new Response(200, [
            'Content-Type' => 'application/json',
        ], \GuzzleHttp\json_encode([
            'id'   => 1,
            'name' => 'Book 1',
        ]));
        $this->assertEquals(\GuzzleHttp\json_encode([
            'id'   => 1,
            'name' => 'Book 1',
        ]), $response->toJson());
    }

    public function test_can_cast_response_to_string()
    {
        $response = new Response(200, [
            'Content-Type' => 'text/plain',
        ], 'Hello world!');
        $this->assertEquals('Hello world!', $response);

        $encodedJson = \GuzzleHttp\json_encode([
            'id'   => 1,
            'name' => 'Book 1',
        ]);
        $response = new Response(200, [
            'Content-Type' => 'application/json',
        ], $encodedJson);
        $this->assertEquals($encodedJson, (string) $response);
    }
}
