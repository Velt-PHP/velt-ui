<?php

declare(strict_types=1);

namespace Velt\Ui\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Ui\Components\Alert;
use Velt\Ui\Components\Button;
use Velt\Ui\Components\Card;
use Velt\Ui\Components\Form;
use Velt\Ui\Components\Input;
use Velt\Ui\Components\Link;
use Velt\Ui\Components\Text;
use Velt\Ui\Page;

/**
 * Class PageAndComponentsTest
 *
 * Tests pour la Page et tous les composants UI MVP.
 */
class PageAndComponentsTest extends TestCase
{
    /**
     * Test que Text::make() crée un composant avec le bon contenu.
     */
    public function test_text_make(): void
    {
        $text = Text::make('Bonjour');

        $array = $text->toArray();

        $this->assertSame('text', $array['type']);
        $this->assertSame('Bonjour', $array['content']);
    }

    /**
     * Test que Text supporte la méthode as().
     */
    public function test_text_as(): void
    {
        $text = Text::make('Titre')->as('h1');

        $array = $text->toArray();

        $this->assertSame('h1', $array['props']['as']);
    }

    /**
     * Test que Button::make() crée un composant avec le bon contenu.
     */
    public function test_button_make(): void
    {
        $button = Button::make('Cliquer');

        $array = $button->toArray();

        $this->assertSame('button', $array['type']);
        $this->assertSame('Cliquer', $array['content']);
    }

    /**
     * Test que Button supporte la méthode type().
     */
    public function test_button_type(): void
    {
        $button = Button::make('Cliquer')->type('submit');

        $array = $button->toArray();

        $this->assertSame('submit', $array['props']['type']);
    }

    /**
     * Test que Button supporte la méthode variant().
     */
    public function test_button_variant(): void
    {
        $button = Button::make('Cliquer')->variant('primary');

        $array = $button->toArray();

        $this->assertSame('primary', $array['props']['variant']);
    }

    /**
     * Test que Button supporte la méthode disabled().
     */
    public function test_button_disabled(): void
    {
        $button = Button::make('Cliquer')->disabled();

        $array = $button->toArray();

        $this->assertTrue($array['props']['disabled']);
    }

    /**
     * Test que Card::make() crée un composant.
     */
    public function test_card_make(): void
    {
        $card = Card::make();

        $array = $card->toArray();

        $this->assertSame('card', $array['type']);
    }

    /**
     * Test que Card supporte les enfants.
     */
    public function test_card_children(): void
    {
        $card = Card::make()
            ->add(Text::make('Contenu'))
            ->add(Button::make('Lire plus'));

        $array = $card->toArray();

        $this->assertCount(2, $array['children']);
        $this->assertSame('text', $array['children'][0]['type']);
        $this->assertSame('button', $array['children'][1]['type']);
    }

    /**
     * Test que Form::make() crée un composant.
     */
    public function test_form_make(): void
    {
        $form = Form::make();

        $array = $form->toArray();

        $this->assertSame('form', $array['type']);
    }

    /**
     * Test que Form supporte la méthode method().
     */
    public function test_form_method(): void
    {
        $form = Form::make()->method('POST');

        $array = $form->toArray();

        $this->assertSame('POST', $array['props']['method']);
    }

    /**
     * Test que Form supporte la méthode action().
     */
    public function test_form_action(): void
    {
        $form = Form::make()->action('/login');

        $array = $form->toArray();

        $this->assertSame('/login', $array['props']['action']);
    }

    /**
     * Test que Form supporte la méthode csrf().
     */
    public function test_form_csrf(): void
    {
        $form = Form::make()->csrf();

        $array = $form->toArray();

        $this->assertTrue($array['props']['csrf']);
    }

    /**
     * Test que Input::make() crée un composant avec name et label.
     */
    public function test_input_make(): void
    {
        $input = Input::make('email', 'Email');

        $array = $input->toArray();

        $this->assertSame('input', $array['type']);
        $this->assertSame('email', $array['name']);
        $this->assertSame('Email', $array['label']);
    }

    /**
     * Test que Input supporte la méthode type().
     */
    public function test_input_type(): void
    {
        $input = Input::make('email', 'Email')->type('email');

        $array = $input->toArray();

        $this->assertSame('email', $array['props']['inputType']);
    }

    /**
     * Test que Input supporte la méthode required().
     */
    public function test_input_required(): void
    {
        $input = Input::make('email', 'Email')->required();

        $array = $input->toArray();

        $this->assertTrue($array['props']['required']);
    }

    /**
     * Test que Input supporte la méthode placeholder().
     */
    public function test_input_placeholder(): void
    {
        $input = Input::make('email', 'Email')->placeholder('Entrez votre email');

        $array = $input->toArray();

        $this->assertSame('Entrez votre email', $array['props']['placeholder']);
    }

    /**
     * Test que Input supporte la méthode value().
     */
    public function test_input_value(): void
    {
        $input = Input::make('email', 'Email')->value('test@example.com');

        $array = $input->toArray();

        $this->assertSame('test@example.com', $array['props']['value']);
    }

    /**
     * Test que Link::make() crée un composant avec label et href.
     */
    public function test_link_make(): void
    {
        $link = Link::make('Dashboard', '/dashboard');

        $array = $link->toArray();

        $this->assertSame('link', $array['type']);
        $this->assertSame('Dashboard', $array['content']);
        $this->assertSame('/dashboard', $array['href']);
    }

    /**
     * Test que Alert::make() crée un composant.
     */
    public function test_alert_make(): void
    {
        $alert = Alert::make('Une erreur est survenue');

        $array = $alert->toArray();

        $this->assertSame('alert', $array['type']);
        $this->assertSame('Une erreur est survenue', $array['content']);
    }

    /**
     * Test que Alert supporte la méthode type().
     */
    public function test_alert_type(): void
    {
        $alert = Alert::make('Erreur')->type('error');

        $array = $alert->toArray();

        $this->assertSame('error', $array['props']['alertType']);
    }

    /**
     * Test que la syntaxe officielle de l'Issue 01 fonctionne.
     */
    public function test_official_api_syntax(): void
    {
        $page = Page::make('Connexion')
            ->layout('auth')
            ->meta(['title' => 'Connexion - Velt App'])
            ->add(
                Card::make()->class('p-8')->add(
                    Text::make('Connexion')->as('h1')
                )->add(
                    Button::make('Se connecter')->type('submit')->variant('primary')
                )
            );

        $array = $page->toArray();

        $this->assertSame('page', $array['type']);
        $this->assertSame('Connexion', $array['title']);
        $this->assertSame('auth', $array['layout']);
        $this->assertSame('Connexion - Velt App', $array['meta']['title']);

        $this->assertCount(1, $array['children']);
        $this->assertSame('card', $array['children'][0]['type']);
        $this->assertSame('p-8', $array['children'][0]['props']['class']);

        $cardChildren = $array['children'][0]['children'];
        $this->assertCount(2, $cardChildren);
        $this->assertSame('text', $cardChildren[0]['type']);
        $this->assertSame('h1', $cardChildren[0]['props']['as']);
        $this->assertSame('button', $cardChildren[1]['type']);
        $this->assertSame('submit', $cardChildren[1]['props']['type']);
        $this->assertSame('primary', $cardChildren[1]['props']['variant']);
    }

    /**
     * Test que les composants sont sérialisables en JSON.
     */
    public function test_page_json_serialization(): void
    {
        $page = Page::make('Test')
            ->add(Text::make('Bonjour'));

        $json = $page->toJson();

        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertSame(1, $decoded['schemaVersion']);
        $this->assertSame('Test', $decoded['screen']);
        $this->assertSame('Text', $decoded['components'][0]['type']);
    }

    /**
     * Test que les props chainables fonctionnent.
     */
    public function test_chainable_methods(): void
    {
        $button = Button::make('Clique')
            ->type('submit')
            ->variant('primary')
            ->class('w-full')
            ->class('h-10');

        $array = $button->toArray();

        // Seule la dernière classe est conservée (comportement de prop)
        $this->assertSame('h-10', $array['props']['class']);
        $this->assertSame('submit', $array['props']['type']);
        $this->assertSame('primary', $array['props']['variant']);
    }
}
