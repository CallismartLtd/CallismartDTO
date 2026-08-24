<?php

declare( strict_types = 1 );

namespace Callismart\DTO\Tests;

use PHPUnit\Framework\TestCase;
use Callismart\DTO\DTO;
use InvalidArgumentException;

/**
 * Comprehensive Test Suite for Callismart DTO.
 *
 * Covers all features including basic operations, type casting,
 * validation, serialization, and extension hooks.
 */
class DTOTest extends TestCase {

    /*
    |---------------------
    | BASIC OPERATIONS
    |---------------------
    */

    /**
     * Test basic instantiation and retrieval.
     */
    public function test_initialization_and_basic_access(): void {
        $data = [ 'id' => 101, 'status' => 'active' ];
        $dto = new DTO( $data );

        $this->assertEquals( 101, $dto->get( 'id' ) );
        $this->assertEquals( 'active', $dto['status'] );
        $this->assertEquals( 'default', $dto->get( 'missing', 'default' ) );
    }

    /**
     * Test empty DTO initialization.
     */
    public function test_empty_dto_creation(): void {
        $dto = new DTO();
        $this->assertCount( 0, $dto );
        $this->assertTrue( $dto->is_empty() );
    }

    /**
     * Test setting and getting values.
     */
    public function test_set_and_get_operations(): void {
        $dto = new DTO();
        
        $dto->set( 'name', 'Alice' );
        $this->assertEquals( 'Alice', $dto->get( 'name' ) );

        $dto->set( 'age', 30 );
        $this->assertEquals( 30, $dto->get( 'age' ) );
    }

    /**
     * Test removing properties.
     */
    public function test_remove_property(): void {
        $dto = new DTO( [ 'name' => 'Alice', 'age' => 30 ] );
        
        $this->assertTrue( $dto->has( 'name' ) );
        $dto->remove( 'name' );
        $this->assertFalse( $dto->has( 'name' ) );
        $this->assertCount( 1, $dto );
    }

    /**
     * Test clearing all properties.
     */
    public function test_clear_all_properties(): void {
        $dto = new DTO( [ 'a' => 1, 'b' => 2, 'c' => 3 ] );
        $this->assertCount( 3, $dto );
        
        $dto->clear();
        $this->assertCount( 0, $dto );
        $this->assertTrue( $dto->is_empty() );
    }

    /**
     * Test has() with null values.
     */
    public function test_has_method_with_null_values(): void {
        $dto = new DTO( [ 'explicit_null' => null ] );
        
        // has() should return true even for null values (unlike isset)
        $this->assertTrue( $dto->has( 'explicit_null' ) );
        $this->assertNull( $dto->get( 'explicit_null' ) );
        $this->assertFalse( $dto->has( 'nonexistent' ) );
    }

    /*
    |------------------------
    | MAGIC PROPERTY ACCESS
    |------------------------
    */

    /**
     * Test magic property access (__get, __set).
     */
    public function test_magic_property_access(): void {
        $dto = new DTO();
        
        $dto->username = 'john_doe';
        $this->assertEquals( 'john_doe', $dto->username );

        $dto->email = 'john@example.com';
        $this->assertEquals( 'john@example.com', $dto->email );
    }

    /**
     * Test magic isset (__isset).
     */
    public function test_magic_isset(): void {
        $dto = new DTO( [ 'present' => 'value' ] );
        
        $this->assertTrue( isset( $dto->present ) );
        $this->assertFalse( isset( $dto->missing ) );
    }

    /**
     * Test magic unset (__unset).
     */
    public function test_magic_unset(): void {
        $dto = new DTO( [ 'key' => 'value' ] );
        
        $this->assertTrue( $dto->has( 'key' ) );
        unset( $dto->key );
        $this->assertFalse( $dto->has( 'key' ) );
    }

    /*
    |-------------------------------
    | ARRAY ACCESS (ArrayAccess)
    |-------------------------------
    */

    /**
     * Test ArrayAccess implementation.
     */
    public function test_array_access_compliance(): void {
        $dto = new DTO();
        $dto['product'] = 'Smart License';

        $this->assertTrue( isset( $dto['product'] ) );
        $this->assertEquals( 'Smart License', $dto['product'] );

        unset( $dto['product'] );
        $this->assertFalse( $dto->has( 'product' ) );
    }

    /**
     * Test offsetGet with non-existent key.
     */
    public function test_array_access_nonexistent_key(): void {
        $dto = new DTO();
        $this->assertNull( $dto['missing'] );
    }

    /**
     * Test array access with non-string key throws exception.
     */
    public function test_array_access_non_string_key_throws(): void {
        $dto = new DTO();
        
        $this->expectException( InvalidArgumentException::class );
        $dto[123] = 'value';  // Keys must be strings
    }

    /*
    |---------------------------------
    | FLUENT API / METHOD CHAINING
    |---------------------------------
    */

    /**
     * Test fluent interface (method chaining).
     */
    public function test_fluid_interface_chaining(): void {
        $dto = new DTO();
        $result = $dto->set( 'key1', 'val1' )
            ->set( 'key2', 'val2' )
            ->set( 'key3', 'val3' );

        $this->assertSame( $dto, $result );  // Returns self
        $this->assertCount( 3, $dto );
    }

    /**
     * Test chaining with merge().
     */
    public function test_chaining_with_merge(): void {
        $dto = new DTO( [ 'a' => 1 ] )
            ->merge( [ 'b' => 2, 'c' => 3 ] )
            ->set( 'd', 4 );

        $this->assertEquals( [ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4 ], $dto->to_array() );
    }

    /**
     * Test fill() replaces all data (fluent).
     */
    public function test_fill_replaces_and_is_fluent(): void {
        $dto = new DTO( [ 'old1' => 1, 'old2' => 2 ] );
        
        $result = $dto->fill( [ 'new1' => 'a', 'new2' => 'b' ] );
        
        $this->assertSame( $dto, $result );
        $this->assertFalse( $dto->has( 'old1' ) );
        $this->assertEquals( [ 'new1' => 'a', 'new2' => 'b' ], $dto->to_array() );
    }

    /**
     * Test merge() preserves existing data (fluent).
     */
    public function test_merge_preserves_data_and_is_fluent(): void {
        $dto = new DTO( [ 'id' => 1, 'name' => 'Alice' ] );
        
        $result = $dto->merge( [ 'email' => 'alice@example.com' ] );
        
        $this->assertSame( $dto, $result );
        $this->assertTrue( $dto->has( 'id' ) );
        $this->assertTrue( $dto->has( 'name' ) );
        $this->assertTrue( $dto->has( 'email' ) );
    }

    /*
    |----------
    | SERIALIZATION & CONVERSION
    |----------
    */

    /**
     * Test to_array() conversion.
     */
    public function test_to_array_conversion(): void {
        $original = [ 'a' => 1, 'b' => 'two', 'c' => [ 1, 2, 3 ] ];
        $dto = new DTO( $original );

        $this->assertEquals( $original, $dto->to_array() );
    }

    /**
     * Test to_json() conversion.
     */
    public function test_to_json_conversion(): void {
        $dto = new DTO( [ 'name' => 'Alice', 'age' => 30 ] );
        $json = $dto->to_json();

        $this->assertIsString( $json );
        $decoded = json_decode( $json, true );
        $this->assertEquals( 'Alice', $decoded['name'] );
        $this->assertEquals( 30, $decoded['age'] );
    }

    /**
     * Test json_encode() works (JsonSerializable).
     */
    public function test_json_encode_with_jsonserializable(): void {
        $dto = new DTO( [ 'key' => 'value', 'number' => 42 ] );
        $json = json_encode( $dto );

        $this->assertIsString( $json );
        $decoded = json_decode( $json, true );
        $this->assertEquals( 'value', $decoded['key'] );
        $this->assertEquals( 42, $decoded['number'] );
    }

    /**
     * Test from_json() with valid JSON.
     */
    public function test_from_json_with_valid_json(): void {
        $dto = new DTO();
        $json = '{"site":"example.com","active":true}';
        
        $result = $dto->from_json( $json );
        
        $this->assertSame( $dto, $result );  // Fluent
        $this->assertEquals( 'example.com', $dto->site );
        $this->assertTrue( $dto->active );
    }

    /**
     * Test from_json() throws on invalid JSON.
     */
    public function test_from_json_throws_on_invalid_json(): void {
        $dto = new DTO();
        
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Invalid JSON' );
        $dto->from_json( 'invalid-json' );
    }

    /**
     * Test from_json() throws when JSON is not an object/array.
     */
    public function test_from_json_throws_on_scalar_json(): void {
        $dto = new DTO();
        
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'must decode to an object/array' );
        $dto->from_json( '"just a string"' );
    }

    /**
     * Test from_json() merges data (fluent).
     */
    public function test_from_json_merges_data(): void {
        $dto = new DTO( [ 'existing' => 'value' ] );
        $dto->from_json( '{"new":"data"}' );

        $this->assertTrue( $dto->has( 'existing' ) );
        $this->assertTrue( $dto->has( 'new' ) );
    }

    /*
    |----------
    | KEYS & VALUES
    |----------
    */

    /**
     * Test keys() returns all keys.
     */
    public function test_keys_method(): void {
        $dto = new DTO( [ 'id' => 1, 'name' => 'Alice', 'email' => 'a@example.com' ] );
        
        $keys = $dto->keys();
        
        $this->assertEquals( [ 'id', 'name', 'email' ], $keys );
    }

    /**
     * Test values() returns all values.
     */
    public function test_values_method(): void {
        $dto = new DTO( [ 'id' => 1, 'name' => 'Alice', 'count' => 5 ] );
        
        $values = $dto->values();
        
        $this->assertEquals( [ 1, 'Alice', 5 ], $values );
    }

    /**
     * Test only() returns selected keys.
     */
    public function test_only_filter(): void {
        $dto = new DTO( [ 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4 ] );

        $filtered = $dto->only( [ 'a', 'c' ] );

        $this->assertEquals( [ 'a' => 1, 'c' => 3 ], $filtered );
        $this->assertCount( 4, $dto );  // Original unchanged
    }

    /**
     * Test except() returns all except selected keys.
     */
    public function test_except_filter(): void {
        $dto = new DTO( [ 'a' => 1, 'b' => 2, 'c' => 3 ] );

        $filtered = $dto->except( [ 'b' ] );

        $this->assertEquals( [ 'a' => 1, 'c' => 3 ], $filtered );
        $this->assertCount( 3, $dto );  // Original unchanged
    }

    /**
     * Test only() with non-existent keys.
     */
    public function test_only_with_nonexistent_keys(): void {
        $dto = new DTO( [ 'a' => 1, 'b' => 2 ] );
        $filtered = $dto->only( [ 'x', 'y', 'z' ] );

        $this->assertEmpty( $filtered );
    }

    /*
    |----------
    | ITERATION & COUNTABLE
    |----------
    */

    /**
     * Test iteration (IteratorAggregate).
     */
    public function test_dto_is_iterable(): void {
        $data = [ 'x' => 10, 'y' => 20, 'z' => 30 ];
        $dto = new DTO( $data );
        $iterated = [];

        foreach ( $dto as $key => $value ) {
            $iterated[ $key ] = $value;
        }

        $this->assertEquals( $data, $iterated );
    }

    /**
     * Test iteration order matches insertion order.
     */
    public function test_iteration_order(): void {
        $dto = new DTO();
        $dto->set( 'first', 1 );
        $dto->set( 'second', 2 );
        $dto->set( 'third', 3 );

        $keys = [];
        foreach ( $dto as $key => $value ) {
            $keys[] = $key;
        }

        $this->assertEquals( [ 'first', 'second', 'third' ], $keys );
    }

    /**
     * Test Countable interface.
     */
    public function test_dto_count(): void {
        $dto = new DTO( [ 'one' => 1, 'two' => 2 ] );
        $this->assertCount( 2, $dto );
        
        $dto->clear();
        $this->assertEquals( 0, count( $dto ) );
    }

    /**
     * Test count after adding/removing items.
     */
    public function test_count_after_modifications(): void {
        $dto = new DTO();
        $this->assertCount( 0, $dto );

        $dto->set( 'a', 1 );
        $this->assertCount( 1, $dto );

        $dto->set( 'b', 2 );
        $this->assertCount( 2, $dto );

        $dto->remove( 'a' );
        $this->assertCount( 1, $dto );
    }

    /*
    |----------
    | EXTENSION HOOKS: ALLOWED KEYS
    |----------
    */

    /**
     * Test allowed_keys() enforcement.
     */
    public function test_allowed_keys_enforcement(): void {
        $dto = new AllowedKeysDTO( [ 'name' => 'Alice', 'email' => 'alice@example.com' ] );

        $this->assertEquals( 'Alice', $dto->name );
        $this->assertEquals( 'alice@example.com', $dto->email );
    }

    /**
     * Test disallowed keys throw exception.
     */
    public function test_disallowed_key_throws_exception(): void {
        $dto = new AllowedKeysDTO();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'not an allowed key' );
        $dto->set( 'invalid_key', 'value' );
    }

    /**
     * Test allowed_keys() in constructor.
     */
    public function test_allowed_keys_in_constructor(): void {
        $this->expectException( InvalidArgumentException::class );
        new AllowedKeysDTO( [ 'invalid' => 'value' ] );
    }

    /**
     * Test empty allowed_keys() allows any key (default).
     */
    public function test_default_allows_any_key(): void {
        $dto = new DTO( [ 'any' => 'value', 'random' => 'key' ] );

        $this->assertTrue( $dto->has( 'any' ) );
        $this->assertTrue( $dto->has( 'random' ) );
    }

    /*
    |----------
    | EXTENSION HOOKS: SENSITIVE KEYS & MASKING
    |----------
    */

    /**
     * Test sensitive_keys() masking in to_array().
     */
    public function test_sensitive_keys_masking_in_to_array(): void {
        $dto = new SensitiveKeysDTO( [
            'username' => 'alice',
            'password' => 'secret123',
            'api_key'  => 'sk_live_xyz789',
        ] );

        $array = $dto->to_array();

        $this->assertEquals( 'alice', $array['username'] );
        $this->assertEquals( '***', $array['password'] );
        $this->assertEquals( '***', $array['api_key'] );
    }

    /**
     * Test direct access still works for sensitive keys.
     */
    public function test_direct_access_to_sensitive_keys(): void {
        $dto = new SensitiveKeysDTO( [ 'password' => 'secret123' ] );

        $this->assertEquals( 'secret123', $dto->password );
        $this->assertEquals( 'secret123', $dto->get( 'password' ) );
    }

    /**
     * Test sensitive_keys() masking in JSON.
     */
    public function test_sensitive_keys_masking_in_json(): void {
        $dto = new SensitiveKeysDTO( [ 'password' => 'secret123' ] );
        $json = json_encode( $dto );

        $this->assertStringContainsString( '"password":"***"', $json );
        $this->assertStringNotContainsString( 'secret123', $json );
    }

    /**
     * Test dump() shows masked values.
     */
    public function test_dump_masks_sensitive_values(): void {
        $dto = new SensitiveKeysDTO( [ 'api_key' => 'secret_key' ] );
        $dump = $dto->dump();

        $this->assertEquals( '***', $dump['props']['api_key'] );
    }

    /*
    |----------
    | EXTENSION HOOKS: CASTING
    |----------
    */

    /**
     * Test cast() applies type conversion.
     */
    public function test_cast_hook_applies_conversion(): void {
        $dto = new CastingDTO( [
            'age'    => '30',           // String → int
            'price'  => '19.99',        // String → float
            'active' => 'yes',          // String → bool
            'name'   => '  Alice  ',    // String → trimmed
        ] );

        $this->assertIsInt( $dto->age );
        $this->assertEquals( 30, $dto->age );

        $this->assertIsFloat( $dto->price );
        $this->assertEquals( 19.99, $dto->price );

        $this->assertIsBool( $dto->active );
        $this->assertTrue( $dto->active );

        $this->assertEquals( 'Alice', $dto->name );
    }

    /**
     * Test cast() validation can throw exceptions.
     */
    public function test_cast_validation_throws_exception(): void {
        $this->expectException( InvalidArgumentException::class );
        new CastingDTO( [ 'status' => 'invalid_status' ] );
    }

    /**
     * Test cast() with merge() and fill().
     */
    public function test_cast_applies_on_merge(): void {
        $dto = new CastingDTO();
        $dto->merge( [ 'age' => '25' ] );

        $this->assertIsInt( $dto->age );
        $this->assertEquals( 25, $dto->age );
    }

    /*
    |----------
    | EDGE CASES & SPECIAL VALUES
    |----------
    */

    /**
     * Test storing null values.
     */
    public function test_storing_null_values(): void {
        $dto = new DTO( [ 'nullable' => null ] );

        $this->assertTrue( $dto->has( 'nullable' ) );
        $this->assertNull( $dto->nullable );
    }

    /**
     * Test storing various data types.
     */
    public function test_storing_various_data_types(): void {
        $dto = new DTO( [
            'integer'  => 42,
            'float'    => 3.14,
            'boolean'  => true,
            'null'     => null,
            'array'    => [ 1, 2, 3 ],
            'object'   => (object) [ 'prop' => 'value' ],
            'string'   => 'text',
        ] );

        $this->assertIsInt( $dto->integer );
        $this->assertIsFloat( $dto->float );
        $this->assertIsBool( $dto->boolean );
        $this->assertNull( $dto->null );
        $this->assertIsArray( $dto->array );
        $this->assertIsObject( $dto->object );
        $this->assertIsString( $dto->string );
    }

    /**
     * Test empty string vs null.
     */
    public function test_empty_string_vs_null(): void {
        $dto = new DTO( [ 'empty' => '', 'null' => null ] );

        $this->assertTrue( $dto->has( 'empty' ) );
        $this->assertTrue( $dto->has( 'null' ) );
        $this->assertEquals( '', $dto->empty );
        $this->assertNull( $dto->null );
    }

    /**
     * Test zero values are preserved.
     */
    public function test_zero_values_preserved(): void {
        $dto = new DTO( [ 'zero_int' => 0, 'zero_float' => 0.0 ] );

        $this->assertTrue( $dto->has( 'zero_int' ) );
        $this->assertTrue( $dto->has( 'zero_float' ) );
        $this->assertEquals( 0, $dto->zero_int );
        $this->assertEquals( 0.0, $dto->zero_float );
    }

    /**
     * Test false values are preserved.
     */
    public function test_false_values_preserved(): void {
        $dto = new DTO( [ 'flag' => false ] );

        $this->assertTrue( $dto->has( 'flag' ) );
        $this->assertFalse( $dto->flag );
    }

    /**
     * Test is_empty() accuracy.
     */
    public function test_is_empty_accuracy(): void {
        $dto_empty = new DTO();
        $this->assertTrue( $dto_empty->is_empty() );

        $dto_with_null = new DTO( [ 'key' => null ] );
        $this->assertFalse( $dto_with_null->is_empty() );

        $dto_with_zero = new DTO( [ 'key' => 0 ] );
        $this->assertFalse( $dto_with_zero->is_empty() );

        $dto_with_false = new DTO( [ 'key' => false ] );
        $this->assertFalse( $dto_with_false->is_empty() );
    }

    /*
    |----------
    | IMMUTABILITY SCENARIOS
    |----------
    */

    /**
     * Test modifying data doesn't affect original array.
     */
    public function test_modification_isolation(): void {
        $original = [ 'key' => 'value' ];
        $dto = new DTO( $original );

        $dto->key = 'modified';

        $this->assertEquals( 'value', $original['key'] );
        $this->assertEquals( 'modified', $dto->key );
    }

    /**
     * Test to_array() returns a copy.
     */
    public function test_to_array_returns_copy(): void {
        $dto = new DTO( [ 'key' => 'value' ] );
        $array = $dto->to_array();

        $array['key'] = 'modified';

        $this->assertEquals( 'value', $dto->key );
    }

    /*
    |----------
    | PERFORMANCE / SCALABILITY
    |----------
    */

    /**
     * Test DTO with many properties.
     */
    public function test_dto_with_many_properties(): void {
        $data = [];
        for ( $i = 0; $i < 1000; $i++ ) {
            $data[ "key_{$i}" ] = "value_{$i}";
        }

        $dto = new DTO( $data );

        $this->assertCount( 1000, $dto );
        $this->assertEquals( 'value_500', $dto->get( 'key_500' ) );
    }

    /**
     * Test repeated set/get operations.
     */
    public function test_repeated_operations(): void {
        $dto = new DTO();

        for ( $i = 0; $i < 100; $i++ ) {
            $dto->set( "key_{$i}", $i );
        }

        for ( $i = 0; $i < 100; $i++ ) {
            $this->assertEquals( $i, $dto->get( "key_{$i}" ) );
        }
    }
}

/*
|---------------------------
| TEST FIXTURES / SUBCLASSES
|---------------------------
*/

/**
 * DTO with allowed_keys() restriction.
 */
class AllowedKeysDTO extends DTO {
    protected function allowed_keys(): array {
        return [ 'name', 'email', 'age' ];
    }
}

/**
 * DTO with sensitive_keys() masking.
 */
class SensitiveKeysDTO extends DTO {
    protected function sensitive_keys(): array {
        return [ 'password', 'api_key', 'token', 'secret' ];
    }
}

/**
 * DTO with cast() type conversion and validation.
 */
class CastingDTO extends DTO {
    protected function cast( string $key, mixed $value ): mixed {
        return match ( $key ) {
            'age'    => (int) $value,
            'price'  => (float) $value,
            'active' => $this->to_bool( $value ),
            'name'   => trim( (string) $value ),
            'status' => $this->validate_status( $value ),
            default  => $value,
        };
    }

    private function to_bool( mixed $value ): bool {
        if ( is_bool( $value ) ) {
            return $value;
        }
        if ( is_string( $value ) ) {
            return in_array( strtolower( $value ), [ 'true', 'yes', '1', 'on' ], true );
        }
        return (bool) $value;
    }

    private function validate_status( mixed $value ): string {
        $allowed = [ 'active', 'inactive', 'pending' ];
        $value = (string) $value;

        if ( ! in_array( $value, $allowed, true ) ) {
            throw new InvalidArgumentException(
                "Invalid status: {$value}. Allowed: " . implode( ', ', $allowed )
            );
        }

        return $value;
    }
}