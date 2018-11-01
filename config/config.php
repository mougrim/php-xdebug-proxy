<?php
/**
 * @author Mougrim <rinat@mougrim.ru>
 */

namespace Mougrim\XdebugProxy;

return [
    'xdebugServer' => [
        'listen' => '127.0.0.1:9002',
    ],
    'ideServer' => [
        'defaultIde' => '127.0.0.1:9000',
        'predefinedIdeList' => [
            'idekey' => '127.0.0.1:9000',
        ],
    ],
    'ideRegistrationServer' => [
        'listen' => '127.0.0.1:9001',
    ],
];
