<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    private static function secret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? '';
        if ($secret === '' || $secret === 'change-me') {
            return 'dev-insecure-secret-change-in-production';
        }
        return $secret;
    }

    private static function accessTtl(): int
    {
        return (int) ($_ENV['JWT_ACCESS_TTL'] ?? 3600);
    }

    private static function refreshTtl(): int
    {
        return (int) ($_ENV['JWT_REFRESH_TTL'] ?? 2592000);
    }

    public static function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function createAccessToken(array $user): string
    {
        $now = time();
        $payload = [
            'sub' => (int) $user['id'],
            'email' => $user['email'],
            'type' => 'access',
            'iat' => $now,
            'exp' => $now + self::accessTtl(),
        ];
        return JWT::encode($payload, self::secret(), 'HS256');
    }

    public static function createRefreshToken(array $user): string
    {
        $now = time();
        $payload = [
            'sub' => (int) $user['id'],
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + self::refreshTtl(),
        ];
        return JWT::encode($payload, self::secret(), 'HS256');
    }

    public static function storeRefreshToken(int $userId, string $refreshToken): void
    {
        $db = DatabaseFactory::getDb();
        $expiresAt = date('Y-m-d H:i:s', time() + self::refreshTtl());
        $db->execute(
            'INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :hash, :expires)',
            [
                ':user_id' => $userId,
                ':hash' => self::hashRefreshToken($refreshToken),
                ':expires' => $expiresAt,
            ]
        );
    }

    public static function revokeRefreshToken(string $refreshToken): void
    {
        $db = DatabaseFactory::getDb();
        $db->execute(
            'UPDATE refresh_tokens SET revoked_at = :now WHERE token_hash = :hash',
            [
                ':now' => date('Y-m-d H:i:s'),
                ':hash' => self::hashRefreshToken($refreshToken),
            ]
        );
    }

    public static function decodeToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(self::secret(), 'HS256'));
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function requireUser(): ?array
    {
        $token = self::bearerToken();
        if (!$token) {
            self::jsonError('Missing authorization token', 401);
            return null;
        }

        $decoded = self::decodeToken($token);
        if (!$decoded || ($decoded->type ?? '') !== 'access') {
            self::jsonError('Invalid or expired access token', 401);
            return null;
        }

        $user = self::findUserById((int) $decoded->sub);
        if (!$user) {
            self::jsonError('User not found', 401);
            return null;
        }

        return self::publicUser($user);
    }

    public static function findUserByEmail(string $email): ?array
    {
        $db = DatabaseFactory::getDb();
        return $db->one('SELECT * FROM users WHERE email = :email', [':email' => $email]);
    }

    public static function findUserById(int $id): ?array
    {
        $db = DatabaseFactory::getDb();
        return $db->one('SELECT * FROM users WHERE id = :id', [':id' => $id]);
    }

    public static function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'created_at' => $user['created_at'],
        ];
    }

    public static function validateRefreshToken(string $refreshToken): ?array
    {
        $decoded = self::decodeToken($refreshToken);
        if (!$decoded || ($decoded->type ?? '') !== 'refresh') {
            return null;
        }

        $db = DatabaseFactory::getDb();
        $row = $db->one(
            'SELECT * FROM refresh_tokens WHERE token_hash = :hash AND revoked_at IS NULL AND expires_at > :now',
            [
                ':hash' => self::hashRefreshToken($refreshToken),
                ':now' => date('Y-m-d H:i:s'),
            ]
        );

        if (!$row) {
            return null;
        }

        return self::findUserById((int) $decoded->sub);
    }

    public static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
