<?php

declare(strict_types=1);

\header('X-my-custom-header:hello');

echo $_SERVER['HTTP_X_YOLO'];
