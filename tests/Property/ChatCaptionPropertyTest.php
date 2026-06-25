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
 * Property-based tests for Chat with Caption
 * 
 * **Feature: chat-enhancements, Property 7: Image with caption round-trip**
 * **Feature: chat-enhancements, Property 8: Caption line break preservation**
 * **Validates: Requirements 5.1, 5.3, 5.4**
 */
class ChatCaptionPropertyTest extends TestCase
{
    use TestTrait;
    use RefreshDatabase;

    private User $user;
    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure role exists
        Role::firstOrCreate(['id' => 1], ['name' => 'admin', 'display_name' => 'Admin']);
        
        $this->user = User::factory()->create();
        $kelas = Kelas::factory()->create();
        $this->siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);
    }

    /**
     * **Feature: chat-enhancements, Property 7: Image with caption round-trip**
     * 
     * For any message sent with both image and text (including URLs), 
     * retrieving the message should return both the image path and 
     * the complete text with any URLs intact.
     * 
     * **Validates: Requirements 5.1, 5.3**
     */
    public function testImageWithCaptionRoundTrip(): void
    {
        $this->forAll(
            Generator\elements(['test/image1.jpg', 'test/image2.png', 'uploads/photo.gif']),
            Generator\elements([
                'Check this out!',
                'Visit google.com for more',
                'See https://example.com',
                'Hello world',
                'Link: test.io/page'
            ])
        )
        ->withMaxSize(100)
        ->then(function ($imagePath, $caption) {
            $chat = Chat::create([
                'sender_user_id' => $this->user->id,
                'receiver_siswa_id' => $this->siswa->id,
                'message' => null,
                'message_type' => 'image',
                'attachment_path' => $imagePath,
                'caption' => $caption,
            ]);

            // Retrieve the message
            $retrieved = Chat::find($chat->id);

            // Verify round-trip
            $this->assertEquals($imagePath, $retrieved->attachment_path);
            $this->assertEquals($caption, $retrieved->caption);
            $this->assertEquals('image', $retrieved->message_type);
        });
    }

    /**
     * **Feature: chat-enhancements, Property 8: Caption line break preservation**
     * 
     * For any image caption containing line breaks, 
     * the displayed caption should preserve all line breaks.
     * 
     * **Validates: Requirements 5.4**
     */
    public function testCaptionLineBreakPreservation(): void
    {
        $this->forAll(
            Generator\elements([
                "Line 1\nLine 2",
                "First\nSecond\nThird",
                "Hello\n\nWorld",
                "A\nB\nC\nD",
                "Start\nMiddle\nEnd"
            ])
        )
        ->withMaxSize(100)
        ->then(function ($captionWithBreaks) {
            $originalLineCount = substr_count($captionWithBreaks, "\n");

            $chat = Chat::create([
                'sender_user_id' => $this->user->id,
                'receiver_siswa_id' => $this->siswa->id,
                'message' => null,
                'message_type' => 'image',
                'attachment_path' => 'test/image.jpg',
                'caption' => $captionWithBreaks,
            ]);

            // Retrieve and verify line breaks are preserved
            $retrieved = Chat::find($chat->id);
            $retrievedLineCount = substr_count($retrieved->caption, "\n");

            $this->assertEquals($originalLineCount, $retrievedLineCount);
            $this->assertEquals($captionWithBreaks, $retrieved->caption);
        });
    }

    /**
     * Test that image without caption works correctly
     */
    public function testImageWithoutCaption(): void
    {
        $this->forAll(
            Generator\elements(['test/image1.jpg', 'test/image2.png', 'uploads/photo.gif'])
        )
        ->withMaxSize(100)
        ->then(function ($imagePath) {
            $chat = Chat::create([
                'sender_user_id' => $this->user->id,
                'receiver_siswa_id' => $this->siswa->id,
                'message' => null,
                'message_type' => 'image',
                'attachment_path' => $imagePath,
                'caption' => null,
            ]);

            $retrieved = Chat::find($chat->id);

            $this->assertEquals($imagePath, $retrieved->attachment_path);
            $this->assertNull($retrieved->caption);
            $this->assertEquals('image', $retrieved->message_type);
        });
    }
}
