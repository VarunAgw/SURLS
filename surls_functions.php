<?php

function surls_handler_hello() {
    echo 'Hello World!';
}

function surls_handler_boogle() {
    header('Location: http://www.boogle.com', true, 302);
}
