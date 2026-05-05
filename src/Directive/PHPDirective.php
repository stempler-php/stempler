<?php

declare(strict_types=1);

namespace Stempler\Directive;

use Stempler\Node\Dynamic\Directive;

final class PHPDirective extends AbstractDirective
{
    public function renderPHP(Directive $directive): string
    {
        return '<?php';
    }

    public function renderEndPHP(Directive $directive): string
    {
        return '?>';
    }
}
