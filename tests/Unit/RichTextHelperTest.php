<?php

namespace Tests\Unit;

use App\Support\RichHtmlPurifier;
use App\Support\RichTextHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RichTextHelperTest extends TestCase
{
    #[Test]
    public function word_count_strips_tags_and_entities(): void
    {
        $html = '<p>Hello&nbsp;<strong>world</strong> again</p>';

        $this->assertSame(3, RichTextHelper::wordCountFromHtml($html));
    }

    #[Test]
    public function word_count_returns_zero_for_empty_html(): void
    {
        $this->assertSame(0, RichTextHelper::wordCountFromHtml('<p><br></p>'));
    }

    #[Test]
    public function has_text_content_requires_visible_text(): void
    {
        $this->assertFalse(RichTextHelper::hasTextContent('<p><br></p>'));
        $this->assertTrue(RichTextHelper::hasTextContent('<p>Policy text</p>'));
    }

    #[Test]
    public function purifier_removes_script_tags(): void
    {
        $dirty = '<p>Safe</p><script>alert(1)</script>';
        $clean = RichHtmlPurifier::purify($dirty);

        $this->assertStringContainsString('<p>Safe</p>', $clean);
        $this->assertStringNotContainsString('script', $clean);
    }
}
