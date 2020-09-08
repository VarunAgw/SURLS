<?php

/**
 * Insert your SURLS functions here.
 */

$surlsHandlers = [
    'hello' => function () {
        // This will run on example.com/hello
        echo 'Hello World!';
        die;
    },

    'google' => function () {
        // This will run on example.com/google
        header('Location: http://www.boogle.com', true, 302);
        die;
    }
];
