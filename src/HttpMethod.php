<?php

declare(strict_types=1);

namespace HackRouting;

final class HttpMethod {
    private function __construct() {}

    public const HEAD = 'HEAD';
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';
    public const OPTIONS = 'OPTIONS';
    public const PURGE = 'PURGE';
    public const TRACE = 'TRACE';
    public const CONNECT = 'CONNECT';
    public const REPORT = 'REPORT';
    public const LOCK = 'LOCK';
    public const UNLOCK = 'UNLOCK';
    public const COPY = 'COPY';
    public const MOVE = 'MOVE';
    public const MERGE = 'MERGE';
    public const NOTIFY = 'NOTIFY';
    public const SUBSCRIBE = 'SUBSCRIBE';
    public const UNSUBSCRIBE = 'UNSUBSCRIBE';
}
