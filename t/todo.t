#!/usr/bin/env php
<?php

require 'Test.php';

plan(6);

ok(1);
ok(2);

todo_start();
ok(0, "oh noes todo");
is("foo", "bar");
todo_end();

ok(3);
ok(4);

?>
