<?php

class RecordRepository
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseFactory::getDb();
    }

    public function listForUser(int $userId): array
    {
        $rows = $this->db->all(
            'SELECT id, user_id, title, payload, created_at, updated_at FROM records WHERE user_id = :user_id ORDER BY id DESC',
            [':user_id' => $userId]
        );
        return array_map([$this, 'formatRecord'], $rows ?: []);
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $row = $this->db->one(
            'SELECT id, user_id, title, payload, created_at, updated_at FROM records WHERE id = :id AND user_id = :user_id',
            [':id' => $id, ':user_id' => $userId]
        );
        return $row ? $this->formatRecord($row) : null;
    }

    public function create(int $userId, string $title, array $payload): array
    {
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $id = $this->db->execute(
            'INSERT INTO records (user_id, title, payload) VALUES (:user_id, :title, :payload)',
            [
                ':user_id' => $userId,
                ':title' => $title,
                ':payload' => $payloadJson,
            ]
        );
        return $this->findForUser((int) $id, $userId);
    }

    public function update(int $id, int $userId, string $title, array $payload): ?array
    {
        $existing = $this->findForUser($id, $userId);
        if (!$existing) {
            return null;
        }

        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $this->db->execute(
            'UPDATE records SET title = :title, payload = :payload, updated_at = :updated WHERE id = :id AND user_id = :user_id',
            [
                ':title' => $title,
                ':payload' => $payloadJson,
                ':updated' => date('Y-m-d H:i:s'),
                ':id' => $id,
                ':user_id' => $userId,
            ]
        );
        return $this->findForUser($id, $userId);
    }

    public function delete(int $id, int $userId): bool
    {
        $count = $this->db->execute(
            'DELETE FROM records WHERE id = :id AND user_id = :user_id',
            [':id' => $id, ':user_id' => $userId]
        );
        return $count > 0;
    }

    private function formatRecord(array $row): array
    {
        $payload = $row['payload'];
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'title' => $row['title'],
            'payload' => $payload,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
