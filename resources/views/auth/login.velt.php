<?php

declare(strict_types=1);

use Velt\Ui\Components\Alert;
use Velt\Ui\Components\Button;
use Velt\Ui\Components\Card;
use Velt\Ui\Components\Form;
use Velt\Ui\Components\Input;
use Velt\Ui\Components\Link;
use Velt\Ui\Components\Text;
use Velt\Ui\Page;

return Page::make('Connexion')
    ->layout('auth')
    ->meta([
        'title' => 'Connexion - Velt UI',
        'description' => 'Page de connexion exemple pour le layout auth',
    ])
    ->add(
        Card::make()
            ->class('auth-card')
            ->add(
                Text::make('Connectez-vous')->as('h1')->class('auth-title')
            )
            ->add(
                Text::make('Utilisez votre email et votre mot de passe pour continuer.')->class('auth-subtitle')
            )
            ->add(
                Alert::make('')
                    ->type('info')
                    ->class('auth-alert')
            )
            ->add( 
                Form::make()
                    ->method('POST')
                    ->action('/login')
                    ->csrf()
                    ->add(
                        Input::make('email', 'Adresse email')
                            ->type('email')
                            ->required()
                            ->placeholder('exemple@velt.dev')
                    )
                    ->add(
                        Input::make('password', 'Mot de passe')
                            ->type('password')
                            ->required()
                            ->placeholder('Votre mot de passe')
                    )
                    ->add(
                        Button::make('Se connecter')
                            ->type('submit')
                            ->variant('primary')
                            ->class('auth-submit')
                    )
            )
            ->add(
                Link::make('Mot de passe oublié ?', '/password/reset')
                    ->class('auth-link')
            )
    );