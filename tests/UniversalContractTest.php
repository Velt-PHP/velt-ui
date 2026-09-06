<?php

declare(strict_types=1);

namespace Velt\Ui\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Ui\Components\Button;
use Velt\Ui\Components\Card;
use Velt\Ui\Components\Input;
use Velt\Ui\Components\Text;
use Velt\Ui\Contracts\UniversalUiContract;
use Velt\Ui\Exceptions\UniversalContractViolation;
use Velt\Ui\Page;
use Velt\Ui\Renderers\UniversalRenderer;

final class UniversalContractTest extends TestCase
{
    public function test_contract_serializes_portable_intentions_with_stable_ids(): void
    {
        $page = Page::make('Connexion')->add(
            Card::make()->add(Text::make('Bienvenue')->as('h1'))->add(
                Input::make('email', 'Adresse email')->type('email')->required()
            )->add(Button::make('Continuer')->variant('primary'))
        );

        $data = (new UniversalRenderer())->toArray($page);

        $this->assertSame('2.0', $data['contractVersion']);
        $this->assertSame('layout', $data['components'][0]['type']);
        $this->assertSame('node-0-1', $data['components'][0]['children'][1]['id']);
        $this->assertSame('Adresse email', $data['components'][0]['children'][1]['accessibility']['label']);
        $this->assertSame(44, $data['components'][0]['children'][2]['accessibility']['targetSize']);
        $this->assertContains('themes', $data['capabilities']);
    }

    public function test_css_classes_are_rejected_instead_of_being_forwarded(): void
    {
        $this->expectException(UniversalContractViolation::class);

        (new UniversalRenderer())->render(Page::make('Bad')->add(Card::make()->class('p-8')));
    }

    public function test_non_portable_props_are_reported(): void
    {
        $violations = UniversalUiContract::validate(Page::make('Bad')->add(Button::make('OK')->showIf('guest')));

        $this->assertSame(['0.props.showIf: prop non portable ou inconnue'], $violations);
    }

    public function test_manifest_is_versioned_and_declares_all_required_primitive_families(): void
    {
        $manifest = UniversalUiContract::manifest();

        $this->assertSame('2.0', $manifest['contractVersion']);
        $this->assertSame(11, count($manifest['primitives']));
        $this->assertArrayHasKey('light', $manifest['tokens']['color']);
        $this->assertSame('1rem', $manifest['tokens']['space']['4']);
        $this->assertSame(['light', 'dark'], $manifest['tokens']['themes']);
        $this->assertContains('accessibility', $manifest['capabilities']);
    }
}