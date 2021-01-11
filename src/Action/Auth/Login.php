<?php

declare(strict_types=1);

namespace Yii\Extension\User\Action\Auth;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yii\Extension\Service\ServiceFlashMessage;
use Yii\Extension\Service\ServiceUrl;
use Yii\Extension\User\Event\AfterLogin;
use Yii\Extension\User\Form\FormLogin;
use Yii\Extension\User\Repository\RepositoryUser;
use Yii\Extension\User\Settings\RepositorySetting;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

final class Login
{
    public function run(
        AfterLogin $afterLogin,
        EventDispatcherInterface $eventDispatcher,
        FormLogin $formLogin,
        RepositorySetting $repositorySetting,
        RepositoryUser $repositoryUser,
        ServerRequestInterface $serverRequest,
        ServiceFlashMessage $serviceFlashMessage,
        ServiceUrl $serviceUrl,
        TranslatorInterface $translator,
        ViewRenderer $viewRenderer
    ): ResponseInterface {
        /** @var array $body */
        $body = $serverRequest->getParsedBody();

        /** @var string $method */
        $method = $serverRequest->getMethod();

        $formLogin->ip($serverRequest->getServerParams()['REMOTE_ADDR']);

        if ($method === 'POST' && $formLogin->load($body) && $formLogin->validate()) {
            $eventDispatcher->dispatch($afterLogin);

            $bodyMessage = $translator->translate('Sign in successful - you are welcome');

            if ($formLogin->getLastLogout() > 0) {
                $bodyMessage = $translator->translate('Sign in successful') . ' - ' .
                    date('Y-m-d G:i:s', $formLogin->getLastLogout());
            }

            $serviceFlashMessage->run(
                'success',
                $translator->translate($repositorySetting->getMessageHeader()),
                $bodyMessage,
            );

            return $serviceUrl->run('site/index');
        }

        return $viewRenderer
            ->withViewPath('@user-view-views')
            ->render('auth/login', ['body' => $body, 'data' => $formLogin]);
    }
}
