<?php
/** @file
 *
 *  Simple tests of condition parsing methods from 
 *  Connexions_Model_Mapper_DbTable (see MockDb below) as well as 
 *  SearchController term parsing.
 */

parseTest();
whereTest();
fullTest();

/*****************************************************************************
 *****************************************************************************
 *****************************************************************************/

function parseTest()
{
    $fields = array('name','desc');
    $tests  = array(
        'Alexander'                 => array(
            array('condition' => 'name=*',  'value' => 'Alexander'),
            array('condition' => '+|desc=*','value' => 'Alexander'),
        ),
        '*Alexander'                => array(
            array('condition' => 'name=$',  'value' => 'Alexander'),
            array('condition' => '+|desc=$','value' => 'Alexander'),
        ),
        'Alexander*'                => array(
            array('condition' => 'name=^',  'value' => 'Alexander'),
            array('condition' => '+|desc=^','value' => 'Alexander'),
        ),
        '*lexander*'                => array(
            array('condition' => 'name=$',  'value' => 'lexander*'),
            array('condition' => '+|desc=$','value' => 'lexander*'),
        ),
        'Alex*der'                  => array(
            array('condition' => 'name=*',  'value' => 'Alex*der'),
            array('condition' => '+|desc=*','value' => 'Alex*der'),
        ),
        'Alexander Bell'            => array(
            array('condition' => 'name=*',  'value' => 'Alexander'),
            array('condition' => '+|desc=*','value' => 'Alexander'),
            array('condition' => 'name=*',  'value' => 'Bell'),
            array('condition' => '+|desc=*','value' => 'Bell'),
        ),
        'Alexander +Bell'           => array(
            array('condition' => 'name=*',  'value' => 'Alexander'),
            array('condition' => '+|desc=*','value' => 'Alexander'),
            array('condition' => 'name=',   'value' => 'Bell'),
            array('condition' => '+|desc=', 'value' => 'Bell'),
        ),
        'Alexander -Bell'           => array(
            array('condition' => 'name=*',  'value' => 'Alexander'),
            array('condition' => '+|desc=*','value' => 'Alexander'),
            array('condition' => 'name!=*', 'value' => 'Bell'),
            array('condition' => '+desc!=*','value' => 'Bell'),
        ),
        'Alexander | Bell'          => array(
            array('condition' => 'name=*',  'value' => 'Alexander'),
            array('condition' => '+|desc=*','value' => 'Alexander'),
            array('condition' => '|name=*', 'value' => 'Bell'),
            array('condition' => '+|desc=*','value' => 'Bell'),
        ),
        'Alexander OR Bell'         => array(
            array('condition' => 'name=*',  'value' => 'Alexander'),
            array('condition' => '+|desc=*','value' => 'Alexander'),
            array('condition' => '|name=*', 'value' => 'Bell'),
            array('condition' => '+|desc=*','value' => 'Bell'),
        ),
        "'Alexander Bell'"          => array(
            array('condition' => 'name=*',  'value' => 'Alexander Bell'),
            array('condition' => '+|desc=*','value' => 'Alexander Bell'),
        ),
        "'Alexander\\'s Bell'"      => array(
            array('condition' => 'name=*',  'value' => "Alexander's Bell"),
            array('condition' => '+|desc=*','value' => "Alexander's Bell"),
        ),
        '"Alexander Bell"'          => array(
            array('condition' => 'name=*',  'value' => 'Alexander Bell'),
            array('condition' => '+|desc=*','value' => 'Alexander Bell'),
        ),
        'Alexander* Bell'           => array(
            array('condition' => 'name=^',  'value' => 'Alexander'),
            array('condition' => '+|desc=^','value' => 'Alexander'),
            array('condition' => 'name=*',  'value' => 'Bell'),
            array('condition' => '+|desc=*','value' => 'Bell'),
        ),
        '*Alexander Bell'           => array(
            array('condition' => 'name=$',  'value' => 'Alexander'),
            array('condition' => '+|desc=$','value' => 'Alexander'),
            array('condition' => 'name=*',  'value' => 'Bell'),
            array('condition' => '+|desc=*','value' => 'Bell'),
        ),
        "Alexander's Bell"          => array(
            array('condition' => 'name=*',  'value' => "Alexander's"),
            array('condition' => '+|desc=*','value' => "Alexander's"),
            array('condition' => 'name=*',  'value' => "Bell"),
            array('condition' => '+|desc=*','value' => "Bell"),
        ),
        "Alexander's Bell 'for me'" => array(
            array('condition' => 'name=*',  'value' => "Alexander's"),
            array('condition' => '+|desc=*','value' => "Alexander's"),
            array('condition' => 'name=*',  'value' => "Bell"),
            array('condition' => '+|desc=*','value' => "Bell"),
            array('condition' => 'name=*',  'value' => "for me"),
            array('condition' => '+|desc=*','value' => "for me"),
        ),
    );

    printf ("%d Parse Tests:\n", count($tests));

    $idex = 1;
    foreach ($tests as $term => $expected)
    {
        printf ("%2d: %s: ", $idex++, $term);

        $result = _parseTerms($term, $fields);

        if ($result != $expected)
        {
            echo "FAILED\n";
            print_r($result);
            echo "\n\n";
        }
        else
        {
            echo "success\n";
        }
    }
    echo "========================================================\n";
}

function whereTest()
{
    $tests  = array(
        // 0
        array(
            'test'    => array(
                'name='     => 'Alexander',
            ),
            'expected'  => array(
                'name = ?'  => 'Alexander',
            ),
        ),
        // 1
        array(
            'test'    => array(
                'name='     => 'Alexander',
                'desc='     => 'Bell',
            ),
            'expected'  => array(
                'name = ?'  => 'Alexander',
                'desc = ?'  => 'Bell',
            ),
        ),
        // 2
        array(
            'test'    => array(
                'name='     => 'Alexander',
                '|desc='    => 'Bell',
            ),
            'expected'  => array(
                'name = ?'  => 'Alexander',
                '|desc = ?' => 'Bell',
            ),
        ),
        // 3
        array(
            'test'    => array(
                'name='     => 'Alexander',
            ),
            'expected'  => array(
                'name = ?'  => 'Alexander',
            ),
        ),
        // 4
        array(
            'test'    => array(
                'name!='    => 'Alexander',
            ),
            'expected'  => array(
                'name != ?' => 'Alexander',
            ),
        ),
        // 5
        array(
            'test'    => array(
                'name >'    => 'Alexander',
            ),
            'expected'  => array(
                'name > ?'  => 'Alexander',
            ),
        ),
        // 6
        array(
            'test'    => array(
                'name <'    => 'Alexander',
            ),
            'expected'  => array(
                'name < ?'  => 'Alexander',
            ),
        ),
        // 7
        array(
            'test'    => array(
                'name >='   => 'Alexander',
            ),
            'expected'  => array(
                'name >= ?' => 'Alexander',
            ),
        ),
        // 8
        array(
            'test'    => array(
                'name <='   => 'Alexander',
            ),
            'expected'  => array(
                'name <= ?' => 'Alexander',
            ),
        ),
        // 9
        array(
            'test'    => array(
                'name=^'    => 'Alexander',
            ),
            'expected'  => array(
                'name LIKE ?'   => 'Alexander%',
            ),
        ),
        // 10
        array(
            'test'    => array(
                'name=*'    => 'Alexander',
            ),
            'expected'  => array(
                'name LIKE ?'   => '%Alexander%',
            ),
        ),
        // 11
        array(
            'test'    => array(
                'name=$'    => 'Alexander',
            ),
            'expected'  => array(
                'name LIKE ?'   => '%Alexander',
            ),
        ),
        // 12
        array(
            'test'    => array(
                'name!=^'   => 'Alexander',
            ),
            'expected'  => array(
                'name NOT LIKE ?'   => 'Alexander%',
            ),
        ),
        array(
            'test'    => array(
                'name!=*'    => 'Alexander',
            ),
            'expected'  => array(
                'name NOT LIKE ?'   => '%Alexander%',
            ),
        ),
        array(
            'test'    => array(
                'name!=$'   => 'Alexander',
            ),
            'expected'  => array(
                'name NOT LIKE ?'   => '%Alexander',
            ),
        ),
        array(
            'test'    => array(
                'name='     => array('Alexander', 'Bell'),
            ),
            'expected'  => array(
                'name IN ?' => array('Alexander', 'Bell'),
            ),
        ),
        array(
            'test'    => array(
                'tag='          => array('one', 'two'),
                'userId='       => array(1, 2, 3, 4),
                'updated='      => '2010-09-02 11:51:00',
            ),
            'expected'  => array(
                'tag IN ?'      => array('one', 'two'),
                'userId IN ?'   => array(1, 2, 3, 4),
                'updated = ?'   => '2010-09-02 11:51:00',
            ),
        ),
        // */
        array(
            'test'    => array(
                'isFavorite=0',
            ),
            'expected'  => array(
                'isFavorite=0'  => null,
            ),
        ),
        array(
            'test'    => array(
                'tag='          => array('one', 'two'),
            ),
            'expected'  => array(
                "tag IN ?"      => array('one', 'two'),
            ),
        ),
        array(
            'test'    => array(
                'tag!='         => array('one', 'two'),
            ),
            'expected'  => array(
                "tag NOT IN ?"  => array('one', 'two'),
            ),
        ),
        array(
            'test'    => array(
                'tag=*'         => array('one', 'two'),
            ),
            'expected'  => array(
                "((tag LIKE '%one%') OR (tag LIKE '%two%'))"    => null,
            ),
        ),
        array(
            'test'    => array(
                'tag!=*'        => array('one', 'two'),
            ),
            'expected'  => array(
                "((tag NOT LIKE '%one%') AND (tag NOT LIKE '%two%'))" => null,
            ),
        ),
    );

    printf ("%d Where Tests:\n", count($tests));

    $db   = new MockDb();
    $idex = 1;
    foreach ($tests as $idex => $config)
    {
        $test = var_export($config['test'], true);
        $test = preg_replace("/\s*[\n\r]+\s*/ms", ' ', $test);
        $test = preg_replace('/^array \(/',       '[', $test);
        $test = preg_replace('/\)$/',             ']', $test);

        printf ("%2d: %s: ", $idex++, $test);

        $where = $db->_where($config['test']);

        if ($where != $config['expected'])
        {
            echo "FAILED\n";
            print_r($where);

            echo "Expected:\n";
            print_r($config['expected']);
            echo "\n\n";
        }
        else
        {
            echo "success\n";
        }
    }

    echo "========================================================\n";
}

function fullTest()
{
    $fields = array('name','desc');
    $tests  = array(
      'Alexander'                 => array(
        "((name LIKE '%Alexander%') OR (desc LIKE '%Alexander%'))"  => null,
      ),
      '*Alexander'                => array(
        "((name LIKE '%Alexander') OR (desc LIKE '%Alexander'))"    => null,
      ),
      'Alexander*'                => array(
        "((name LIKE 'Alexander%') OR (desc LIKE 'Alexander%'))"    => null,
      ),
      '*lexander*'                => array(
        "((name LIKE '%lexander%') OR (desc LIKE '%lexander%'))"    => null,
      ),
      'Alex*der'                  => array(
        "((name LIKE '%Alex%der%') OR (desc LIKE '%Alex%der%'))"    => null,
      ),

      'Alexander Bell'            => array(
        "((name LIKE '%Alexander%') OR (desc LIKE '%Alexander%'))"  => null,
        "((name LIKE '%Bell%') OR (desc LIKE '%Bell%'))"            => null,
      ),
      'Alexander +Bell'           => array(
        "((name LIKE '%Alexander%') OR (desc LIKE '%Alexander%'))"  => null,
        "((name = 'Bell') OR (desc = 'Bell'))"                      => null,
      ),
      'Alexander -Bell'           => array(
        "((name LIKE '%Alexander%') OR (desc LIKE '%Alexander%'))"  => null,
        "((name NOT LIKE '%Bell%') AND (desc NOT LIKE '%Bell%'))"   => null,
      ),
      'Alexander | Bell'          => array(
        "((name LIKE '%Alexander%') OR (desc LIKE '%Alexander%'))"  => null,
        "|((name LIKE '%Bell%') OR (desc LIKE '%Bell%'))"           => null,
      ),
      'Alexander OR Bell'         => array(
        "((name LIKE '%Alexander%') OR (desc LIKE '%Alexander%'))"  => null,
        "|((name LIKE '%Bell%') OR (desc LIKE '%Bell%'))"           => null,
      ),
      "'Alexander Bell'"          => array(
        "((name LIKE '%Alexander Bell%') OR (desc LIKE '%Alexander Bell%'))"
                                                                    => null,
      ),
      // 7
      "'Alexander\\'s Bell'"      => array(
        "((name LIKE '%Alexander\'s Bell%') OR (desc LIKE '%Alexander\'s Bell%'))"
                                                                    => null,
      ),
      '"Alexander Bell"'          => array(
        "((name LIKE '%Alexander Bell%') OR (desc LIKE '%Alexander Bell%'))"
                                                                    => null,
      ),
      // 9
      'Alexander* Bell'           => array(
        "((name LIKE 'Alexander%') OR (desc LIKE 'Alexander%'))"    => null,
        "((name LIKE '%Bell%') OR (desc LIKE '%Bell%'))"            => null,
      ),
      "Alexander's Bell"          => array(
        "((name LIKE '%Alexander\'s%') OR (desc LIKE '%Alexander\'s%'))"
                                                                    => null,
        "((name LIKE '%Bell%') OR (desc LIKE '%Bell%'))"            => null,
      ),
      "Alexander's Bell 'for me'" => array(
        "((name LIKE '%Alexander\'s%') OR (desc LIKE '%Alexander\'s%'))"
                                                                    => null,
        "((name LIKE '%Bell%') OR (desc LIKE '%Bell%'))"            => null,
        "((name LIKE '%for me%') OR (desc LIKE '%for me%'))"        => null,
      ),
    );

    printf ("%d Full Tests:\n", count($tests));

    $db   = new MockDb();
    $idex = 1;
    foreach ($tests as $term => $expected)
    {
        printf ("%2d: %s: ", $idex++, $term);

        $result = _parseTerms($term, $fields);

        /*
        printf ("\n %s: parsed terms:\n", $term);
        print_r($result);
        // */
    
        $where  = $db->_where($result);

        /*
        printf ("\n %s: where:\n", $term);
        print_r($where);
    
        echo "================================================\n";
        // */
    
        if ($where != $expected)
        {
            echo "FAILED\n";
            print_r($where);

            echo "Expected:\n";
            print_r($expected);
            echo "\n\n";
        }
        else
        {
            echo "success\n";
        }
    }

    echo "========================================================\n";
}

/*****************************************************************************
 *****************************************************************************
 *****************************************************************************/

/****************************************************************************
 *  From class SearchController
 *
 */

    /** @brief  Given a search term (string) and array of fields to apply the 
     *          term against, construct a representative array of 
     *          'condition/value' pairs.
     *  @param  terms       The search term string;
     *  @param  fields      The array of fields to apply the term against;
     *
     *  The search term syntax:
     *      - Quoted phrase;
     *      - Term exclusion by prefixing the term with '-';
     *      - Wildcard '*';
     *      - Exact match '+';
     *      - The OR operator: '|' 'OR';
     *      - By default, all terms are combined with AND;
     *
     *  @return An array of 'condition/value' pairs.
     */
    function _parseTerms($terms, array $fields)
    {
        $re = '/(?:'
            .    '(\(|\))'              // ( or )   => ( or )
            .'|'.'"(.*?[^\\\])"'        // "term"   => term
            .'|'.'\'(.*?[^\\\])\''      // 'term'   => term
            .'|'.'\s*([^\s\)]+)\s*'     // \sterm\s => term
            . ')/';
        preg_match_all($re, $terms, $matches);
    
        
        $splitTerms = array();
        $nParts = count($matches[0]);
        for ($idex = 0; $idex < $nParts; $idex++)
        {
            if (! empty($matches[2][$idex]))
                array_push($splitTerms, str_replace('\\','',
                           $matches[2][$idex]));
            else if (! empty($matches[3][$idex]))
                array_push($splitTerms, str_replace('\\','',
                           $matches[3][$idex]));
            else if (! empty($matches[4][$idex]))
                array_push($splitTerms, str_replace('\\','',
                           $matches[4][$idex]));
            else if (! empty($matches[1][$idex]))
                array_push($splitTerms, str_replace('\\','',
                           $matches[1][$idex]));
        }
    
        // Now, combine the splitTerms and fields
        $search = array();
        $nTerms = count($splitTerms) - 1;
        $op     = '';
        foreach ($splitTerms as $term)
        {
            $combiner = '+|';
            if (($term === '|') || ($term === 'OR'))
            {
                $op = '|';
                continue;
            }
            else if ($term[0] === '+')
            {
                // Exactly
                $comp = '=';
                $term = substr($term,1);
            }
            else if ($term[0] === '-')
            {
                // Does NOT contain
                $comp     = '!=*';
                $combiner = '+';
                $term     = substr($term,1);
            }
            else if ($term[0] === '*')
            {
                // Ends with
                $comp = '=$';
                $term = substr($term,1);
            }
            else if ($term[strlen($term)-1] === '*')
            {
                // Begins with
                $comp = '=^';
                $term = substr($term,0,-1);
            }
            else
            {
                // Contains
                $comp = '=*';
            }
    
            foreach ($fields as $idex => $field)
            {
                $condition = ($idex === 0
                                ? $op      . $field . $comp
                                : $combiner. $field . $comp);
    
                array_push($search, array('condition' => $condition,
                                          'value'     => $term));
            }
    
            $op   = '';
        }
    
        return $search;
    }

/****************************************************************************
 *  From class Connexions_Model_Mapper_DbTable
 *
 */
Class MockAdapter
{
    public function quote($value, $type = null)
    {
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val, $type);
            }

            return '('. implode(', ', $value) .')';
        }

        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf("%F", $value);
        }

        return "'". addcslashes($value, "\000\n\r\\'\"\032") ."'";
    }

    public function quoteInto($text, $value, $type = null, $count = null)
    {
        return str_replace('?', $this->quote($value, $type), $text);
    }
}

Class MockAccessor
{
    protected $_adapter = null;

    public function __construct()
    {
        $this->_adapter = new MockAdapter();
    }

    public function getAdapter()
    {
        return $this->_adapter;
    }
}

class MockDb
{
    protected   $_accessor  = null;

    public function __construct()
    {
        $this->_accessor = new MockAccessor();
    }

    public function getAccessor()
    {
        return $this->_accessor;
    }

    /*************************************************************************
     * The following methods are taken from
     *  Connexions_Model_Mapper_DbTable
     *
     */

    /** @brief  Given a condition string and value, generate an appropriate
     *          'where' condition array entry.
     *  @param  condition   The condition string.
     *  @param  value       The desired value.
     *
     *  'condition' can have the form:
     *      prefix field op
     *
     *  'prefix' may be:
     *      |   - this is an 'OR' condition as opposed to the default 'AND';
     *      +   - combine/group this condition with the previous using 'AND';
     *      +|  - combine/group this condition with the previous using 'OR';
     *
     *  'field' names the target database field
     *  'op' may be:
     *      =   - [default] equivalence match;
     *      !=  - NOT equal;
     *      >   - greater than;
     *      >=  - greater than or equal to;
     *      <   - less than;
     *      <=  - less than or equal to;
     *
     *  For String values, the follow are also valid for 'op':
     *      =*  - contains 'value';
     *      =^  - begins with 'value';
     *      =$  - ends   with 'value;
     *
     *      !=* - does NOT contain 'value';
     *      !=^ - does NOT begin with 'value';
     *      !=$ - does NOT end   with 'value;
     *
     *
     *  If 'value' is an array, it indicates that any of the values is 
     *  acceptable.
     *
     *  For array values, if the operator is '=' or '!=', the condition will be
     *  reduced using 'IN' or 'NOT IN'; otherwise, it will be converted to a 
     *  single, complex 'where' condition with one per value, pre-bound and 
     *  database quoted, all combined via 'OR' ('AND' for NOT conditions).  
     *
     *  Note: This REQUIRES _flattenConditions() to convert a set of conditions 
     *        generate via _whereCondition() to a single, flat, pre-bound, 
     *        database-specific WHERE condition string.
     *
     *  @return An array of { condition: %condition%,
     *                        value:     %value% } or null if invalid.
     */
    /*protected*/ function _whereCondition($condition, $value)
    {
        if (preg_match(
                //    prefix  field    op
                '/^\s*(\|)?\s*(.*?)\s*(!=[\^*$]?|[<>]=?|=[\^*$]?)?\s*[?]?\s*$/',
                $condition, $match))
        {
            /*
            Connexions::log("Connexions_Model_Mapper_DbTable::_whereCondition()"
                            .   ": condition match [ %s ]",
                            Connexions::varExport($match));
            // */

            /* match[1] == empty or '|'
             * match[2] == field name
             * match[3] == condition operator
             */
            $prefix    = $match[1];
            $field     = $match[2];
            $op        = $match[3];

            $condition = $prefix . $field;
            switch ($op)
            {
            case '=':
            case '!=':
                if (is_array($value))
                {
                    // Convert to IN / NOT IN
                    if ($op[0] == '!')
                        $condition .= ' NOT IN ?';
                    else
                        $condition .= ' IN ?';
                }
                else
                {
                    $condition .= ' '. $op .' ?';
                }
                break;

            case '<=':
            case '>=':
            case '<':
            case '>':
                $condition .= ' '. $op .' ?';
                break;

            case '=^':
            case '!=^':
                if ($op[0] == '!')
                    $condition .= ' NOT';

                $condition .= ' LIKE ?';

                // Adjust each value to be a string with '%' suffix
                if (! is_array($value))
                    $value = (array)$value;

                $newValue = array();
                foreach ($value as $val)
                {
                    $pVal = preg_replace('/\*+/', '%', $val);
                    if ($pVal[strlen($pVal)-1] !== '%')
                        $pVal = $pVal .'%';

                    array_push($newValue, $pVal);
                }

                $value = (count($newValue) > 1
                            ? $newValue
                            : array_pop($newValue));
                break;

            case '=*':
            case '!=*':
                if ($op[0] == '!')
                    $condition .= ' NOT';

                $condition .= ' LIKE ?';

                // Adjust each value to be a string surrounded with '%'
                if (! is_array($value))
                    $value = (array)$value;

                $newValue = array();
                foreach ($value as $val)
                {
                    $pVal = preg_replace('/\*+/', '%', $val);
                    if ($pVal[0] !== '%')
                        $pVal = '%'. $pVal;
                    if ($pVal[strlen($pVal)-1] !== '%')
                        $pVal = $pVal .'%';

                    array_push($newValue, $pVal);
                }

                $value = (count($newValue) > 1
                            ? $newValue
                            : array_pop($newValue));
                break;

            case '=$':
            case '!=$':
                if ($op[0] == '!')
                    $condition .= ' NOT';

                $condition .= ' LIKE ?';

                // Adjust each value to be a string with '%' prefix
                if (! is_array($value))
                    $value = (array)$value;

                $newValue = array();
                foreach ($value as $val)
                {
                    $pVal = preg_replace('/\*+/', '%', $val);
                    if ($pVal[0] !== '%')
                        $pVal = '%'. $pVal;

                    array_push($newValue, $pVal);
                }

                $value = (count($newValue) > 1
                            ? $newValue
                            : array_pop($newValue));
                break;

            default:
                $condition .= ' = ?';
                break;
            }

            // Now, handle an array of values depending on the operator.
            if (is_array($value))
            {
                if (($op !== '=') && ($op !== '!='))
                {
                    /* MUST be expanded to a direct query since we need one
                     * statement per value.
                     */
                    $conditions = array();
                    foreach ($value as $idex => $val)
                    {
                        array_push($conditions,
                                   array('condition' => $condition,
                                         'value'     => $val));
                    }

                    /* For multi-value matches, if the operator included a NOT,
                     * we need to combine using 'AND' instead of 'OR'.
                     */
                    $condition = $this->_flattenConditions($conditions,
                                                           ($op[0] === '!'));

                    $value     = null;
                }
            }
        }
        else
        {
            // else, skip it (or throw an error)...

            /*
            Connexions::log("Connexions_Model_Mapper_DbTable::_whereCondition()"
                            .   ": condition NO MATCH");
            // */

            $condition = null;
        }

        /*
        Connexions::log("Connexions_Model_Mapper_DbTable::_whereCondition():"
                        .   "final condition: [ %s ], value[ %s ]",
                        Connexions::varExport($condition),
                        Connexions::varExport($value));
        // */

        return ($condition !== null
                    ? array('condition' => $condition,
                            'value'     => $value)
                    : null);
    }

    /** @brief  Given an array of conditions generated via _whereCondition(),
     *          flatten them into a single condition string, binding and 
     *          quoting all values.
     *  @param  conditions      An array of conditions from _whereCondition();
     *  @param  and             Join via 'AND' (true) or 'OR' (false) [ true ];
     *
     *  @return A flat, string condition
     */
    /*protected*/ function _flattenConditions($conditions, $and = true)
    {
        $adapter    = $this->getAccessor()->getAdapter();

        /*
        Connexions::log("_flattenConditions: adapter [ %s ]",
                        (is_object($adapter)
                            ? get_class($adapter)
                            : gettype($adapter)) );
        // */

        $quoted = array();
        foreach ($conditions as $cond)
        {
            array_push($quoted,
                       '('.$adapter->quoteInto($cond['condition'],
                                               $cond['value']) .')');
        }

        $res = '('
             .    implode(($and ? ' AND '   // Zend_Db_Select::SQL_AND
                                : ' OR '),  // Zend_Db_Select::SQL_OR
                          $quoted)
             . ')';

        return $res;
    }

    /**************************************************************************
     * These MAY be generic enough now to move to Connexions_Model_Mapper()
     *
     */

    /** @brief  Given an array of identification/selection information,
     *          construct a database-specific array of selection clauses.
     *  @param  id          The identification/selection information;
     *  @param  nonEmpty    Can the final array of selection clauses be empty?
     *                      [ true ];
     *
     *  Identification/selection information may have the form:
     *      { condition1: value(s), condition2: value(s), ... }
     *      [ condition1, condition2, ... ]
     *      [ {'condition': condition1, 'value': value(s)},
     *         'condition': condition2, 'value': value(s)},
     *         ...} ]
     *
     *  Each 'condition' can have the form:
     *      prefix field op
     *
     *  'prefix' may be:
     *      |   - this is an 'OR' condition as opposed to the default 'AND';
     *      +   - combine/group this condition with the previous using 'AND';
     *      +|  - combine/group this condition with the previous using 'OR';
     *
     *  'field' names the target database field
     *  'op' may be:
     *      =   - [default] equivalence match;
     *      !=  - NOT equal;
     *      >   - greater than;
     *      >=  - greater than or equal to;
     *      <   - less than;
     *      <=  - less than or equal to;
     *
     *  For String values, the follow are also valid for 'op':
     *      =*  - contains 'value';
     *      =^  - begins with 'value';
     *      =$  - ends   with 'value;
     *
     *      !=* - does NOT contain 'value';
     *      !=^ - does NOT begin with 'value';
     *      !=$ - does NOT end   with 'value;
     *
     *
     *  If 'value' is an array, it indicates that any of the values is 
     *  acceptable.
     *
     *  For array values, if the operator is '=' or '!=', the condition will be
     *  reduced using 'IN' or 'NOT IN'; otherwise, it will be converted to a 
     *  single, complex 'where' condition with one per value, pre-bound and 
     *  database quoted, all combined via 'OR' ('AND' for NOT conditions).  
     *
     *  Note: This REQUIRES _whereCondition() to convert a generic 
     *        condition/value pair into a database-specific, bindable 
     *        condition/value pair.
     *
     *        This also REQUIRES _flattenConditions() to convert a set of 
     *        conditions generate via _whereCondition() to a single, flat, 
     *        pre-bound, database-specific WHERE condition string.
     *
     *  @return An array of database-specific selection clauses.
     */
    /*protected*/ function _where(array $id, $nonEmpty = true)
    {
        /*
        Connexions::log("Connexions_Model_Mapper_DbTable[%s]::"
                        . "_where(%s, %sempty):",
                        get_class($this),
                        Connexions::varExport($id),
                        ($nonEmpty ? 'non-' : ''));
        // */

        $tmpWhere = array();
        foreach ($id as $condition => $value)
        {
            if (is_int($condition))
            {
                /* 'condition' is an integer, meaning this is a non-associative 
                 * member.
                 *
                 * See if 'value' is an array containing 'condition' and 
                 * 'value'.  If so, we have a condition and value, otherwise, 
                 * 'value' is actually the condition.
                 */
                if ( is_array($value)           &&
                     isset($value['condition']) &&
                     isset($value['value']) )
                {
                    // Special, complex condition(s)
                    $condition = $value['condition'];
                    $value     = $value['value'];
                }
                else
                {
                    /* Simply use 'value' as the condition.  This is for
                     * simple, direct compare/value statements (e.g. 'field=1')
                     */
                    $condition = $value;
                    $value     = null;

                    array_push($tmpWhere,
                               array('condition' => $condition,
                                     'value'     => $value));
                    continue;
                }
            }

            /*******************************************************
             * See if this condition is to be directly joined
             * with the previous condition (i.e. prefixed with '+').
             *
             */
            if (preg_match('/^\s*\+(.*)$/', $condition, $match))
            {
                // YES - remember the condition without the prefix.
                $joinPrevious = true;
                $condition    = $match[1];
            }
            else
            {
                // NO
                $joinPrevious = false;
            }

            /*******************************************************
             * Parse this single condition/value pair, generating
             * a discrete 'condition' and 'value'
             *
             */
            $res = $this->_whereCondition($condition, $value);
            if ($res === null)
            {
                // INVALID - skip it (or throw an error)...
                continue;
            }

            if ($joinPrevious)
            {
                /* Join the current condition with the previous condition 
                 * pre-binding and quoting all values.
                 */
                $prev      = array_pop($tmpWhere);
                $condition = '';

                if ($prev['condition'][0] === '|')
                {
                    $condition = '|';
                    $prev['condition'] = substr($prev['condition'], 1);
                }

                if ($res['condition'][0] === '|')
                {
                    $and = false;
                    $res['condition'] = substr($res['condition'], 1);
                }
                else
                {
                    $and = true;
                }


                // Generate a flattened, string condition.
                $condition .= $this->_flattenConditions(array($prev,$res),
                                                        $and);

                $res['condition'] = $condition;
                $res['value']     = null;
            }

            array_push($tmpWhere, $res);
        }

        if ( ($nonEmpty !== false) && empty($tmpWhere) )
        {
            throw new Exception(
                        "Cannot generate a non-empty WHERE clause for "
                        . "model [ ". get_class($this) ." ] "
                        . "from data "
                        . "[ ". Connexions::varExport($id) ." ]");
        }

        /***********************************************************
         * Finally, simplify the where to an associative array of
         *  { condition: value(s), ... }
         */
        $where = array();
        foreach ($tmpWhere as $statement)
        {
            if (is_array($statement['condition']))
            {
                // Break it out into multiple statements
                foreach ($statement['condition'] as $idex => $condition)
                {
                    $where[ $condition ] = $statement['value'][$idex];
                }
            }
            else
            {
                $where[ $statement['condition'] ] = $statement['value'];
            }
        }

        return $where;
    }
}
