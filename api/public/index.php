<?php

include_once __DIR__ . '/../vendor/epiphany/src/Epi.php';
Epi::setPath('base', __DIR__ . '/../vendor/epiphany/src');
Epi::init('api', 'database');

require_once __DIR__ . '/../vendor/composer/autoload.php';
require_once __DIR__ . '/../lib/DatabaseFactory.php';
require_once __DIR__ . '/../lib/Cors.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/RecordRepository.php';
require_once __DIR__ . '/../controllers/AuthApi.php';
require_once __DIR__ . '/../controllers/RecordsApi.php';

DatabaseFactory::loadEnv();
Cors::handle($_ENV['CORS_ORIGINS'] ?? '*');
DatabaseFactory::connectFromEnv();

getApi()->post('/auth/login.json', ['AuthApi', 'login'], EpiApi::external);
getApi()->post('/auth/refresh.json', ['AuthApi', 'refresh'], EpiApi::external);
getApi()->post('/auth/logout.json', ['AuthApi', 'logout'], EpiApi::external);
getApi()->get('/auth/me.json', ['AuthApi', 'me'], EpiApi::external);

getApi()->get('/records.json', ['RecordsApi', 'list'], EpiApi::external);
getApi()->post('/records.json', ['RecordsApi', 'create'], EpiApi::external);
getApi()->get('/records/(\d+)\.json', ['RecordsApi', 'get'], EpiApi::external);
getApi()->put('/records/(\d+)\.json', ['RecordsApi', 'update'], EpiApi::external);
getApi()->delete('/records/(\d+)\.json', ['RecordsApi', 'delete'], EpiApi::external);

getRoute()->run();
