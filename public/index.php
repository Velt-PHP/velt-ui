<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Velt\Ui\Renderers\WebRenderer;
use Velt\Ui\View\ViewFactory;

$views = new ViewFactory(__DIR__ . '/../resources/views');
$page = $views->make('auth.login');

$renderer = new WebRenderer(
    static fn (array $form): string => '<input type="hidden" name="_token" value="demo-token">'
);

header('Content-Type: text/html; charset=UTF-8');
echo $renderer->render($page);