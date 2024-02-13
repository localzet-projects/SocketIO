<?php

/**
 * @package     SocketIO Engine
 * @link        https://localzet.gitbook.io
 *
 * @author      localzet <creator@localzet.ru>
 *
 * @copyright   Copyright (c) 2018-2020 Zorin Projects
 * @copyright   Copyright (c) 2020-2022 NONA Team
 *
 * @license     https://www.localzet.ru/license GNU GPLv3 License
 */

namespace localzet\SocketIO\Engine\Transports;

use localzet\SocketIO\Debug;

class PollingJsonp extends Polling
{
    public $head = null;
    public $foot = ');';

    public function __construct($req)
    {
        $this->head = '___eio[' . (isset($req['_query']['j']) ? preg_replace('/[^0-9]/', '', $req['_query']['j']) : '') . '](';
        Debug::debug('PollingJsonp __construct');
    }

    public function __destruct()
    {
        Debug::debug('PollingJsonp __destruct');
    }

    public function onData($data)
    {
        $parsed_data = null;
        parse_str($data, $parsed_data);
        $data = $parsed_data['d'];
        call_user_func([$this, 'parent::onData'], preg_replace('/\\\\n/', '\\n', $data));
    }

    public function doWrite($data): void
    {
        $js = json_encode($data);

        $data = $this->head . $js . $this->foot;

        // explicit UTF-8 is required for pages not served under utf
        $headers = [
            'Content-Type' => 'text/javascript; charset=UTF-8',
            'Content-Length' => strlen($data),
            'X-XSS-Protection' => '0'
        ];
        if (empty($this->res)) {
            echo new Exception('empty $this->res');
            return;
        }
        $this->res->writeHead(200, '', $this->headers($headers));
        $this->res->end($data);
    }

    public function headers(array $headers = []): array
    {
        $listeners = $this->listeners('headers');
        foreach ($listeners as $listener) {
            $listener($headers);
        }
        return $headers;
    }
}
