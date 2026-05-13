<?php

namespace Callismart\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Callismart\DTO\DTO;
use InvalidArgumentException;

/**
 * Enhanced Test Suite for Callismart DTO.
 */
class DTOTest extends TestCase {

    /**
     * Test basic instantiation and retrieval.
     */
    public function test_initialization_and_basic_access() {
        $data = ['id' => 101, 'status' => 'active'];
        $dto = new DTO($data);

        $this->assertEquals(101, $dto->get('id'));
        $this->assertEquals('active', $dto['status']);
        $this->assertEquals('default', $dto->get('missing', 'default'));
    }

    /**
     * Test fluid interface (method chaining).
     */
    public function test_fluid_interface_chaining() {
        $dto = new DTO();
        $dto->set('key1', 'val1')
            ->merge(['key2' => 'val2'])
            ->fill(['key3' => 'val3']);

        $this->assertCount(1, $dto); // fill() clears previous keys
        $this->assertTrue($dto->has('key3'));
    }

    /**
     * Test ArrayAccess implementation.
     */
    public function test_array_access_compliance() {
        $dto = new DTO();
        $dto['product'] = 'Smart License';

        $this->assertTrue(isset($dto['product']));
        $this->assertEquals('Smart License', $dto['product']);

        unset($dto['product']);
        $this->assertFalse($dto->has('product'));
    }

    /**
     * Test JSON handling and errors.
     */
    public function test_json_functionality() {
        $dto = new DTO();
        $json = '{"site":"example.com","active":true}';
        
        $dto->from_json($json);
        $this->assertEquals('example.com', $dto->site);
        $this->assertTrue($dto->active);

        $this->expectException(InvalidArgumentException::class);
        $dto->from_json('invalid-json');
    }

    /**
     * Test collection-like helper methods.
     */
    public function test_only_and_except_filters() {
        $dto = new DTO(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertEquals(['a' => 1, 'c' => 3], $dto->only(['a', 'c']));
        $this->assertEquals(['b' => 2], $dto->except(['a', 'c']));
    }

    /**
     * Test iteration (IteratorAggregate).
     */
    public function test_dto_is_iterable() {
        $data = ['x' => 10, 'y' => 20];
        $dto = new DTO($data);
        $iterated = [];

        foreach ($dto as $key => $value) {
            $iterated[$key] = $value;
        }

        $this->assertEquals($data, $iterated);
    }

    /**
     * Test Countable interface.
     */
    public function test_dto_count() {
        $dto = new DTO(['one' => 1, 'two' => 2]);
        $this->assertCount(2, $dto);
        
        $dto->clear();
        $this->assertEquals(0, count($dto));
    }
}