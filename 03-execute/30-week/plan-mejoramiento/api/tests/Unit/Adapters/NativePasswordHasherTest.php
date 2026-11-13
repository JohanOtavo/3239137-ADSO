<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters;

use App\Adapters\Security\NativePasswordHasher;
use PHPUnit\Framework\TestCase;

/** T-14 - The real hasher, against the real algorithm. */
final class NativePasswordHasherTest extends TestCase
{
    private NativePasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new NativePasswordHasher();
    }

    public function test_a_password_verifies_against_its_own_hash(): void
    {
        $hash = $this->hasher->hash('clave-de-prueba');

        $this->assertTrue($this->hasher->matches('clave-de-prueba', $hash));
    }

    public function test_a_wrong_password_does_not_verify(): void
    {
        $hash = $this->hasher->hash('clave-de-prueba');

        $this->assertFalse($this->hasher->matches('otra-clave', $hash));
    }

    /** NFR-3 - the hash must not contain the password it came from. */
    public function test_the_hash_does_not_contain_the_password(): void
    {
        $hash = $this->hasher->hash('clave-de-prueba');

        $this->assertStringNotContainsString('clave-de-prueba', $hash);
    }

    /** The salt makes two hashes of the same password differ. */
    public function test_hashing_the_same_password_twice_gives_different_hashes(): void
    {
        $first = $this->hasher->hash('clave-de-prueba');
        $second = $this->hasher->hash('clave-de-prueba');

        $this->assertNotSame($first, $second);
        $this->assertTrue($this->hasher->matches('clave-de-prueba', $first));
        $this->assertTrue($this->hasher->matches('clave-de-prueba', $second));
    }
}
