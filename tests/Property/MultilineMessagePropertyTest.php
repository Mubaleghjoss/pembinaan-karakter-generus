<?php

namespace Tests\Property;

use App\Models\Chat;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Kelas;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for Multiline Message Preservation
 * 
 * **Feature: chat-enhancements, Property 6: Multiline message preservation**
 * **Validates: Requirements 4.4**
 */
class MultilineMessagePropertyTest extends TestCase
{
    use TestTrait;
    use RefreshDatabase;

    private User $user;
    private Siswa $siswa;
    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure role exists
        Role::firstOrCreate(['id' => 1], ['name' => 'admin', 'display_name' => 'Admin']);
        
        $this->user = User::factory()->create();
        $this->kelas = Kelas::factory()->create();
        $this->siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
    }

    /**
     * **Feature: chat-enhancements, Property 6: Multiline message preservation**
     * 
     * For any message containing line breaks, the stored message should 
     * preserve all line breaks exactly as entered.
     * 
     * **Validates: Requirements 4.4**
     */
    public function testMultilineMessagePreservation(): void
    {
        $this->forAll(
            Generator\elements([
                "Line 1\nLine 2",
                "First\nSecond\nThird",
                "Hello\n\nWorld",
                "Test\r\nWindows\r\nLineBreaks",
                "Mixed\nLine\r\nBreaks",
                "Single line",
                "Multiple\n\n\nEmpty\n\n\nLines",
            ])
        )
        ->withMaxSize(100)
        ->then(function ($message) {
            // Clean up previous test data
            Chat::truncate();
            
            // Create chat with multiline message
            $chat = Chat::create([
                'sender_user_id' => $this->user->id,
                'receiver_siswa_id' => $this->siswa->id,
                'message' => $message,
                'message_type' => 'text',
            ]);

            // Retrieve the message
            $retrieved = Chat::find($chat->id);
            
            // Verify line breaks are preserved
            $this->assertEquals($message, $retrieved->message);
            
            // Count line breaks in original and retrieved
            $originalLineBreaks = substr_count($message, "\n") + substr_count($message, "\r");
            $retrievedLineBreaks = substr_count($retrieved->message, "\n") + substr_count($retrieved->message, "\r");
            
            $this->assertEquals($originalLineBreaks, $retrievedLineBreaks);
        });
    }

    /**
     * Test that multiline messages with random content preserve structure
     */
    public function testRandomMultilinePreservation(): void
    {
        $this->forAll(
            Generator\seq(Generator\string()),
            Generator\choose(1, 5)
        )
        ->withMaxSize(50)
        ->then(function ($lines, $lineCount) {
            // Clean up previous test data
            Chat::truncate();
            
            // Create multiline message from random strings
            $filteredLines = array_filter($lines, fn($l) => strlen($l) > 0);
            if (empty($filteredLines)) {
                $filteredLines = ['test'];
            }
            $message = implode("\n", array_slice($filteredLines, 0, $lineCount));
            
            // Create chat
            $chat = Chat::create([
                'sender_user_id' => $this->user->id,
                'receiver_siswa_id' => $this->siswa->id,
                'message' => $message,
                'message_type' => 'text',
            ]);

            // Retrieve and verify
            $retrieved = Chat::find($chat->id);
            $this->assertEquals($message, $retrieved->message);
        });
    }

    /**
     * Test that empty lines are preserved
     */
    public function testEmptyLinesPreservation(): void
    {
        $this->forAll(
            Generator\choose(1, 5),
            Generator\choose(1, 3)
        )
        ->withMaxSize(100)
        ->then(function ($emptyLineCount, $textLineCount) {
            // Clean up previous test data
            Chat::truncate();
            
            // Create message with empty lines
            $emptyLines = str_repeat("\n", $emptyLineCount);
            $textLines = [];
            for ($i = 0; $i < $textLineCount; $i++) {
                $textLines[] = "Line " . ($i + 1);
            }
            $message = implode($emptyLines, $textLines);
            
            // Create chat
            $chat = Chat::create([
                'sender_user_id' => $this->user->id,
                'receiver_siswa_id' => $this->siswa->id,
                'message' => $message,
                'message_type' => 'text',
            ]);

            // Retrieve and verify
            $retrieved = Chat::find($chat->id);
            $this->assertEquals($message, $retrieved->message);
            
            // Verify empty line count
            $expectedNewlines = ($textLineCount - 1) * $emptyLineCount;
            $actualNewlines = substr_count($retrieved->message, "\n");
            $this->assertEquals($expectedNewlines, $actualNewlines);
        });
    }
}
