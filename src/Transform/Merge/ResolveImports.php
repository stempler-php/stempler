<?php

declare(strict_types=1);

namespace Stempler\Transform\Merge;

use Stempler\Builder;
use Stempler\Exception\ImportException;
use Stempler\Node\HTML\Tag;
use Stempler\Transform\Context\ImportContext;
use Stempler\Transform\Import\Bundle;
use Stempler\Transform\Import\Directory;
use Stempler\Transform\Import\Element;
use Stempler\Transform\Import\ImportInterface;
use Stempler\Transform\Import\Inline;
use Stempler\Transform\Merger;
use Stempler\VisitorContext;
use Stempler\VisitorInterface;

/**
 * Resolves inline imports and use tags.
 */
final class ResolveImports implements VisitorInterface
{
    private string $useKeyword = 'use:';

    public function __construct(
        private readonly Builder $builder,
        private readonly Merger $merger = new Merger(),
    ) {}

    public function enterNode(mixed $node, VisitorContext $ctx): mixed
    {
        if ($node instanceof Tag && \str_starts_with($node->name, $this->useKeyword)) {
            return self::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        }

        return null;
    }

    public function leaveNode(mixed $node, VisitorContext $ctx): mixed
    {
        if (!$node instanceof Tag) {
            return null;
        }

        $importCtx = ImportContext::on($ctx);

        // import definition
        if (\str_starts_with($node->name, $this->useKeyword)) {
            $importCtx->add($this->makeImport($node));

            return self::REMOVE_NODE;
        }

        // imported tag
        try {
            $import = $importCtx->resolve($this->builder, $node->name);
        } catch (\Throwable $e) {
            throw new ImportException(
                \sprintf('Unable to resolve import `%s`', $node->name),
                $node->getContext(),
                $e,
            );
        }

        if ($import !== null) {
            $node = $this->merger->merge($import, $node);

            return $this->merger->isolateNodes($node, $import->getContext()->getPath());
        }

        return null;
    }

    /**
     * Create import definition (aka "use").
     * @throws ImportException
     */
    private function makeImport(Tag $tag): ImportInterface
    {
        $options = [];
        foreach ($tag->attrs as $attr) {
            if (!\is_string($attr->value) || !\is_string($attr->name)) {
                continue;
            }
            $options[$attr->name] = \trim($attr->value, '\'"');
        }

        switch (\strtolower($tag->name)) {
            case 'use':
            case 'use:element':
                $this->assertHasOption('path', $options, $tag);

                return new Element(
                    $options['path'],
                    $options['as'] ?? $options['alias'] ?? null,
                    $tag->getContext(),
                );

            case 'use:dir':
                $this->assertHasOption('dir', $options, $tag);
                $this->assertHasOption('ns', $options, $tag);

                return new Directory(
                    $options['dir'],
                    $options['ns'],
                    $tag->getContext(),
                );

            case 'use:bundle':
                $this->assertHasOption('path', $options, $tag);

                return new Bundle(
                    $options['path'],
                    $options['ns'] ?? null,
                    $tag->getContext(),
                );

            case 'use:inline':
                $this->assertHasOption('name', $options, $tag);

                return new Inline(
                    $options['name'],
                    $tag->nodes,
                    $tag->getContext(),
                );

            default:
                throw new ImportException(\sprintf('Can not import tag `%s`.', $tag->name), $tag->getContext());
        }
    }

    private function assertHasOption(string $option, array $options, Tag $tag): void
    {
        if (!isset($options[$option])) {
            throw new ImportException(\sprintf('Missing `%s` option', $option), $tag->getContext());
        }
    }
}
