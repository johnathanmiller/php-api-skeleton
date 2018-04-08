<?php

// OAuth2
$app->post('/oauth/token', 'OAuth2:token')->add('APIKey')->add('RateLimiter');

// Users
$app->post('/users', 'User:createUser')->add('APIKey')->add('RateLimiter');
$app->get('/users/{id}', 'User:getUser')->add('OAuth2Auth')->add('APIKey')->add('RateLimiter');
$app->put('/users/{id}', 'User:updateUser')->add('OAuth2Auth')->add('APIKey')->add('RateLimiter');
$app->delete('/users/{id}', 'User:deleteUser')->add('APIKey')->add('RateLimiter');