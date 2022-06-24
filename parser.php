<?php
$output = "";


function println($input)
{
    echo($input);
    echo("\n");
}

function addLine($input, $target)
{
    $target = $target . $input . "\n";
    return $target;
}

function removeComment($line)
{
    $pattern = '/#.*/';
    return preg_replace($pattern, '', $line);
}

function reduceWhitespaces($line)
{
    $pattern = '/[ \t]+/';
    $line = preg_replace($pattern, ' ', $line);
    $pattern = '/[ \t]+$/';
    $line = preg_replace($pattern, '', $line);
    return $line;

}

/*
function replaceEscapeSequences($string)
{
    $pattern = '/\\\\\d\d\d/';
    if (preg_match($pattern, $string, $matches)) {
        $sequence = $matches[0];
        $sequence = str_replace("\\", "", $sequence);
        $sequence = intval($sequence, 10);
        $sequence = chr($sequence);
        $string = preg_replace($pattern, $sequence, $string);
    }
    return $string;
}*/

function decodeVariables($string)
{


    $pattern = '/[\w]+@/';
    if (preg_match($pattern, $string, $type) == false)
        exit(22);

    if ($type[0] == "int@") {
        $pattern = '/^int@/';
        return preg_replace($pattern, "", $string);
    }

    if ($type[0] == "bool@") {
        $pattern = '/^bool@/';
        return preg_replace($pattern, "", $string);
    }

    if ($type[0] == "string@") {
        $pattern = '/^string@/';
        return preg_replace($pattern, "", $string);
    }

    return $string;


}

function replaceIllegalSymbols($string)
{
//replace &
    $pattern = '/&(?!(quot|apos|lt|gt|amp))/';
    $replacement = '&amp;';
    $string = preg_replace($pattern, $replacement, $string);

    // replace <
    $pattern = '/</';
    $replacement = '&lt;';
    $string = preg_replace($pattern, $replacement, $string);

    // replace >
    $pattern = '/>/';
    $replacement = '&gt;';
    $string = preg_replace($pattern, $replacement, $string);

    // replace "
    $pattern = '/\"/';
    $replacement = '&quot;';
    $string = preg_replace($pattern, $replacement, $string);

    // replace '
    $pattern = '/\'/';
    $replacement = '&apos;';
    $string = preg_replace($pattern, $replacement, $string);

    return $string;
}


function compareVarNameSyntax($name)
{

    if (preg_match('/(LF|GF|TF)@[a-zA-Z#&_%!?\-*$][a-zA-Z#&*$0-9]*\z/', $name)) {
        return 0;
    } else {
        return 23;
    }
}

function compareLabelNameSyntax($name)
{

    if (preg_match('/[a-zA-Z#&_%!?\-*$][a-zA-Z#&*$0-9]*\z/', $name)) {
        return 0;
    } else {
        return 23;
    }
}

function compareTypeNameSyntax($name)
{

    if (preg_match('/(string)|(bool)|(nil)|(int)\z/', $name)) {
        return 0;
    } else {
        return 23;
    }
}


function compareSymNameSyntax($name)
{
    $matches = null;
    if (preg_match('/(LF|GF|TF)@[a-zA-Z#&_%!?\-*$][a-zA-Z#&*$0-9]*\z/', $name, $matches)) {
        return 0;
    }


    if (preg_match('/string@(([^\s\\\#]+)|(\\\\\d{3})+)*\z/', $name, $matches)) {
        return 0;
    }

    if (preg_match('/(int)@[+-]?[0-9]*\z/', $name, $matches)) {
        return 0;
    }


    if (preg_match('/(bool)@(true|false)\z/', $name, $matches)) {
        return 0;
    }

    return 23;
}

function compareSymType($type, $output)
{
    if (compareSymNameSyntax($type) == 0) {
        if (preg_match('/(LF|GF|TF)@/', $type)) {
            $type = decodeVariables($type);
            $output = addLine("        <arg1 type=\"var\">$type</arg1>", $output);
            return $output;
        } elseif (preg_match('/(int)@/', $type)) {
            $type = decodeVariables($type);
            $output = addLine("        <arg1 type=\"int\">$type</arg1>", $output);
            return $output;
        } elseif (preg_match('/(bool)@/', $type)) {
            $type = decodeVariables($type);
            $output = addLine("        <arg1 type=\"bool\">$type</arg1>", $output);
            return $output;
        } elseif (preg_match('/(string)@/', $type)) {
            //$type = replaceEscapeSequences($type);
            $type = replaceIllegalSymbols($type);
            $type = decodeVariables($type);
            $output = addLine("        <arg1 type=\"string\">$type</arg1>", $output);
            return $output;
        }
    } else {
        exit(23);
    }
    return $output;
}


ini_set('display_errors', 'stderr');


if ($argc > 1) {
    if ($argv[1] == "--help") {
        println("usage: parser.php arg1 arg2\n arg1 = input code STDIN \n arg2 = optional argument used for --help which prints out help \n");
        exit(0);
    }
}


$output = addLine('<?xml version="1.0" encoding="UTF-8"?>', $output);

//$file = fopen("test.src", "r");
$header = false;
$order = 1;


while ($line = fgets(STDIN)) {


    $line = removeComment($line);
    $line = reduceWhitespaces($line);
    $line = trim($line, " \t\n\v");


    if (empty($line)) {
        continue;
    }

    if (!$header) {
        if (strcmp(strtoupper($line), ".IPPCODE21") == 0) {
            $header = true;
            $output = addLine("<program language=\"$line\">", $output);
            continue;
        } else {
            exit(21);
        }

    }


    $splitted = explode(' ', $line);
    $splitted[0] = strtoupper($splitted[0]);
    $numberOfSplits = count($splitted);


    switch ($splitted[0]) {
        //bez neterminálu
        case 'CREATEFRAME':
        case 'PUSHFRAME':
        case 'POPFRAME':
        case 'RETURN':
        case 'BREAK':
            $output = addLine("    <instruction order=\"$order\" opcode=\"$splitted[0]\">", $output);
            $output = addLine("    </instruction>", $output);
            if ($numberOfSplits > 1)
                exit(23);
            $order++;
            break;
        //neterminal <var>
        case 'DEFVAR':
        case 'POPS':
            if ($numberOfSplits > 2)
                exit(23);
            $output = addLine("    <instruction order=\"$order\" opcode=\"$splitted[0]\">", $output);
            if (compareVarNameSyntax($splitted[1]) == 0) {
                $splitted[1] = decodeVariables($splitted[1]);
                $output = addLine("        <arg1 type=\"var\">$splitted[1]</arg1>", $output);
            } else {
                exit(23);
            }
            $output = addLine("    </instruction>", $output);
            $order++;
            break;
        //neterminal <label>
        case 'LABEL':
        case 'CALL':
        case 'JUMP':
            if ($numberOfSplits > 2)
                exit(23);
            $output = addLine("    <instruction order=\"$order\" opcode=\"$splitted[0]\">", $output);
            if (compareLabelNameSyntax($splitted[1]) == 0)
                $output = addLine("        <arg1 type=\"label\">$splitted[1]</arg1>", $output);
            else
                exit(23);
            $output = addLine("    </instruction>", $output);
            $order++;
            break;
        //neterminal <sym>
        case 'DPRINT':
        case 'PUSHS':
        case 'WRITE':
        case 'EXIT':
            if ($numberOfSplits > 2)
                exit(23);
            $output = addLine("    <instruction order=\"$order\" opcode=\"$splitted[0]\">", $output);
            if (compareSymNameSyntax($splitted[1]) == 0) {
                $output = compareSymType($splitted[1] , $output);
            } else {
                exit(23);
            }

            $output = addLine("    </instruction>", $output);
            $order++;
            break;

        //neterminaly <var> <sym>
        case 'INT2CHAR':
        case 'STRLEN':
        case 'MOVE':
        case 'TYPE':
            if ($numberOfSplits > 3)
                exit(23);
            $output = addLine("    <instruction order=\"$order\" opcode=\"$splitted[0]\">", $output);
            if (compareVarNameSyntax($splitted[1]) == 0) {
                $splitted[1] = decodeVariables($splitted[1]);
                $output = addLine("        <arg1 type=\"var\">$splitted[1]</arg1>", $output);
            } else
                exit(23);
            if (compareSymNameSyntax($splitted[2]) == 0) {
                $output = compareSymType($splitted[2],  $output);
            } else {
                exit(23);
            }

            $output = addLine("    </instruction>", $output);
            $order++;
            break;
        //neterminály <var> <type>
        case 'READ':
            if ($numberOfSplits > 3)
                exit(23);
            $output = addLine("    <instruction order=\"$order\" opcode=\"$splitted[0]\">", $output);
            if (compareVarNameSyntax($splitted[1]) == 0) {
                $splitted[1] = decodeVariables($splitted[1]);
                $output = addLine("        <arg1 type=\"var\">$splitted[1]</arg1>", $output);
            } else
                exit(23);
            if (compareTypeNameSyntax($splitted[2]) == 0) {
                $output = addLine("        <arg2 type=\"type\">$splitted[2]</arg2>", $output);
            } else
                exit(23);
            $output = addLine("    </instruction>", $output);
            $order++;
            break;
        //neterminaly <var> <sym1> <sym2>
        case 'STRI2INT':
        case 'GETCHAR':
        case 'SETCHAR':
        case 'CONCAT':
        case 'AND':
        case 'OR':
        case 'NOT':
        case 'IDIV':
        case 'ADD':
        case 'SUB':
        case 'MUL':
        case 'LT':
        case 'GT':
        case 'EQ':
            if ($numberOfSplits > 4)
                exit(23);
            $output = addLine("    <instruction order=\"$order\" opcode=\"$splitted[0]\">", $output);
            if (compareVarNameSyntax($splitted[1]) == 0) {
                $splitted[1] = decodeVariables($splitted[1]);
                $output = addLine("        <arg1 type=\"var\">$splitted[1]</arg1>", $output);
            } else
                exit(23);
            if (compareSymNameSyntax($splitted[2]) == 0) {
                $output = compareSymType($splitted[2], $output);
            } else {
                exit(23);
            }
            if (compareSymNameSyntax($splitted[3]) == 0) {
                $output = compareSymType($splitted[3], $output);
            } else {
                exit(23);
            }
            $output = addLine("    </instruction>", $output);
            $order++;
            break;
        //neterminaly <label> <sym1> <sym2>
        case 'JUMPIFEQ':
        case 'JUMPIFNEQ':
            if ($numberOfSplits > 4)
                exit(23);
            $output = addLine("    <instruction order=\"$order\" opcode=\"$splitted[0]\">", $output);
            if (compareLabelNameSyntax($splitted[1]) == 0) {
                $output = addLine("        <arg1 type=\"label\">$splitted[1]</arg1>", $output);
            } else {
                exit(23);
            }
            if (compareSymNameSyntax($splitted[2]) == 0) {
                $output = compareSymType($splitted[2], $output);
            } else {
                exit(23);
            }
            if (compareSymNameSyntax($splitted[3]) == 0) {
                $output = compareSymType($splitted[3], $output);
            } else {
                exit(23);
            }
            $output = addLine("    </instruction>", $output);
            break;

        default:
            exit(23);
    }
}
$output = addLine("</program>", $output);
echo($output);
exit(0);
?>