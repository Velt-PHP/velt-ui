<?php

declare(strict_types=1);

namespace Velt\Ui\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Kernel\Application;
use Velt\Ui\Components\Form;
use Velt\Ui\Page;
use Velt\Ui\Providers\UiServiceProvider;
use Velt\Ui\Renderers\JsonRenderer;
use Velt\Ui\Renderers\WebRenderer;
use Velt\Ui\View\ViewFactory;

final class UiServiceProviderTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR . 'velt-ui-provider-' . uniqid('', true);

        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    public function test_it_registers_ui_services_and_aliases(): void
    {
        $app = new Application($this->basePath);
        $app->registerProvider(UiServiceProvider::class);

        $container = $app->container();

        $this->assertSame($container->get(ViewFactory::class), $container->get('view'));
        $this->assertSame($container->get(WebRenderer::class), $container->get('ui.renderer.web'));
        $this->assertSame($container->get(JsonRenderer::class), $container->get('ui.renderer.json'));
    }

    public function test_it_uses_the_configured_view_path(): void
    {
        $viewPath = $this->basePath . DIRECTORY_SEPARATOR . 'custom-views';
        mkdir($viewPath, 0777, true);
        file_put_contents(
            $viewPath . DIRECTORY_SEPARATOR . 'home.velt.php',
            '<?php return \\Velt\\Ui\\Page::make(\'Home\');'
        );

        $app = new Application($this->basePath, ['view' => ['path' => $viewPath]]);
        $app->registerProvider(UiServiceProvider::class);

        $page = $app->container()->get(ViewFactory::class)->make('home');

        $this->assertSame('Home', $page->title());
    }

    public function test_it_passes_the_optional_csrf_service_to_the_web_renderer(): void
    {
        $app = new Application($this->basePath);
        $app->container()->instance('csrf', new class {
            public function field(): string
            {
                return '<input name="_token" value="test-token">';
            }
        });
        $app->registerProvider(UiServiceProvider::class);

        $page = Page::make('Form')->add(Form::make()->csrf());
        $html = $app->container()->get(WebRenderer::class)->render($page);

        $this->assertStringContainsString('name="_token"', $html);
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $child = $path . DIRECTORY_SEPARATOR . $entry;
            is_dir($child) ? $this->removeDirectory($child) : unlink($child);
        }

        rmdir($path);
    }
}
