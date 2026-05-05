<?php

declare(strict_types=1);

namespace Stempler\Tests\Transform;

use PHPUnit\Framework\TestCase;
use Stempler\Node\HTML\Tag;
use Stempler\Node\Template;
use Stempler\Transform\Import\Bundle;
use Stempler\Transform\Merger;

final class MergerTest extends TestCase
{
    public function testMergeWithBundle(): void
    {
        $merger = new Merger();

        $template = $merger->merge(new Template([new Bundle('/')]), new Tag());

        self::assertCount(1, $template->nodes);
    }
}
