<?php

return array(
    'test1' => function () {
        echo '<style>a{text-decoration: none;}</style>';
        echo '<h1>';
        echo '<marquee>';
        echo '<a href="https://en.wikipedia.org/wiki/Bill_Gates">Bill Gates</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '<a href="http://ben10.wikia.com/wiki/Vilgax">Vilgax</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '</marquee>';
        echo '</h1>';
    },
    'test2' => function () {
        header('Location: http://www.boogle.com', true, 302);
    },
);
?>
