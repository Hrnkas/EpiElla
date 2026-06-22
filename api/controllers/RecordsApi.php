<?php

class RecordsApi
{
    public static function list()
    {
        $user = Auth::requireUser();
        if (!$user) {
            return null;
        }

        $repo = new RecordRepository();
        return ['records' => $repo->listForUser($user['id'])];
    }

    public static function get($id)
    {
        $user = Auth::requireUser();
        if (!$user) {
            return null;
        }

        $repo = new RecordRepository();
        $record = $repo->findForUser((int) $id, $user['id']);
        if (!$record) {
            Auth::jsonError('Record not found', 404);
        }
        return $record;
    }

    public static function create()
    {
        $user = Auth::requireUser();
        if (!$user) {
            return null;
        }

        $body = Auth::readJsonBody();
        $title = trim($body['title'] ?? '');
        $payload = $body['payload'] ?? [];

        if ($title === '') {
            Auth::jsonError('Title is required', 400);
        }
        if (!is_array($payload)) {
            Auth::jsonError('Payload must be a JSON object', 400);
        }

        $repo = new RecordRepository();
        return $repo->create($user['id'], $title, $payload);
    }

    public static function update($id)
    {
        $user = Auth::requireUser();
        if (!$user) {
            return null;
        }

        $body = Auth::readJsonBody();
        $title = trim($body['title'] ?? '');
        $payload = $body['payload'] ?? null;

        if ($title === '') {
            Auth::jsonError('Title is required', 400);
        }
        if ($payload !== null && !is_array($payload)) {
            Auth::jsonError('Payload must be a JSON object', 400);
        }

        $repo = new RecordRepository();
        $existing = $repo->findForUser((int) $id, $user['id']);
        if (!$existing) {
            Auth::jsonError('Record not found', 404);
        }

        if ($payload === null) {
            $payload = $existing['payload'];
        }

        return $repo->update((int) $id, $user['id'], $title, $payload);
    }

    public static function delete($id)
    {
        $user = Auth::requireUser();
        if (!$user) {
            return null;
        }

        $repo = new RecordRepository();
        if (!$repo->delete((int) $id, $user['id'])) {
            Auth::jsonError('Record not found', 404);
        }
        return ['ok' => true];
    }
}
