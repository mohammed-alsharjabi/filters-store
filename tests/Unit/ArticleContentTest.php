<?php

namespace Tests\Unit;

use App\Support\ArticleContent;
use PHPUnit\Framework\TestCase;

class ArticleContentTest extends TestCase
{
    public function test_it_builds_a_stable_arabic_table_of_contents_without_rendering_html(): void
    {
        $content = new ArticleContent;
        $sections = $content->sections("قراءة الموقع\nابدأ بقياس المساحة واتجاه الشمس.\n\nاختيار الخامة\nقارن المواصفات المكتوبة.");

        $this->assertCount(2, $sections);
        $this->assertSame('قراءة الموقع', $sections[0]['title']);
        $this->assertNotEmpty($sections[0]['id']);
        $this->assertSame(['ابدأ بقياس المساحة واتجاه الشمس.'], $sections[0]['paragraphs']);
        $this->assertSame(1, $content->readingMinutes('نص قصير مفيد'));
    }
}
