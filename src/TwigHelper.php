<?php

namespace Brucep\WordPress\TwigHelper;

use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

class TwigHelper
{
    private static array $defaultContext = [];
    private static Environment $twig;

    public static function setDefaultContext(array $defaultContext): void
    {
        self::$defaultContext = $defaultContext;
    }

    public static function render(
        string $template,
        string $id,
        array $context = []): string
    {
        self::init();
        self::$twig->getLoader()->addLoader(new ArrayLoader([$id => $template]));

        $context = array_merge(self::$defaultContext, $context);

        try {
            return self::$twig->render($id, $context);
        } catch (SyntaxError $e) {
            // inline templates need a more helpful line number
            if (empty($e->getSourceContext()->path)) {
                $trace = current(debug_backtrace(0, 1));

                $e->setTemplateLine(
                    $trace['line']
                    - 3 // heredoc and $trace['args'][1]
                    - count($trace['args'][2]) // context
                    - count(explode("\n", $trace['args'][0] ?? '')) // sourceCode
                    + $e->getTemplateLine()
                );
            }

            throw $e;
        }
    }

    public static function display(
        string $template,
        string $id,
        array $context = []): void
    {
        echo self::render($template, $id, $context);
    }

    public static function getEnvironment(): Environment
    {
        self::init();

        return self::$twig;
    }

    private static function init(): void
    {
        if (!isset(self::$twig)) {
            self::$twig = new Environment(
                new ChainLoader(),
                ['debug' => defined('WP_DEBUG') ? WP_DEBUG : false]
            );
            self::$twig->addExtension(new DebugExtension());
        }
    }

    private function __construct()
    {
    }
}
