<?php

declare(strict_types=1);

namespace Velt\Ui\Providers;

use Velt\Kernel\Contracts\ContainerInterface;
use Velt\Kernel\ServiceProvider;
use Velt\Ui\Renderers\JsonRenderer;
use Velt\Ui\Renderers\WebRenderer;
use Velt\Ui\View\ViewFactory;

final class UiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->app->container();

        $container->singleton(
            ViewFactory::class,
            fn (): ViewFactory => new ViewFactory($this->viewPath())
        );

        $container->singleton(
            WebRenderer::class,
            fn (ContainerInterface $container): WebRenderer => new WebRenderer(
                $this->csrfFieldResolver($container)
            )
        );

        $container->singleton(
            JsonRenderer::class,
            static fn (): JsonRenderer => new JsonRenderer()
        );

        $container->alias(ViewFactory::class, 'view');
        $container->alias(WebRenderer::class, 'ui.renderer.web');
        $container->alias(JsonRenderer::class, 'ui.renderer.json');
    }

    private function viewPath(): string
    {
        return (string) $this->app->config()->get(
            'view.path',
            $this->app->basePath()
                . DIRECTORY_SEPARATOR . 'resources'
                . DIRECTORY_SEPARATOR . 'views'
        );
    }

    /**
     * @return null|callable(array<string, mixed>): string
     */
    private function csrfFieldResolver(ContainerInterface $container): ?callable
    {
        if (! $container->has('csrf')) {
            return null;
        }

        $csrf = $container->get('csrf');

        if (! is_object($csrf) || ! method_exists($csrf, 'field')) {
            return null;
        }

        return static fn (array $form): string => (string) $csrf->field();
    }
}
