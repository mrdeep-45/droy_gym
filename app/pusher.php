<?php

use Pusher\Pusher;

if (!function_exists('push_to_channel')) {
    function push_to_channel($channel, $event, $data)
    {
        $pusher = new Pusher(
            '713b914b10219f63d205',
            '5fdfb3495e412d16d848',
            '2029158',
            [
                'cluster' => 'ap2',
                'useTLS' => true
            ]
        );

        return $pusher->trigger($channel, $event, $data);
    }
}
