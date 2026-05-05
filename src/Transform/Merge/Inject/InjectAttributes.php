<?php

declare(strict_types=1);

namespace Stempler\Transform\Merge\Inject;

use Stempler\Node\Aggregate;
use Stempler\Node\HTML\Attr;
use Stempler\Node\HTML\Nil;
use Stempler\Node\HTML\Verbatim;
use Stempler\Node\Mixin;
use Stempler\Node\Raw;
use Stempler\Transform\BlockClaims;
use Stempler\Transform\QuotedValue;
use Stempler\VisitorContext;
use Stempler\VisitorInterface;

/**
 * Creates attribute values based on un-claimed import blocks via `attr:aggregate` attribute.
 */
final class InjectAttributes implements VisitorInterface
{
    public function __construct(
        private readonly BlockClaims $blocks,
    ) {}

    public function enterNode(mixed $node, VisitorContext $ctx): mixed
    {
        if (!$node instanceof Aggregate) {
            return null;
        }

        foreach ($this->blocks->getUnclaimed() as $name) {
            $alias = $node->accepts($name);
            if ($alias === null) {
                continue;
            }

            $value = $this->blocks->claim($name);

            if ($value instanceof QuotedValue) {
                /**
                 * TODO issue #767
                 * @link https://github.com/spiral/framework/issues/767
                 * @psalm-suppress InvalidPropertyAssignmentValue
                 */
                $node->nodes[] = new Attr($alias, $value->getValue());
                continue;
            }

            // simple copy attribute copy
            if ($value instanceof Attr) {
                /**
                 * TODO issue #767
                 * @link https://github.com/spiral/framework/issues/767
                 * @psalm-suppress InvalidPropertyAssignmentValue
                 */
                $node->nodes[] = clone $value;
                continue;
            }

            /**
             * TODO issue #767
             * @link https://github.com/spiral/framework/issues/767
             * @psalm-suppress InvalidPropertyAssignmentValue
             */
            $node->nodes[] = new Attr($alias, $this->wrapValue($value));
        }

        return null;
    }

    public function leaveNode(mixed $node, VisitorContext $ctx): mixed
    {
        return null;
    }

    /**
     * @return Nil|Verbatim|Mixin|scalar
     */
    private function wrapValue(mixed $value): mixed
    {
        return match (true) {
            $value === [] || $value === null || $value instanceof Nil => new Nil(),
            $value instanceof Verbatim || \is_scalar($value) => $value,
            default => new Mixin(\array_merge(
                [new Raw('"')],
                \is_array($value) ? $value : [$value],
                [new Raw('"')],
            )),
        };
    }
}
