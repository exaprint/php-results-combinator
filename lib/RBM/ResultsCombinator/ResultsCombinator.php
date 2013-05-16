<?php

namespace RBM\ResultsCombinator;

class ResultsCombinator
{
    const DEFAULT_SEPARATOR = '.';

    protected $_identifier;

    protected $_separator = self::DEFAULT_SEPARATOR;

    protected $_groups = [];

    protected $_class = '\stdClass';

    protected $_subClasses = [];

    protected $_methods = [];

    /**
     * @param $class
     */
    public function setClass($class)
    {
        $this->_class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->_class;
    }

    /**
     * @param $property
     * @param $class
     */
    public function addSubClass($property, $class)
    {
        $this->_subClasses[$property] = $class;
    }

    /**
     * @param $object
     * @param $methodName
     * @param $args
     * @param string $scope
     */
    public function addMethod($object, $methodName, $args)
    {
        if (!isset($this->_methods[$object])) {
            $this->_methods[$object] = [];
        }
        $this->_methods[$object][$methodName] = [
            "args" => $args,
            "exec" => [],
        ];
    }

    public function setIdentifier($identifier)
    {
        $this->_identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->_identifier;
    }

    public function setSeparator($separator)
    {
        $this->_separator = $separator;
    }

    public function getSeparator()
    {
        return $this->_separator;
    }

    public function addGroup($path, $identifier, $class = '\stdClass')
    {

        if (isset($this->_groups[$path])) {
            throw new \InvalidArgumentException("A group for path $path already exists");
        }

        $this->_groups[$path] = [
            "identifier" => $identifier,
            "class"      => $class,
        ];
    }

    public function process($rows)
    {
        $data   = [];
        $flat   = [];
        $sepLen = strlen($this->_separator);

        foreach ($rows as $row) {

            $identifierValue = $row[$this->_identifier];

            foreach ($row as $key => $value) {

                $buffer = "";
                $path   = "";

                if (!isset($data[$identifierValue])) {
                    $data[$identifierValue] = new $this->_class();
                    $flat[$identifierValue] = [];
                }

                $parent     = & $data[$identifierValue];
                $flatParent = & $flat[$identifierValue];

                // store the key length for iteration optim
                $keyLen = strlen($key);
                // iterate on chars
                $erase = false;

                $skip = false;
                for ($i = 0; $i < $keyLen; $i++) {
                    // if we find a separator
                    if (substr($buffer, -$sepLen) === $this->_separator) {
                        // remove the separator from the buffer
                        $buffer = substr($buffer, 0, -$sepLen);
                        // and concat to whole path
                        $path .= $buffer;
                        // if we find a group
                        if (isset($this->_groups[$path])) {
                            // we look up for the grouping column

                            // if the holder for group does not exist, create an array
                            if (!isset($parent->$buffer)) {
                                $parent->$buffer = [];
                            }

                            $groupKey = $path . $this->_separator . $this->_groups[$path]["identifier"];
                            // and fetch the value
                            $groupVal = $row[$groupKey];
                            // if the the value's not empty
                            if ($groupVal) {
                                // if the group isn't already created
                                $group     = & $parent->$buffer;
                                $flatGroup = & $flatParent[$buffer];
                                if (!isset($group[$groupVal])) {
                                    // get the class
                                    $groupClass = $this->_groups[$path]["class"];
                                    // and create
                                    $group[$groupVal]     = new $groupClass();
                                    $flatGroup[$groupVal] = [];
                                }
                                // make the parent sur group class
                                $parent     = & $group[$groupVal];
                                $flatParent = & $flatGroup[$groupVal];
                            } else {
                                $skip = true;
                            }
                        } else {
                            // subclass
                            if (!isset($parent->$buffer)) {
                                $cls                 = (isset($this->_subClasses[$path])) ? $this->_subClasses[$path] : '\stdClass';
                                $parent->$buffer     = new $cls();
                                $flatParent[$buffer] = [];
                            }

                            if (isset($this->_methods[$path])) {
                                $method = & $this->_methods[$path];
                                foreach ($method as $name => $detail) {
                                    $method[$name]["exec"][] = [
                                        "target"  => & $parent->$buffer,
                                        "context" => & $flat[$identifierValue],
                                    ];
                                }
                            }
                            $parent     = & $parent->$buffer;
                            $flatParent = & $flatParent[$buffer];
                        }

                        $path .= $this->_separator;
                        $buffer = "";
                    }
                    $buffer .= $key[$i];
                }

                if (!$skip && (is_a($parent, '\stdClass') || property_exists(get_class($parent), $buffer))) {
                    $parent->$buffer = $value;
                }

                $flatParent[$buffer] = $value;
            }
        }

        foreach ($this->_methods as $method) {
            foreach ($method as $methodName => $methodDetail) {
                foreach ($methodDetail["exec"] as $exec) {
                    $target  = $exec["target"];
                    $context = $exec["context"];
                    $params  = $this->_getParams($context, $methodDetail['args']);
                    call_user_func_array([$target, $methodName], $params);
                }
            }
        }

        return $data;
    }

    protected function _getParams($context, $args)
    {
        $values = [];
        $sepLen = strlen($this->getSeparator());
        foreach($args as $arg){
            $keyLen = strlen($arg);
            $buffer = "";
            $parent = & $context;

            for ($i = 0; $i < $keyLen; $i++) {
                // if we find a separator
                if (substr($buffer, -$sepLen) === $this->getSeparator()) {
                    // remove the separator from the buffer
                    $buffer = substr($buffer, 0, -$sepLen);
                    $parent = & $parent[$buffer];
                    $buffer = "";
                }
                $buffer .= $arg[$i];
            }
            $values[] = $parent[$buffer];
        }
        return $values;
    }

    /**
     * @deprecated
     * @param $rows
     * @param $identifier
     * @param array $groups
     * @param string $separator
     * @return array
     */
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