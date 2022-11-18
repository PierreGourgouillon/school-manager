<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        if ($exception instanceof HttpException) {
            
            $response = new JsonResponse([
                'code' => $exception->getStatusCode(),
                'message' => "The resource you are looking for does not exist"
            ], $exception->getStatusCode());

            $event->setResponse($response);
        } else {
            $response = new JsonResponse([
                'code' => 500,
                'message' => $exception->getMessage()
            ], 500);

            $event->setResponse($response);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
