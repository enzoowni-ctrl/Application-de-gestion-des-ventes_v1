<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Converts exceptions thrown on /api routes into JSON responses so the SPA
 * never receives the HTML error page (e.g. on a 403 access-denied).
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage() ?: 'Request failed'],
                $exception->getStatusCode(),
            ));
        }
    }
}
