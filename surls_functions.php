<?php

// This will run on example.com/hello
function surls_handler_hello() {
    echo 'Hello World!';
}

// This will run on example.com/google
function surls_handler_google() {
    header('Location: http://www.boogle.com', true, 302);
}
