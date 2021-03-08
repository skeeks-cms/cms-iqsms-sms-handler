<?php
return [
    'components' => [
        'cms' => [
            'smsHandlers'             => [
                \skeeks\cms\sms\iqsms\IqsmsHandler::class
            ]
        ],
    ],
];