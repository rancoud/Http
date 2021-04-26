<?php

header("X-my-custom-header:hello", true);

echo $_SERVER['HTTP_X_YOLO'];