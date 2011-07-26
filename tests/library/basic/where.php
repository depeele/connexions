<?php
require_once('./bootstrap.php');

echo "<pre>\n";
echo "<h3>Where Checks</h3>\n";

$where = array('userId = ?'   => 1,
               'tagId IN (?)' => array(2,3,4,5),
               'itemId = ?'   => 9);

$expr = _whereExpr($where);

echo "where: "; print_r($where); echo "\n";

echo "expr: ";  print_r($expr);  echo "\n";


/***************************************************************************
 * Extracted from Zend_Db_Adapter_Abstract
 *
 */
    function _whereExpr($where)
    {
        if (empty($where)) {
            return $where;
        }
        if (!is_array($where)) {
            $where = array($where);
        }
        foreach ($where as $cond => &$term) {
            // is $cond an int? (i.e. Not a condition)
            if (is_int($cond)) {
                // $term is the full condition
                if ($term instanceof Zend_Db_Expr) {
                    $term = $term->__toString();
                }
            } else {
                // $cond is the condition with placeholder,
                // and $term is quoted into the condition
                $term = quoteInto($cond, $term);
            }
            $term = '(' . $term . ')';
        }

        $where = implode(' AND ', $where);
        return $where;
    }

    function quoteInto($text, $value)
    {
            return str_replace('?', quote($value, $type), $text);
    }

    function quote($value, $type = null)
    {
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = quote($val, $type);
            }
            return implode(', ', $value);
        }

        return _quote($value);
    }

    function _quote($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
    }
