# Callismart DTO

A flexible, extensible foundation for Data Transfer Objects (DTOs) in PHP 8.1+. Build type-safe, immutable data containers with zero dependencies.

## Why Callismart DTO?

DTOs are fundamental building blocks for clean architecture, but creating them usually means writing repetitive boilerplate. Callismart DTO eliminates that by providing a powerful, extensible base class that handles:

- **Dynamic Properties** — Add any data without declaring fields
- **Type Coercion** — Automatically cast values to expected types
- **Validation** — Enforce which keys are allowed
- **Security** — Mask sensitive values in debugging and serialization
- **Fluent API** — Chain methods for readable configuration
- **Zero Dependencies** — No external packages required

Perfect for APIs, configuration objects, domain models, and any data transfer scenario.

## Installation

Install via Composer:

```bash
composer require callismart/dto
```

**Requirements**: PHP 8.1 or higher

## Quick Start

### Basic Usage

```php
use Callismart\DTO\DTO;

// Create a DTO with initial data
$dto = new DTO([
    'name'  => 'Alice',
    'email' => 'alice@example.com',
    'age'   => 30,
]);

// Access via magic property
echo $dto->name;           // Alice

// Access via array syntax
echo $dto['email'];        // alice@example.com

// Access via get() method
echo $dto->get( 'age' );   // 30

// Check if key exists
if ( $dto->has( 'name' ) ) {
    echo "Name is set";
}

// Set a value
$dto->phone = '555-1234';

// Remove a key
$dto->remove( 'age' );

// Convert to array
$array = $dto->to_array();

// Convert to JSON
$json = $dto->to_json();
```

### Creating Type-Safe Subclasses

Restrict allowed keys and apply type casting:

```php
use Callismart\DTO\DTO;

class UserDTO extends DTO {
    protected function allowed_keys(): array {
        return [ 'id', 'name', 'email', 'role', 'active' ];
    }

    protected function sensitive_keys(): array {
        return [ 'email' ];
    }

    protected function cast( string $key, mixed $value ): mixed {
        return match ( $key ) {
            'id'     => (int) $value,
            'role'   => strtolower( (string) $value ),
            'active' => (bool) $value,
            default  => $value,
        };
    }
}

$user = new UserDTO([
    'id'     => '42',
    'name'   => 'Bob',
    'email'  => 'bob@example.com',
    'role'   => 'ADMIN',  // Cast to 'admin'
    'active' => 1,        // Cast to true
]);

// This throws InvalidArgumentException
$user->phone = '555-1234';  // 'phone' is not allowed
```

## Core Features

### Dynamic Properties

Access properties using any of three syntaxes:

```php
$dto = new DTO( [ 'name' => 'Alice' ] );

// Magic property access
$dto->name;

// Array access
$dto['name'];

// Explicit get() method
$dto->get( 'name' );

// With default value
$dto->get( 'missing', 'default' );
```

### Type Casting & Validation

Override `cast()` to enforce types and validate values:

```php
class ProductDTO extends DTO {
    protected function cast( string $key, mixed $value ): mixed {
        return match ( $key ) {
            'price'     => (float) $value,
            'quantity'  => (int) $value,
            'category'  => $this->validate_category( $value ),
            'in_stock'  => (bool) $value,
            default     => $value,
        };
    }

    private function validate_category( mixed $value ): string {
        $allowed = [ 'electronics', 'clothing', 'books' ];
        $value = (string) $value;

        if ( ! in_array( $value, $allowed, true ) ) {
            throw new InvalidArgumentException(
                "Invalid category: {$value}"
            );
        }

        return $value;
    }
}

$product = new ProductDTO([
    'price'    => '19.99',      // Cast to float: 19.99
    'quantity' => '5',          // Cast to int: 5
    'category' => 'ELECTRONICS', // Validated: 'electronics'
    'in_stock' => 'yes',        // Cast to bool: true
]);
```

### Key Whitelisting

Restrict which keys are allowed:

```php
class ConfigDTO extends DTO {
    protected function allowed_keys(): array {
        return [ 'host', 'port', 'username', 'password' ];
    }
}

$config = new ConfigDTO([
    'host'     => 'localhost',
    'port'     => 3306,
    'username' => 'user',
    'password' => 'secret',
]);

// This throws InvalidArgumentException
$config->api_key = 'xyz';  // Not in allowed_keys
```

### Sensitive Value Masking

Protect sensitive data in debugging and serialization:

```php
class APIKeyDTO extends DTO {
    protected function sensitive_keys(): array {
        return [ 'api_key', 'secret' ];
    }
}

$creds = new APIKeyDTO([
    'app_id'  => 'my-app',
    'api_key' => 'sk_live_abc123xyz789',
    'secret'  => 'super_secret_value',
]);

// Direct access works
echo $creds->api_key;  // sk_live_abc123xyz789

// But in output, it's masked
var_dump( $creds );                 // api_key: '***', secret: '***'
echo $creds->to_array()['api_key']; // *** (masked)
echo json_encode( $creds );         // {"api_key":"***", ...}
```

### Fluent API

Chain methods for readable code:

```php
$user = new UserDTO()
    ->set( 'id', 1 )
    ->set( 'name', 'Alice' )
    ->set( 'email', 'alice@example.com' )
    ->set( 'role', 'admin' );

// Or fill at once
$user = new UserDTO()
    ->fill([
        'id'    => 1,
        'name'  => 'Alice',
        'email' => 'alice@example.com',
    ]);

// Or merge additional data
$user = new UserDTO( [ 'id' => 1 ] )
    ->merge([
        'name'  => 'Alice',
        'email' => 'alice@example.com',
    ]);
```

### Advanced Operations

```php
$dto = new DTO([
    'id'     => 1,
    'name'   => 'Product',
    'price'  => 19.99,
    'secret' => 'hidden',
]);

// Get all keys
$keys = $dto->keys();        // ['id', 'name', 'price', 'secret']

// Get all values
$values = $dto->values();    // [1, 'Product', 19.99, 'hidden']

// Select only certain keys
$public = $dto->only( ['id', 'name', 'price'] );
// ['id' => 1, 'name' => 'Product', 'price' => 19.99]

// Get all except certain keys
$filtered = $dto->except( ['secret'] );
// ['id' => 1, 'name' => 'Product', 'price' => 19.99]

// Check if empty
if ( $dto->is_empty() ) {
    echo "No data";
}

// Count properties
echo count( $dto );         // 4

// Clear all data
$dto->clear();
```

### Serialization

DTOs work with standard PHP serialization interfaces:

```php
$dto = new DTO([
    'name'     => 'Test',
    'password' => 'secret',
]);

// JSON serialization (automatically masks sensitive values)
$json = json_encode( $dto );

// Iteration (foreach loops)
foreach ( $dto as $key => $value ) {
    echo "{$key}: {$value}";
}

// Countable
echo count( $dto );  // Returns number of properties

// Convert to array
$array = $dto->to_array();

// Convert to JSON string
$json_str = $dto->to_json();

// Populate from JSON
$dto->from_json( '{"name":"Alice","age":30}' );
```

### Debugging

```php
$dto = new UserDTO([
    'id'       => 1,
    'name'     => 'Alice',
    'email'    => 'alice@example.com',
    'password' => 'secret123',
]);

// var_dump respects __debugInfo()
var_dump( $dto );
// Shows password as '***' (masked)

// Get debug information
$debug = $dto->dump();
// Returns array with class name, count, allowed keys, and masked properties
```

## API Reference

### Reading

```php
$dto->get( string $key, mixed $default = null ): mixed
$dto['key']                                    // ArrayAccess
$dto->key                                      // Magic property
$dto->has( string $key ): bool
$dto->keys(): array
$dto->values(): array
$dto->to_array(): array
$dto->is_empty(): bool
$dto->count(): int
$dto->dump(): array
```

### Writing

```php
$dto->set( string $key, mixed $value ): static  // Fluent, returns $this
$dto->fill( array $data ): static               // Replace all, fluent
$dto->merge( array $data ): static              // Merge in, fluent
$dto->remove( string $key ): static             // Delete one, fluent
$dto->clear(): static                           // Delete all, fluent
$dto[$key] = $value                             // ArrayAccess
$dto->key = $value                              // Magic property
```

### Selection

```php
$dto->only( array $keys ): array                // Only these keys
$dto->except( array $keys ): array              // All except these
```

### Serialization

```php
$dto->to_json( int $flags = 0 ): string
$dto->from_json( string $json ): static
json_encode( $dto )                             // Works (JsonSerializable)
foreach ( $dto as $key => $value ) {}           // Works (IteratorAggregate)
count( $dto )                                   // Works (Countable)
```

## Extension Points

Override these methods in subclasses to customize behavior:

### allowed_keys()

Restrict which properties can be set:

```php
protected function allowed_keys(): array {
    return [ 'key1', 'key2', 'key3' ];
}
```

Return empty array (default) to allow any key.

### sensitive_keys()

Mark values that should be masked in output:

```php
protected function sensitive_keys(): array {
    return [ 'password', 'api_key', 'token' ];
}
```

Sensitive values are masked as `***` in:
- `to_array()` output
- JSON serialization
- `var_dump()` output
- `dump()` method

### cast()

Transform or validate values before storage:

```php
protected function cast( string $key, mixed $value ): mixed {
    return match ( $key ) {
        'age'    => (int) $value,
        'email'  => strtolower( (string) $value ),
        'active' => (bool) $value,
        default  => $value,
    };
}
```

Can throw `InvalidArgumentException` for invalid values.

## Examples

### API Request/Response

```php
class CreateUserRequest extends DTO {
    protected function allowed_keys(): array {
        return [ 'name', 'email', 'password' ];
    }

    protected function cast( string $key, mixed $value ): mixed {
        return match ( $key ) {
            'email' => strtolower( (string) $value ),
            'name'  => trim( (string) $value ),
            default => $value,
        };
    }
}

// Handle request
$request = new CreateUserRequest( $_POST );
$user = create_user( $request );
```

### Configuration Object

```php
class DatabaseConfig extends DTO {
    protected function allowed_keys(): array {
        return [ 'host', 'port', 'database', 'username', 'password' ];
    }

    protected function sensitive_keys(): array {
        return [ 'password' ];
    }

    protected function cast( string $key, mixed $value ): mixed {
        return match ( $key ) {
            'port' => (int) $value,
            default => $value,
        };
    }
}

$config = new DatabaseConfig([
    'host'     => 'localhost',
    'port'     => '3306',
    'database' => 'myapp',
    'username' => 'user',
    'password' => 'secret',
]);

echo json_encode( $config );  // password is masked
```

### Domain Entity

```php
class Product extends DTO {
    protected function allowed_keys(): array {
        return [ 'id', 'name', 'sku', 'price', 'quantity', 'created_at' ];
    }

    protected function cast( string $key, mixed $value ): mixed {
        return match ( $key ) {
            'id'       => (int) $value,
            'price'    => (float) $value,
            'quantity' => (int) $value,
            'created_at' => new DateTime( $value ),
            default    => $value,
        };
    }

    public function is_in_stock(): bool {
        return $this->quantity > 0;
    }
}

$product = new Product([
    'id'       => '123',
    'name'     => 'Widget',
    'sku'      => 'WDG-001',
    'price'    => '19.99',
    'quantity' => '50',
    'created_at' => '2025-05-13 10:00:00',
]);

echo $product->is_in_stock() ? 'In Stock' : 'Out of Stock';
```

### Nested DTOs

```php
class Address extends DTO {
    protected function allowed_keys(): array {
        return [ 'street', 'city', 'state', 'zip', 'country' ];
    }
}

class User extends DTO {
    protected function allowed_keys(): array {
        return [ 'id', 'name', 'email', 'address' ];
    }

    protected function cast( string $key, mixed $value ): mixed {
        if ( $key === 'address' && is_array( $value ) ) {
            return new Address( $value );
        }
        return $value;
    }
}

$user = new User([
    'id'    => 1,
    'name'  => 'Alice',
    'email' => 'alice@example.com',
    'address' => [
        'street'  => '123 Main St',
        'city'    => 'Springfield',
        'state'   => 'IL',
        'zip'     => '62701',
        'country' => 'USA',
    ],
]);

echo $user->address->city;  // Springfield
```

## Testing

Run the test suite with:

```bash
composer test
```

Or with PHPUnit directly:

```bash
./vendor/bin/phpunit
```

## Best Practices

1. **Create Typed Subclasses** — Always subclass DTO for domain objects, not raw instances
2. **Define allowed_keys()** — Restrict to prevent typos and unexpected properties
3. **Implement cast()** — Enforce types, trim strings, validate values
4. **Mark sensitive_keys()** — Protect passwords, tokens, API keys, etc.
5. **Use Fluent API** — Chain methods for readable initialization
6. **Immutability** — Once created, avoid mutating DTOs (clone if needed)
7. **Safe Logging** — Use `dump()` or `to_array()` for safe debug output

## Performance

The DTO class is designed for performance:

- **Zero Reflection** — No reflection or magic used beyond `__get/__set`
- **Direct Array Access** — Internal storage is a simple array
- **Minimal Overhead** — Single method call for get/set operations
- **Type Hints** — Full type hints enable JIT compilation optimizations

## Security

Sensitive value protection:

- Automatic masking in serialization and debugging
- Prevent accidental credential exposure in logs
- Safe for storing in configuration objects
- Works with any sensitive key name you define

## Requirements

- **PHP**: 8.1 or higher
- **Extensions**: None required

## License

MIT License. See LICENSE file for details.

## Contributing

Contributions are welcome! Please ensure:

- Code follows WordPress PHP Coding Standards (K&R braces, tab indentation)
- All public methods have PHPDoc comments
- Full type hints on parameters and returns
- Tests pass with `composer test`

## Support

- **Issues**: Report bugs on GitHub
- **Documentation**: Check examples and API reference above
- **Questions**: Create a discussion on GitHub

## Related Packages

- **callismart/http** — Lightweight HTTP client with multi-adapter support
- **callismart/database** — Multi-adapter database abstraction (uses this DTO for configuration)

## Changelog

### v1.0.0
- Initial release
- Full DTO functionality with extension hooks
- Magic property access, array access, iteration
- JSON serialization with sensitive value masking
- Fluent API with chaining
- Zero dependencies

## Author

Callistus Nwachukwu - [admin@callismart.com](mailto:admin@callismart.com)