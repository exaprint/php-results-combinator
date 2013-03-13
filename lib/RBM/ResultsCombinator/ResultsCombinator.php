<?php

namespace RBM\ResultsCombinator;

class ResultsCombinator
{
    const DEFAULT_SEPARATOR = ".";

    public function combine($rows, $identifier, $groups = [], $separator = self::DEFAULT_SEPARATOR)
    {
        $data   = [];
        $sepLen = strlen($separator);

        foreach ($rows as $row) {

            $identifierValue = $row[$identifier];

            foreach ($row as $key => $value) {

                $buffer = "";
                $path   = "";

                if (!isset($data[$identifierValue])) {
                    $data[$identifierValue] = [];
                }

                $parent = & $data[$identifierValue];

                // store the key length for iteration optim
                $keyLen = strlen($key);

                for ($i = 0; $i < $keyLen; $i++) {
                    if (substr($buffer, -$sepLen) === $separator) {

                        $buffer = substr($buffer, 0, -$sepLen);

                        if (!isset($parent[$buffer])) {
                            $parent[$buffer] = [];
                        }

                        $path .= $buffer;

                        if (isset($groups[$path])) {
                            $groupKey = $path . $separator . $groups[$path];
                            $groupVal = $row[$groupKey];
                            if (!isset($parent[$buffer][$groupVal])) {
                                $parent[$buffer][$groupVal] = [];
                            }
                            $parent = & $parent[$buffer][$groupVal];
                        } else {
                            $parent = & $parent[$buffer];
                        }

                        $path .= $separator;
                        $buffer = "";
                    }
                    $buffer .= $key[$i];
                }
                $parent[$buffer] = $value;
            }
        }

        return $data;
    }
}