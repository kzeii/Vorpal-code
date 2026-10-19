<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Keeps authentication state separate for each browser tab. */
class TabContextFilter implements FilterInterface
{
    private const CONTEXT_KEY = 'auth_tab_contexts';
    private const ACTIVE_KEY = '_active_tab_context';
    private const AUTH_KEYS = ['user_id', 'name', 'role', 'must_change_password'];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$request instanceof IncomingRequest) return null;

        $session = session();
        $postedToken = $request->getPost('tab_context');
        $queryToken = $request->getGet('tab_context');
        $token = is_string($postedToken) && preg_match('/^[a-f0-9]{32}$/', $postedToken)
            ? $postedToken
            : (is_string($queryToken) && preg_match('/^[a-f0-9]{32}$/', $queryToken) ? $queryToken : null);

        $contexts = $session->get(self::CONTEXT_KEY) ?? [];
        $isLoginPage = $request->getGet('mode') === 'login';

        if ($token === null) {
            $token = bin2hex(random_bytes(16));
            $contexts[$token] = $isLoginPage ? [] : $this->currentAuthState($session);
        }

        $session->set(self::ACTIVE_KEY, $token);
        $session->set(self::CONTEXT_KEY, $contexts);
        $this->restoreAuthState($session, $contexts[$token] ?? []);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $session = session();
        $token = $session->get(self::ACTIVE_KEY);
        if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) return null;

        $contexts = $session->get(self::CONTEXT_KEY) ?? [];
        $authState = $this->currentAuthState($session);
        if (isset($authState['user_id'])) $contexts[$token] = $authState;
        else unset($contexts[$token]);
        $session->set(self::CONTEXT_KEY, $contexts);

        // Carry the tab token through controller redirects so the next request
        // restores the same account context after a form submission.
        $location = $response->getHeaderLine('Location');
        if ($location !== '') {
            $parts = parse_url($location);
            $requestHost = strtolower($request->getUri()->getHost());
            $targetHost = strtolower($parts['host'] ?? $requestHost);
            if ($parts !== false && $targetHost === $requestHost) {
                parse_str($parts['query'] ?? '', $query);
                $query['tab_context'] = $token;
                $target = '';
                if (isset($parts['scheme'])) $target .= $parts['scheme'] . '://';
                if (isset($parts['host'])) $target .= $parts['host'];
                if (isset($parts['port'])) $target .= ':' . $parts['port'];
                $target .= $parts['path'] ?? '/';
                $target .= '?' . http_build_query($query);
                if (isset($parts['fragment'])) $target .= '#' . $parts['fragment'];
                $response->setHeader('Location', $target);
            }
        }

        return null;
    }

    private function currentAuthState($session): array
    {
        $state = [];
        foreach (self::AUTH_KEYS as $key) {
            $value = $session->get($key);
            if ($value !== null) $state[$key] = $value;
        }
        return isset($state['user_id']) ? $state : [];
    }

    private function restoreAuthState($session, array $state): void
    {
        $session->remove(self::AUTH_KEYS);
        if ($state !== []) $session->set($state);
    }
}
