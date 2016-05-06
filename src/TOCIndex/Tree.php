<?php

namespace CHMLib\TOCIndex;

use CHMLib\CHM;
use CHMLib\Map;
use DOMDocument;
use DOMElement;
use DOMXpath;
use Exception;
use Iterator;

/**
 * A list of items in the TOC or in the Index of an CHM file.
 */
class Tree implements Iterator
{
    /**
     * List of Item instances children of this tree.
     *
     * @var Item[]
     */
    protected $items;

    /**
     * The current index for the Iterator interface.
     *
     * @var int
     */
    protected $iteratorIndex;

    /**
     * Initializes the instance.
     */
    public function __construct()
    {
        $this->items = array();
    }

    /**
     * Get the items contained in this tree.
     *
     * @return Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get the total number of items in this instance and in all the sub-instances.
     *
     * @return int
     */
    public function getItemsCount()
    {
        $result = count($this->items);
        foreach ($this->items as $item) {
            $result += $item->getChildren()->getTotalCount();
        }

        return $result;
    }

    /**
     * Resolve the items contained in other CHM files.
     *
     * @param Map $map
     * @param bool $ignoreErrors Set to true to ignore missing CHM and/or entries.
     *
     * @throws Exception Throw an Exception in case of errors.
     */
    public function resolve(Map $map, $ignoreErrors = false)
    {
        $result = array();
        foreach ($this->items as $item) {
            $result = array_merge($result, $item->resolve($map, $ignoreErrors));
        }

        $this->items = $result;
    }

    /**
     * Create a new instance starting from the whole TOC/Index source 'HTML'.
     *
     * @param CHM $chm The parent CHM instance.
     * @param string $data The contents of the .hhc/.hhk file.
     *
     * @throws Exception Throw an Exception in case of errors.
     *
     * @return static
     */
    public static function fromString(CHM $chm, $data)
    {
        if (!class_exists('DOMDocument', false) || !class_exists('DOMXpath', false)) {
            throw new Exception('Missing PHP extension: php-xml');
        }
        $result = new static();
        $data = trim((string) $data);
        if (stripos($data, '<object') !== false) {
            $doc = new DOMDocument();
            $charset = 'ISO-8859-1';
            if (preg_match('%^<\?xml\s+encoding\s*=\s*"([^"]+)"%i', $data, $m)) {
                $charset = $m[1];
            } else {
                if (preg_match('%<meta\s+http-equiv\s*=\s*"Content-Type"\s+content\s*=\s*"text/html;\s*charset=([^"]+)">%i', $data, $m)) {
                    $charset = $m[1];
                }
                $data = '<?xml encoding="'.$charset.'">'.$data;
            }
            // LI elements are very often malformed/misplaced: let's remove them
            $data = preg_replace('$</?li((\\s+[^>]*)|\\s*)>$i', '', $data);
            if (@$doc->loadHTML($data) !== true) {
                throw new Exception('Failed to parse the .hhc/.hhk file contents');
            }
            $result->parseParentElement($chm, $doc->documentElement, 0);
        }

        return $result;
    }

    /**
     * Depth of the found child items.
     *
     * @var int
     */
    protected $depth;

    /**
     * Parse a DOMElement and read the items/sub trees.
     *
     * @param CHM $chm
     * @param DOMElement $parentElement
     * @param int $depth
     */
    protected function parseParentElement(CHM $chm, DOMElement $parentElement, $depth)
    {
        foreach ($parentElement->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                switch (strtolower($node->tagName)) {
                    case 'object':
                        if (strtolower($node->getAttribute('type')) === 'text/sitemap') {
                            $this->depth = $depth;
                            $this->items[] = new Item($chm, $node);
                        }
                        break;
                    case 'ul':
                    case 'ol':
                        $n = count($this->items);
                        if ($n > 0 && $depth >= $this->depth) {
                            $this->items[$n - 1]->getChildren()->parseParentElement($chm, $node, $depth + 1);
                        } else {
                            $this->parseParentElement($chm, $node, $depth + 1);
                        }
                        break;
                    default:
                        $this->parseParentElement($chm, $node, $depth + 1);
                        break;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::current()
     */
    public function current()
    {
        return isset($this->items[$this->iteratorIndex]) ? $this->items[$this->iteratorIndex] : null;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::key()
     */
    public function key()
    {
        return $this->iteratorIndex;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::next()
     */
    public function next()
    {
        ++$this->iteratorIndex;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        $this->iteratorIndex = 0;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::valid()
     */
    public function valid()
    {
        return $this->iteratorIndex < count($this->items);
    }
}
