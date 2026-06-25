<?php

namespace Tests\Property;

use App\Helpers\LinkParser;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property-based tests for LinkParser
 * 
 * **Feature: chat-enhancements, Property 4: Domain to hyperlink conversion**
 * **Feature: chat-enhancements, Property 5: URL parsing preserves surrounding text**
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.5**
 */
class LinkParserPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * **Feature: chat-enhancements, Property 4: Domain to hyperlink conversion**
     * 
     * For any text containing a domain pattern (word.tld format), 
     * the output should contain a hyperlink with href starting with "https://"
     * if no protocol was specified.
     * 
     * **Validates: Requirements 3.1, 3.2**
     */
    public function testDomainToHyperlinkConversion(): void
    {
        $this->forAll(
            Generator\elements(['google', 'facebook', 'example', 'test', 'mysite']),
            Generator\elements(['com', 'org', 'net', 'co.id', 'io'])
        )
        ->withMaxSize(100)
        ->then(function ($domain, $tld) {
            $input = "{$domain}.{$tld}";
            $output = LinkParser::parse($input);
            
            // Should contain href with https://
            $this->assertStringContainsString('href="https://', $output);
            // Should contain the original domain text
            $this->assertStringContainsString($input, $output);
            // Should have target="_blank"
            $this->assertStringContainsString('target="_blank"', $output);
        });
    }

    /**
     * **Feature: chat-enhancements, Property 5: URL parsing preserves surrounding text**
     * 
     * For any message containing URLs mixed with plain text, 
     * the output should contain hyperlink elements and all non-URL text 
     * should remain as plain text.
     * 
     * **Validates: Requirements 3.3, 3.5**
     */
    public function testUrlParsingPreservesSurroundingText(): void
    {
        $this->forAll(
            Generator\elements(['Check this:', 'Visit', 'Link:', 'See', 'Go to']),
            Generator\elements(['google.com', 'example.org', 'test.io']),
            Generator\elements([' for more info', ' now', ' please', '!', '.'])
        )
        ->withMaxSize(100)
        ->then(function ($prefix, $url, $suffix) {
            $input = "{$prefix} {$url}{$suffix}";
            $output = LinkParser::parse($input);
            
            // Prefix text should be preserved (not inside href)
            $this->assertStringContainsString($prefix, $output);
            // URL should be converted to link
            $this->assertStringContainsString('href="https://' . $url, $output);
            // Suffix should be preserved
            $trimmedSuffix = trim($suffix, '.,;:!?)');
            if (!empty($trimmedSuffix)) {
                $this->assertStringContainsString($trimmedSuffix, $output);
            }
        });
    }

    /**
     * Test that URLs with protocol are handled correctly
     */
    public function testUrlsWithProtocolArePreserved(): void
    {
        $this->forAll(
            Generator\elements(['https://', 'http://']),
            Generator\elements(['google.com', 'example.org/path', 'test.io/page?q=1'])
        )
        ->withMaxSize(100)
        ->then(function ($protocol, $domain) {
            $input = "{$protocol}{$domain}";
            $output = LinkParser::parse($input);
            
            // Should contain the original protocol in href
            $this->assertStringContainsString('href="' . $protocol, $output);
        });
    }

    /**
     * Test multiple URLs in same message
     */
    public function testMultipleUrlsAreConverted(): void
    {
        $this->forAll(
            Generator\elements(['google.com', 'facebook.com']),
            Generator\elements(['twitter.com', 'github.com'])
        )
        ->withMaxSize(100)
        ->then(function ($url1, $url2) {
            if ($url1 === $url2) {
                $url2 = 'example.org';
            }
            
            $input = "Check {$url1} and {$url2}";
            $output = LinkParser::parse($input);
            
            // Count href occurrences
            $hrefCount = substr_count($output, 'href="https://');
            
            // Should have 2 hyperlinks
            $this->assertEquals(2, $hrefCount);
        });
    }

    /**
     * Test that plain text without URLs remains unchanged
     */
    public function testPlainTextRemainsUnchanged(): void
    {
        $this->forAll(
            Generator\elements([
                'Hello world',
                'This is a test',
                'No links here',
                'Just plain text',
                '12345'
            ])
        )
        ->withMaxSize(100)
        ->then(function ($text) {
            $output = LinkParser::parse($text);
            
            // Should not contain any href
            $this->assertStringNotContainsString('href=', $output);
            // Original text should be preserved
            $this->assertEquals($text, $output);
        });
    }
}
