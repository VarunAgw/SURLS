<?php

/**
 * Insert your SURLS functions here.
 * Make sure to create a dummy alias in GUI with the same name.
 */

// This will run on example.com/hello
function surls_handler_hello()
{
    echo 'Hello World!';
}

// This will run on example.com/google
function surls_handler_google()
{
    header('Location: http://www.boogle.com', true, 302);
}
