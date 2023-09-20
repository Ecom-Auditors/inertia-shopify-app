<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StartSession extends \Illuminate\Session\Middleware\StartSession
{
    const SESSION_IP = 'session_ip';

    protected function lockToUser($session, $request)
    {
        $session->put(self::SESSION_IP, $request->getClientIp());
    }

    protected function validate($session, $request): bool
    {
        return $session->get(self::SESSION_IP) === $request->getClientIp();
    }

    public function getSession(Request $request): Session
    {
        $session = parent::getSession($request);

        if ($id = $this->resolveSessionParameter($request, $session)) {
            $session->setId($id);

            if (!$session->has(self::SESSION_IP)) {
                $this->lockToUser($session, $request);
            } else {
                if (!$this->validate($session, $request)) {
                    $session->setId(null); // refresh ID
                    $session->start();
                    $this->lockToUser($session, $request);
                }
            }
        }

        return $session;
    }

    protected function addCookieToResponse(Response $response, Session $session)
    {
    }

    protected function resolveSessionParameter(Request $request, Session $session)
    {
        if ($request->has($session->getName())) {
            return $request->input($session->getName());
        }
        if ($request->hasHeader('x-session-id')) {
            return $request->header('x-session-id');
        }
    }
}