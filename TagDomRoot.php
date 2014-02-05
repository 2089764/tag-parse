<?php
/**
 * HTML标签解析包
 *
 * @category  TagParse
 * @package   TagParse
 * @author    kun <yangrokety@gmail.com>
 * @copyright 2014 kun
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version   1.0
 * @link      http://www.blogkun.com
 * @since     1.0
 */
namespace TagParse;

/**
* TagDomRoot
*
* @category  TagParse
* @package   TagParse
* @author    kun <yangrokety@gmail.com>
* @copyright 2014 kun
* @license   http://www.php.net/license/3_01.txt  PHP License 3.01
* @version   1.0
* @link      http://www.blogkun.com
* @since     1.0
*/
class TagDomRoot
{
    public $tag                  = 'root';
    public $plaintext;
    public $child                = array();
    public $level                = 0;
    public static $TagParseError = false;
    protected static $TagSet     = array();
    protected static $FoundNode  = array();
    public static $ErrorTag      = array();

    /**
     * initProperty
     *
     * @access public
     *
     * @return null
     */
    public function initProperty()
    {
        $TagParseError  = false;
        $TagSet         = array();
        $FoundNode      = array();
        $DumpScriptCode = array();
        $ErrorTag       = array();
    }

    /**
     * __construct
     *
     * @param string $str The tag string to be parse.
     *
     * @access public
     *
     * @return TagDomRoot
     */
    public function __construct($str)
    {
        $this->_removeNoise($str);
        if ($str === null) {
            self::$TagParseError = true;
        } else {
            $l = strpos($str, '<');
            if ($l !== false) {
                $this->plaintext = substr($str, 0, $l);
            }
            $res = preg_match_all('~>(.*?)<~s', $str, $matches);
            if ($res !== false && $res > 0) {
                $this->plaintext .= implode($matches[1]);
            }
            $r = strrpos($str, '>');
            if ($r !== false) {
                $this->plaintext .= substr($str, $r+1);
            }

            $tagCollect          = array();
            $attrCollect         = array();
            $innerContentCollect = array();

            if ($this->parseTag($str, $tagCollect, $attrCollect, $innerContentCollect) === false) {
                self::$TagParseError = true;
            }

            foreach ($tagCollect as $index => $tag) {
                $this->child[] = new TagDomNode($tag, $this, $attrCollect[$index], $innerContentCollect[$index], $this->level+1);
            }
        }
    }

    /**
     * parseTag
     *
     * @param mixed $str                  Description.
     * @param mixed &$tagCollect          Description.
     * @param mixed &$attrCollect         Description.
     * @param mixed &$innerContentCollect Description.
     *
     * @access protected
     *
     * @return boolean Value.
     */
    protected function parseTag($str, array &$tagCollect, array &$attrCollect, array &$innerContentCollect)
    {
        $selfClosingTags = array('img' => 1, 'br' => 1, 'input' => 1, 'meta' => 1, 'link' => 1, 'hr' => 1, 'base' => 1, 'embed' => 1, 'spacer' => 1);
        $end             = -2;
        $close           = 0;
        $error           = false;
        $tag             = '';
        while (true) {
            $l = strpos($str, '<', $end+strlen($tag)+2);
            if ($l === false) {//parse end
                break;
            }
            if (strpos(substr($str, $l, 2), '/') !== false) {//surplus closing tag,discard
                $error            = true;
                $end              = $l+strlen($tag);
                self::$ErrorTag[] = substr($str, $l, strpos($str, '>', $l)-$l+1);
                continue;
            }

            $r   = strpos($str, '>', $l);
            $tag = substr($str, $l+1, $r-$l-1);
            if (!ctype_alpha($tag[0]) || strpos($tag, '<') !== false) {
                $end = $r + 1;
                continue;
            }
            $tag   = preg_replace("~\n+~", ' ', $tag);
            $space = strpos($tag, ' ');
            if ($space !== false) {
                $attrCollect[] = substr($tag, $space+1);
                $tag           = substr($tag, 0, $space);
            } else {
                $attrCollect[] = '';
            }
            $tagCollect[] = $tag;
            if (isset($selfClosingTags[$tag])) {
                $innerContentCollect[] = '';
                $end                   = $r-strlen($tag)-2;
                $close                 = $r+1;
                continue;
            }

            $countOpen = -1;
            $open      = strpos($str, '<'.$tag, $close);
            $close     = strpos($str, '</'.$tag.'>', $open);
            if ($close === false) {//surplus opening tag
                $innerContentCollect[] = substr($str, $r+1);
                $error                 = true;
                self::$ErrorTag[]      = '<'.$tag.'>';
                break;
            }
            $start = $open;
            while ($open < $close && $open !== false) {
                $countOpen++;
                $open = strpos($str, '<'.$tag, $open+strlen($tag));
            }

            while ($countOpen > 0 && $close !== false) {
                $open  = strpos($str, '<'.$tag, $close+strlen($tag)+3);
                $close = strpos($str, '</'.$tag.'>', $close+strlen($tag)+3);
                if ($close === false) {
                    break;
                }
                $countOpen--;
                while ($open < $close && $open !== false) {
                    $open = strpos($str, '<'.$tag, $open+strlen($tag)+3);
                    $countOpen++;
                }
            }
            if ($close === false) {//标签闭合不配对
                $innerContentCollect[] = substr($str, $r+1);
                $error                 = true;
                break;
            }
            $end                   = $close;
            $r                     = strpos($str, '>', $start);
            $innerContentCollect[] = substr($str, $r+1, $end - $r - 1);
        }

        return !$error;
    }

    /**
     * _removeNoise
     *
     * @param string &$str The tag string to be parse.
     *
     * @access private
     *
     * @return string
     */
    private function _removeNoise(&$str)
    {
        $str = preg_replace('~<!\[CDATA\[(.*?)\]\]>~is', '', $str);
        $str = preg_replace('~<!--(.*?)-->~is', '', $str);
        $str = preg_replace('~<!DOCTYPE.*?>~is', '', $str);
    }

    /**
     * parseSelectors
     *
     * @param string $selectors      user's select condition.
     * @param array  &$selectorsTag  tags
     * @param array  &$selectorsAttr attributes
     *
     * @access protected
     *
     * @return null
     */
    protected function parseSelectors($selectors, array &$selectorsTag, array &$selectorsAttr)
    {
        preg_match_all('~([\w\d]+)(\[[\w\d -="._/]+\])?~', $selectors, $matches);
        $selectorsTag = $matches[1];
        foreach ($matches[2] as $key => $value) {
            $selectorsAttr[$key] = array();
            if ($value !== '') {
                preg_match_all('~([\w\d-]+)="([\w\d-. _/]+)"~', $value, $matches);
                foreach ($matches[1] as $index => $attr) {
                    $selectorsAttr[$key][$attr] = $matches[2][$index];
                }
            }
        }
    }

    /**
     * find
     *
     * @param mixed $selectors     user's select condition.
     * @param array $selectorsTag  tags.
     * @param array $selectorsAttr attributes.
     *
     * @access public
     *
     * @return array
     */
    public function find($selectors, $selectorsTag  = array(), $selectorsAttr = array())
    {
        if ($selectors !== null) {
            $this->parseSelectors($selectors, $selectorsTag, $selectorsAttr);
        }
        var_dump($selectorsTag, $selectorsAttr);exit();
        if (!empty($selectorsTag)) {
            $this->seek($selectorsTag, $selectorsAttr);
            foreach ($this->child as $key => $node) {
                $node->find(null, $selectorsTag, $selectorsAttr);
            }
        }

        if ($selectors !== null) {
            $res             = self::$FoundNode;
            self::$FoundNode = array();

            return $res;
        }
    }

    /**
     * findGlobal
     *
     * @param string $selectors user's select condition.
     *
     * @access public
     *
     * @return array
     */
    public function findGlobal($selectors)
    {
        $space = strpos($selectors, ' ', strpos($selectors, ']'));
        if ($space === false) {
            return $this->findOneGlobal($selectors);
        } else {
            $selectorsAttr = array();
            $selectorsTag  = array();
            $this->findOneGlobal(substr($selectors, 0, $space), false);
            $this->parseSelectors(substr($selectors, $space + 1), $selectorsTag, $selectorsAttr);
            if (!empty(self::$FoundNode) && !empty($selectorsTag)) {
                $nodes           = self::$FoundNode;
                self::$FoundNode = array();
                foreach ($nodes as $key => $node) {
                    $node->seek($selectorsTag, $selectorsAttr);
                }
            }


        }

        $res             = self::$FoundNode;
        self::$FoundNode = array();

        return $res;
    }

    /**
     * seek
     *
     * @param array $selectorsTag  tags.
     * @param array $selectorsAttr attributes.
     *
     * @access protected
     *
     * @return null
     */
    protected function seek($selectorsTag, $selectorsAttr)
    {
        foreach ($this->child as $key => $node) {
            $isFind = true;
            if ($node->tag === $selectorsTag[0]) {
                foreach ($selectorsAttr[0] as $attrName => $value) {

                    if (isset($node->attr[$attrName])
                        && (preg_match('~.*? '.$value.' .*?~', $node->attr[$attrName]) > 0
                        || preg_match('~^'.$value.'$~', $node->attr[$attrName]) > 0
                        || preg_match('~^'.$value.' ~', $node->attr[$attrName]) > 0
                        || preg_match('~ '.$value.'$~', $node->attr[$attrName]) > 0)
                    ) {
                        continue;
                    } else {
                        $isFind = false;
                        break;
                    }
                }
            } else {
                $isFind = false;
            }
            if ($isFind) {
                if (count($selectorsTag) === 1) {
                    self::$FoundNode[] = $node;
                } else {
                    $node->seek(
                        array_slice($selectorsTag, 1),
                        array_slice($selectorsAttr, 1)
                    );
                }
            }
        }
    }

    /**
     * findOneGlobal
     *
     * @param string $selector user's select condition.
     * @param bool   $isReturn weather return value.
     *
     * @access public
     *
     * @return array
     */
    public function findOneGlobal($selector, $isReturn = true)
    {
        preg_match('~([\w\d]+)(\[[\w\d -="._/]+\])?~', $selector, $matches);
        $tag  = $matches[1];
        $attr = array();
        if (isset($matches[2])) {
            preg_match_all('~([\w\d-]+)="([\w\d-. _/]+)"~', $matches[2], $matches);
            foreach ($matches[1] as $key => $value) {
                $attr[$value] = $matches[2][$key];
            }
        }
        if (isset(self::$TagSet[$tag])) {
            foreach (self::$TagSet[$tag] as $attrValue => $nodeArray) {
                $isFind = true;
                foreach ($attr as $attrName => $value) {
                    if (preg_match('~'.$attrName.'=".*? '.$value.' .*?"~', $attrValue)
                        || preg_match('~'.$attrName.'="'.$value.' .*?"~', $attrValue)
                        || preg_match('~'.$attrName.'=".*? '.$value.'"~', $attrValue)
                        || preg_match('~'.$attrName.'="'.$value.'"~', $attrValue)
                    ) {
                        continue;
                    } else {
                        $isFind = false;
                        break;
                    }
                }
                if ($isFind) {
                    foreach ($nodeArray as $key => $node) {
                        self::$FoundNode[] = $node;
                    }
                }
            }
        }
        if ($isReturn) {
            $res             = self::$FoundNode;
            self::$FoundNode = array();

            return $res;
        }
    }
}

/**
* TagDomNode
*
* @uses     TagDomRoot
*
* @category  TagParse
* @package   TagParse
* @author    kun <yangrokety@gmail.com>
* @copyright 2014 kun
* @license   http://www.php.net/license/3_01.txt  PHP License 3.01
* @version   1.0
* @link      http://www.blogkun.com
* @since     1.0
*/
class TagDomNode extends TagDomRoot
{
    public $attr   = array();
    public $parent = null;

    /**
     * __construct
     *
     * @param mixed $tag          tag.
     * @param mixed $parent       parent node.
     * @param mixed $attr         attribute.
     * @param mixed $innerContent tag content.
     * @param mixed $level        node level.
     *
     * @access public
     *
     * @return TagDomNode
     */
    public function __construct($tag, $parent, $attr, $innerContent, $level)
    {
        $this->tag    = $tag;
        $this->parent = $parent;
        $this->_parseAttr($attr);
        $this->level  = $level;

        $l = strpos($innerContent, '<');
        if ($l !== false) {
            $this->plaintext = substr($innerContent, 0, $l);
        }
        $res = preg_match_all('~>(.*?)<~s', $innerContent, $matches);
        if ($res !== false && $res > 0) {
            $this->plaintext .= implode($matches[1]);
        } else {
            $this->plaintext .= $innerContent;
        }
        $r = strrpos($innerContent, '>');
        if ($r !== false) {
            $this->plaintext .= substr($innerContent, $r+1);
        }

        $tagCollect          = array();
        $attrCollect         = array();
        $innerContentCollect = array();

        if ($this->parseTag($innerContent, $tagCollect, $attrCollect, $innerContentCollect) === false) {
            self::$TagParseError = true;
        }

        foreach ($tagCollect as $index => $tag) {
            $this->child[] = new TagDomNode($tag, $this, $attrCollect[$index], $innerContentCollect[$index], $this->level+1);
        }

        if (!isset(self::$TagSet[$this->tag])) {
            self::$TagSet[$this->tag] = array();
        }
        if (!isset(self::$TagSet[$this->tag][$attr])) {
            self::$TagSet[$this->tag][$attr] = array();
        }
        self::$TagSet[$this->tag][$attr][] = &$this;
    }

    /**
     * _parseAttr
     *
     * @param string $str attribute string.
     *
     * @access public
     *
     * @return null
     */
    private function _parseAttr($str)
    {
        preg_match_all('~(?<attrName>[\w-]+)="(?<attrValue>.*?)"~s', $str, $matches);
        foreach ($matches['attrName'] as $key => $value) {
            $this->attr[$value] = $matches['attrValue'][$key];
        }
    }
}
