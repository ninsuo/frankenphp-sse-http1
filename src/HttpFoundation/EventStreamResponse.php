<?php

/*
 * Copyright (C) 2021 Squadra Software - All Rights Reserved
 *
 * @author alain tiemblo <alain@wetransform.com>
 */

namespace App\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventStreamResponse extends StreamedResponse
{
    /**
     * @param callable $callback A callable that returns the JSON data to be sent to the client.
     */
    public function __construct(callable $callback,
        $status = Response::HTTP_OK,
        array $headers = [],
        int $sleepBetweenChecks = 1,
        int $cycles = 900)
    {
        ignore_user_abort(true);

        parent::__construct(function () use ($callback, $sleepBetweenChecks, $cycles) {
            $ping = 0;
            while (true) {
                // Polls for new events
                if ($data = $callback()) {
                    if (!$this->data($data)) {
                        break;
                    }

                    $ping = 0;
                } else {
                    sleep($sleepBetweenChecks);

                    if ($cycles-- === 0) {
                        break;
                    }

                    // Checks whether client is still alive
                    if (30 === $ping++) {
                        if (!$this->ping()) {
                            break;
                        }

                        $ping = 0;
                    }
                }
            }
        }, $status, array_merge($headers, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Access-Control-Allow-Origin' => '*',
        ]));
    }

    private function data(string $data): bool
    {
        // Ensures that beautified JSON is converted to single-line.
        $data = json_encode(json_decode($data));

        return $this->stream(sprintf("data:%s\n\n", $data));
    }

    private function ping(): bool
    {
        return $this->stream(": ping\n\n");
    }

    private function stream(string $data): bool
    {
        echo $data;

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();

        return CONNECTION_NORMAL === connection_status();
    }
}