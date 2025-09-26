<?php
// index.php - debugging with Xdebug

// 1. Notice / Warning: Using undefined variable
echo $undefinedVar;

// 2. Warning: Wrong number of arguments
function divide($a, $b) {
    return $a / $b;
}
echo divide(10); // missing 2nd argument

// 3. Fatal error: Calling undefined function
$result = add(5, 7); // add() is not defined yet
echo "Result: $result";

// 4. Parse error: uncomment to test syntax error
// echo "Hello"
