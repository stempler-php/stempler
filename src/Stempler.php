<?php

declare(strict_types=1);

namespace Stempler;

use RuntimeException;
use Stempler\Compiler\Renderer\CoreRenderer;
use Stempler\Compiler\Renderer\DynamicRenderer;
use Stempler\Compiler\Renderer\HTMLRenderer;
use Stempler\Compiler\Renderer\PHPRenderer;
use Stempler\Compiler\Result;
use Stempler\Compiler\SourceMap;
use Stempler\Directive\ConditionalDirective;
use Stempler\Directive\DirectiveGroup;
use Stempler\Directive\DirectiveRendererInterface;
use Stempler\Directive\JsonDirective;
use Stempler\Directive\LoopDirective;
use Stempler\Directive\PHPDirective;
use Stempler\Exception\ContextExceptionInterface;
use Stempler\Lexer\Grammar\DynamicGrammar;
use Stempler\Lexer\Grammar\HTMLGrammar;
use Stempler\Lexer\Grammar\InlineGrammar;
use Stempler\Lexer\Grammar\PHPGrammar;
use Stempler\Loader\LoaderInterface;
use Stempler\Loader\Source;
use Stempler\Node\Template;
use Stempler\Parser\Syntax\DynamicSyntax;
use Stempler\Parser\Syntax\HTMLSyntax;
use Stempler\Parser\Syntax\InlineSyntax;
use Stempler\Parser\Syntax\PHPSyntax;
use Stempler\Transform\Finalizer\DynamicToPHP;
use Stempler\Transform\Finalizer\StackCollector;
use Stempler\Transform\Merge\ExtendsParent;
use Stempler\Transform\Merge\ResolveImports;
use Stempler\Transform\Visitor\DefineAttributes;
use Stempler\Transform\Visitor\DefineBlocks;
use Stempler\Transform\Visitor\DefineHidden;
use Stempler\Transform\Visitor\DefineStacks;
use Throwable;
use function array_values;
use function class_exists;
use function extract;
use function hash;
use function in_array;
use function is_array;
use function is_string;
use function iterator_to_array;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function sprintf;
use const EXTR_OVERWRITE;

final class Stempler
{
    private const CLASS_PREFIX = '__StemplerTemplate__';

    public function __construct(
        private readonly Builder $builder,
        private readonly ?StemplerCache $cache = null,
    ) {}

    /**
     * @param iterable<DirectiveRendererInterface|class-string<DirectiveRendererInterface>> $directives
     * @param array<int, array<int, VisitorInterface|class-string<VisitorInterface>>>       $visitors
     */
    public static function create(
        LoaderInterface $loader,
        iterable $directives = [],
        array $visitors = [],
        ?StemplerCache $cache = null,
    ): self {
        $builder = new Builder($loader);
        $definitions = [
            ...self::iterableToArray($directives),
            ...self::defaultDirectives(),
        ];

        self::registerParser($builder, $definitions);
        self::registerCompiler($builder, $definitions);
        self::registerVisitors($builder, $definitions, $visitors);

        return new self($builder, $cache);
    }

    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * @throws Throwable
     */
    public function load(string $path): Template
    {
        return $this->builder->load($path);
    }

    /**
     * @throws Throwable
     */
    public function compile(string $path): Result
    {
        return $this->builder->compile($path);
    }

    /**
     * @throws ContextExceptionInterface
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function compileTemplate(Template $template): Result
    {
        return $this->builder->compileTemplate($template);
    }

    /**
     * @throws Throwable
     */
    public function render(string $path, array $data = []): string
    {
        if ($this->cache === null) {
            return $this->renderResult($this->compile($path), $data);
        }

        $source = $this->builder->getLoader()->load($path);
        if ($source->getFilename() === null) {
            return $this->renderResult($this->compile($path), $data);
        }

        $class = $this->className($source, $path);
        $key = $this->cacheKey($source, $path);

        if ($this->cache->isFresh($key)) {
            $this->cache->load($key);
        } elseif (!class_exists($class, false)) {
            $result = $this->compile($path);
            $compiled = $this->compileClass($class, $result);
            $dependencies = $this->resolveCachePaths($result);

            if ($dependencies !== null) {
                $this->cache->write($key, $compiled, $dependencies);
                $this->cache->load($key);
            }

            if (!class_exists($class, false)) {
                eval('?>' . $compiled);
            }
        }

        if (!class_exists($class, false)) {
            throw new RuntimeException(sprintf('Unable to load `%s`, cache might be corrupted.', $path));
        }

        return $class::render($data);
    }

    public function renderResult(Result $result, array $data = []): string
    {
        ob_start();
        $outputLevel = ob_get_level();

        try {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            extract($data, EXTR_OVERWRITE);
            eval('?>' . $result->getContent());
        } catch (Throwable $e) {
            while (ob_get_level() >= $outputLevel) {
                ob_end_clean();
            }

            throw $e;
        } finally {
            while (ob_get_level() > $outputLevel) {
                ob_end_clean();
            }
        }

        return (string) ob_get_clean();
    }

    /**
     * @param Result|string $source
     * @return SourceMap|null
     * @throws Throwable
     */
    public function makeSourceMap(Result|string $source): ?SourceMap
    {
        if (is_string($source)) {
            $source = $this->compile($source);
        }

        return $source->getSourceMap($this->builder->getLoader());
    }

    public function reset(string $path): void
    {
        if ($this->cache === null) {
            return;
        }

        $source = $this->builder->getLoader()->load($path);
        if ($source->getFilename() === null) {
            return;
        }

        $this->cache->delete($this->cacheKey($source, $path));
    }

    /**
     * @param array<int, DirectiveRendererInterface|class-string<DirectiveRendererInterface>> $directives
     */
    private static function registerParser(Builder $builder, array $directives): void
    {
        $group = new DirectiveGroup(self::buildDirectives($directives));

        $parser = $builder->getParser();
        $parser->addSyntax(new PHPGrammar(), new PHPSyntax());
        $parser->addSyntax(new InlineGrammar(), new InlineSyntax());
        $parser->addSyntax(new DynamicGrammar($group), new DynamicSyntax());
        $parser->addSyntax(new HTMLGrammar(), new HTMLSyntax());
    }

    /**
     * @param array<int, DirectiveRendererInterface|class-string<DirectiveRendererInterface>> $directives
     */
    private static function registerCompiler(Builder $builder, array $directives): void
    {
        $group = new DirectiveGroup(self::buildDirectives($directives));

        $builder->getCompiler()->addRenderer(new CoreRenderer());
        $builder->getCompiler()->addRenderer(new PHPRenderer());
        $builder->getCompiler()->addRenderer(new HTMLRenderer());
        $builder->getCompiler()->addRenderer(new DynamicRenderer($group));
    }

    /**
     * @param array<int, array<int, VisitorInterface|class-string<VisitorInterface>>> $visitors
     */
    private static function registerVisitors(Builder $builder, array $directives, array $visitors): void
    {
        foreach (self::buildVisitors(self::defaultVisitors()[Builder::STAGE_PREPARE]) as $visitor) {
            $builder->addVisitor($visitor, Builder::STAGE_PREPARE);
        }

        foreach (self::buildVisitors($visitors[Builder::STAGE_PREPARE] ?? []) as $visitor) {
            $builder->addVisitor($visitor, Builder::STAGE_PREPARE);
        }

        $builder->addVisitor(
            new DynamicToPHP(DynamicToPHP::DEFAULT_FILTER, self::buildDirectives($directives)),
            Builder::STAGE_TRANSFORM,
        );
        $builder->addVisitor(new ResolveImports($builder), Builder::STAGE_TRANSFORM);
        $builder->addVisitor(new ExtendsParent($builder), Builder::STAGE_TRANSFORM);

        foreach (self::buildVisitors($visitors[Builder::STAGE_TRANSFORM] ?? []) as $visitor) {
            $builder->addVisitor($visitor, Builder::STAGE_TRANSFORM);
        }

        foreach (self::buildVisitors(self::defaultVisitors()[Builder::STAGE_FINALIZE]) as $visitor) {
            $builder->addVisitor($visitor, Builder::STAGE_FINALIZE);
        }

        foreach (self::buildVisitors($visitors[Builder::STAGE_FINALIZE] ?? []) as $visitor) {
            $builder->addVisitor($visitor, Builder::STAGE_FINALIZE);
        }

        foreach (self::buildVisitors($visitors[Builder::STAGE_COMPILE] ?? []) as $visitor) {
            $builder->addVisitor($visitor, Builder::STAGE_COMPILE);
        }
    }

    /**
     * @return list<DirectiveRendererInterface|class-string<DirectiveRendererInterface>>
     */
    private static function defaultDirectives(): array
    {
        return [
            PHPDirective::class,
            LoopDirective::class,
            JsonDirective::class,
            ConditionalDirective::class,
        ];
    }

    /**
     * @return array<int, list<VisitorInterface|class-string<VisitorInterface>>>
     */
    private static function defaultVisitors(): array
    {
        return [
            Builder::STAGE_PREPARE => [
                DefineBlocks::class,
                DefineAttributes::class,
                DefineHidden::class,
            ],
            Builder::STAGE_FINALIZE => [
                DefineStacks::class,
                StackCollector::class,
            ],
        ];
    }

    /**
     * @param array<int, DirectiveRendererInterface|class-string<DirectiveRendererInterface>> $directives
     * @return list<DirectiveRendererInterface>
     */
    private static function buildDirectives(array $directives): array
    {
        $result = [];

        foreach ([...$directives] as $directive) {
            if (is_string($directive)) {
                $result[] = new $directive();
                continue;
            }

            $result[] = $directive;
        }

        return $result;
    }

    /**
     * @param array<int, VisitorInterface|class-string<VisitorInterface>> $visitors
     * @return list<VisitorInterface>
     */
    private static function buildVisitors(array $visitors): array
    {
        $result = [];

        foreach ($visitors as $visitor) {
            if (is_string($visitor)) {
                $result[] = new $visitor();
                continue;
            }

            $result[] = $visitor;
        }

        return $result;
    }

    private function compileClass(string $class, Result $result): string
    {
        $template = '<?php class %s {
            public static function render(array $data=[]): string {
                \ob_start();
                $__outputLevel__ = \ob_get_level();

                try {
                    \extract($data, EXTR_OVERWRITE);
                    ?>%s<?php
                } catch (\Throwable $e) {
                    while (\ob_get_level() >= $__outputLevel__) { \ob_end_clean(); }
                    throw $e;
                } finally {
                    while (\ob_get_level() > $__outputLevel__) { \ob_end_clean(); }
                }

                return (string) \ob_get_clean();
            }
        }';

        return sprintf($template, $class, $result->getContent());
    }

    private function className(Source $source, string $path): string
    {
        return self::CLASS_PREFIX . $this->cacheKey($source, $path);
    }

    private function cacheKey(Source $source, string $path): string
    {
        return hash('sha256', $source->getFilename() ?? $path);
    }

    /**
     * @return list<string>|null
     */
    private function resolveCachePaths(Result $result): ?array
    {
        $paths = [];

        foreach ($result->getPaths() as $path) {
            $filename = $this->builder->getLoader()->load($path)->getFilename();
            if ($filename === null) {
                return null;
            }

            if (!in_array($filename, $paths, true)) {
                $paths[] = $filename;
            }
        }

        return $paths;
    }

    /**
     * @template TValue
     * @param iterable<TValue> $values
     * @return list<TValue>
     */
    private static function iterableToArray(iterable $values): array
    {
        if (is_array($values)) {
            return array_values($values);
        }

        return iterator_to_array($values, false);
    }
}
