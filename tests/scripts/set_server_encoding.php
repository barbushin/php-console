<?php

iconv_set_encoding("internal_encoding", "' . $encoding . '");
iconv_set_encoding("output_encoding", "' . $encoding . '");
ini_set("mbstring.func_overload", 2);
ini_set("mbstring.internal_encoding", "' . $encoding . '");
ini_set("mbstring.http_output", "' . $encoding . '");
header("Content-Type: text/html; charset=' . $encoding . '");
