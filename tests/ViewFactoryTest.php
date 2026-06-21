<?php

declare(strict_types=1);

namespace Velt\Ui\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Velt\Ui\Page;
use Velt\Ui\Renderers\JsonRenderer;
use Velt\Ui\Renderers\WebRenderer;
use Velt\Ui\View\ViewFactory;
use Velt\Ui\View\ViewNotFoundException;

class ViewFactoryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'velt-ui-tests-' . uniqid('', true);
        mkdir($this->root . DIRECTORY_SEPARATOR . 'auth', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function test_make_loads_dot_named_velt_page(): void
    {
        $this->writeView('auth/login.velt.php', <<<'PHP'
<?php

use Velt\Ui\Components\Text;
use Velt\Ui\Page;

return Page::make('Connexion')
    ->layout('auth')
    ->meta(['title' => 'Connexion - Velt App'])
    ->add(Text::make('Bienvenue')->as('h1'));
PHP);

        $page = (new ViewFactory($this->root))->make('auth.login');

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame('Connexion', $page->title());
        $this->assertSame('<h1>Bienvenue</h1>', (new WebRenderer())->render($page, ['document' => false]));

        $preview = json_decode((new JsonRenderer())->render($page), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $preview['schemaVersion']);
        $this->assertSame('Connexion', $preview['screen']);
    }

    public function test_make_loads_real_login_page_from_resources_views(): void
    {
        $factory = new ViewFactory(__DIR__ . '/../resources/views');

        $page = $factory->make('auth.login');

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame('Connexion', $page->title());
        $this->assertSame('auth', $page->getLayout());
    }

    public function test_missing_view_throws_view_not_found_exception(): void
    {
        $this->expectException(ViewNotFoundException::class);

        (new ViewFactory($this->root))->make('auth.missing');
    }

    public function test_view_must_return_page(): void
    {
        $this->writeView('auth/bad.velt.php', <<<'PHP'
<?php

return ['not' => 'a page'];
PHP);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return an instance of');

        (new ViewFactory($this->root))->make('auth.bad');
    }

    public function test_rejects_dangerous_view_names(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ViewFactory($this->root))->make('../secret');
    }

    private function writeView(string $relativePath, string $contents): void
    {
        file_put_contents($this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath), $contents);
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

            if (is_dir($child)) {
                $this->removeDirectory($child);
                continue;
            }

            unlink($child);
        }

        rmdir($path);
    }
}
