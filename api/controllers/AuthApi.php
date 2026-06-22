<?php

class AuthApi
{
    public static function login()
    {
        $body = Auth::readJsonBody();
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            Auth::jsonError('Email and password are required', 400);
        }

        $user = Auth::findUserByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Auth::jsonError('Invalid credentials', 401);
        }

        $accessToken = Auth::createAccessToken($user);
        $refreshToken = Auth::createRefreshToken($user);
        Auth::storeRefreshToken((int) $user['id'], $refreshToken);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => Auth::publicUser($user),
        ];
    }

    public static function refresh()
    {
        $body = Auth::readJsonBody();
        $refreshToken = $body['refreshToken'] ?? '';

        if ($refreshToken === '') {
            Auth::jsonError('Refresh token is required', 400);
        }

        $user = Auth::validateRefreshToken($refreshToken);
        if (!$user) {
            Auth::jsonError('Invalid or expired refresh token', 401);
        }

        return [
            'accessToken' => Auth::createAccessToken($user),
            'user' => Auth::publicUser($user),
        ];
    }

    public static function logout()
    {
        $body = Auth::readJsonBody();
        $refreshToken = $body['refreshToken'] ?? '';

        if ($refreshToken !== '') {
            Auth::revokeRefreshToken($refreshToken);
        }

        return ['ok' => true];
    }

    public static function me()
    {
        $user = Auth::requireUser();
        if (!$user) {
            return null;
        }
        return ['user' => $user];
    }
}
