<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminAccessBlockerSubscriber implements EventSubscriberInterface
{
    public function __construct(private string $adminPath, private SystemConfigService $systemConfigService, private \Twig\Environment $twig)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            "kernel.request" => ['onKernelRequest', 255],
            KernelEvents::EXCEPTION => ['onError', 255],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $restrictAdmin = $this->systemConfigService->getBool('TorqShopwareCommon.config.restrictAdminByIP');

        if ($restrictAdmin && str_starts_with($event->getRequest()->getPathInfo(), '/' . $this->adminPath)) {
            $validIps = $this->systemConfigService->get('TorqShopwareCommon.config.restrictAdminIPList');
            
            if($validIps === null){
                return;
            }

            $validIps = explode(PHP_EOL, $validIps);

            if(count($validIps) < 1){
               return;
            }

            $validIps = array_map('trim', $validIps);

            $request = $event->getRequest();
            $clientIp = $request->getClientIp();

            if(in_array($clientIp, $validIps)){
                return;
            }

            $event->stopPropagation();

            throw new NotFoundHttpException('Page not found');
        }

    }

    public function onError(ExceptionEvent $event): void
    {
        $restrictAdmin = $this->systemConfigService->getBool('TorqShopwareCommon.config.restrictAdminByIP');

        if ($restrictAdmin && str_starts_with($event->getRequest()->getPathInfo(), '/' . $this->adminPath)) {
            
            $event->stopPropagation();

            $event->setResponse(
                new Response($this->twig->render('@TorqShopwareCommon/administration/error-404.html.twig'))
            );
        }
    }

}