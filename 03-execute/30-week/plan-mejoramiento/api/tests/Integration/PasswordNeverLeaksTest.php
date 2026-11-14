<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Adapters\Persistence\Database\Tables;
use Illuminate\Support\Facades\Log;

/**
 * T-14, T-28 and NFR-3 - The password does not travel back and does not reach
 * a log.
 *
 * This is the requirement that is true until somebody adds one convenient
 * line. A `Log::info($request->all())` while debugging a sign-in writes every
 * password that comes through to disk, and nothing in the test suite notices
 * unless a test like this one exists.
 *
 * It drives the two routes that take a password, in the two outcomes each,
 * and then reads back everything written to the log.
 */
final class PasswordNeverLeaksTest extends IntegrationTestCase
{
    private const SECRET = 'a-password-that-must-not-show-up';

    protected function setUp(): void
    {
        parent::setUp();

        // The suite logs to a file for the length of this test so the written
        // lines can be read back. Production still logs to stderr.
        config([
            'logging.default' => 'single',
            'logging.channels.single.path' => $this->logPath(),
            'logging.channels.single.level' => 'debug',
        ]);

        Log::forgetDrivers();

        if (file_exists($this->logPath())) {
            unlink($this->logPath());
        }
    }

    public function test_a_successful_sign_in_answers_a_token_and_not_the_password(): void
    {
        $token = $this->signInAsAdmin();

        $this->assertNotSame('', $token);
        $this->assertLogIsClean();
    }

    public function test_a_refused_sign_in_does_not_echo_what_was_tried(): void
    {
        $this->signInAsAdmin();

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'root',
            'password' => self::SECRET,
        ]);

        $response->assertStatus(401);
        // The body carries the code and a message for a person, and nothing
        // that was sent: an error that repeats the attempt puts it in every
        // browser console and every proxy log on the way back.
        $this->assertStringNotContainsString(self::SECRET, $response->getContent() ?: '');
        $this->assertLogIsClean();
    }

    public function test_creating_an_advisor_returns_the_user_without_its_password(): void
    {
        $admin = $this->signInAsAdmin();

        $response = $this->postJson('/api/v1/users', [
            'username' => 'advisor-two',
            'password' => self::SECRET,
            'role' => 'advisor',
        ], $this->bearer($admin));

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'username', 'role']);
        $response->assertJsonMissingPath('password');
        $response->assertJsonMissingPath('password_hash');
        $this->assertStringNotContainsString(self::SECRET, $response->getContent() ?: '');
        $this->assertLogIsClean();
    }

    public function test_the_hash_is_stored_and_the_plain_password_is_not(): void
    {
        $admin = $this->signInAsAdmin();

        $this->postJson('/api/v1/users', [
            'username' => 'advisor-three',
            'password' => self::SECRET,
            'role' => 'advisor',
        ], $this->bearer($admin))->assertStatus(201);

        $row = $this->app->make('db')->connection()
            ->table(Tables::USER)
            ->where('username', 'advisor-three')
            ->first();

        $this->assertNotNull($row);
        $this->assertNotSame(self::SECRET, $row->password_hash);
        // A hash somebody can read the password out of is not a hash.
        $this->assertStringNotContainsString(self::SECRET, (string) $row->password_hash);
        $this->assertTrue(password_verify(self::SECRET, (string) $row->password_hash));
    }

    private function assertLogIsClean(): void
    {
        if (! file_exists($this->logPath())) {
            return;
        }

        $written = (string) file_get_contents($this->logPath());

        $this->assertStringNotContainsString(self::SECRET, $written);
        $this->assertStringNotContainsString(self::ADMIN_PASSWORD, $written);
    }

    private function logPath(): string
    {
        return storage_path('logs/password-leak-test.log');
    }
}
