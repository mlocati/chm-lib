<?php

namespace CHMLib\TOCIndex;

use CHMLib\CHM;
use CHMLib\Map;
use DOMElement;
use Exception;

/**
 * A list of items in the TOC or in the Index of an CHM file.
 */
class Item
{
    /**
     * The parent CHM instance.
     *
     * @var CHM
     */
    protected $chm;

    /**
     * The name of the tree item.
     *
     * @var string
     */
    protected $name;

    /**
     * The keyword of the tree item.
     *
     * @var string
     */
    protected $keyword;

    /**
     * The local path to the tree item.
     *
     * @var string
     */
    protected $local;

    /**
     * The path to an entry in another CHM file.
     *
     * @var array|null If not null, it's an array with two keys: 'chm' and 'entry'.
     */
    protected $merge;

    /**
     * The image number attribute.
     *
     * @var int|null
     */
    protected $imageNumber;

    /**
     * The sub-elements of this Item.
     *
     * @var Tree|null
     */
    protected $subTree;

    /**
     * Initializes the instance.
     *
     * @param CHM $chm The parent CHM instance.
     */
    protected function __construct(CHM $chm)
    {
        $this->chm = $chm;
        $this->name = '';
        $this->keyword = '';
        $this->local = '';
        $this->merge = null;
        $this->imageNumber = null;
        $this->subTree = null;
    }

    /**
     * Get the name of the tree item.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the keyword of the tree item.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Get the local path to the tree item.
     *
     * @return string
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * Get the path to an entry in another CHM file.
     *
     * @var array|null If not null, it's an array with two keys: 'chm' and 'entry'.
     */
    public function getMerge()
    {
        return $this->merge;
    }

    /**
     * Get the image number attribute.
     *
     * @return int|null
     */
    public function getImageNumber()
    {
        return $this->imageNumber;
    }

    /**
     * Get the sub-elements of this Item.
     *
     * @return Tree|null
     */
    public function getSubTree()
    {
        return $this->subTree;
    }

    /**
     * Resolve the items contained in other CHM files.
     *
     * @param Map $map
     *
     * @throws Exception Throw an Exception in case of errors.
     *
     * @return static[]
     */
    public function resolve(Map $map)
    {
        if ($this->merge === null) {
            $result = array($this);
        } else {
            $chm = $map->get($this->merge['chm']);
            if ($chm === null) {
                throw new Exception("Missing CHM reference from map: {$this->merge['chm']}");
            }
            $entry = $chm->getEntryByPath($this->merge['entry']);
            if ($entry === null) {
                throw new Exception("Missing entry '{$this->merge['entry']}' in CHM file {$this->merge['chm']}");
            }
            $tree = Tree::fromString($chm, $entry->getContents());
            $tree->resolve($map);
            $result = $tree->getItems();
        }

        return $result;
    }

    /**
     * Create a new instance starting from a LI element.
     *
     * @param CHM $chm The parent CHM instance.
     * @param DOMElement $li
     *
     * @throws Exception Throw an Exception in case of errors.
     *
     * @return static
     */
    public static function fromLI(CHM $chm, DOMElement $li)
    {
        $result = array();
        $itemForUL = null;
        foreach ($li->childNodes as $c) {
            if ($c instanceof DOMElement) {
                switch (strtolower($c->tagName)) {
                    case 'object':
                        if (strcasecmp((string) $c->getAttribute('type'), 'text/sitemap') === 0) {
                            $itemForUL = static::fromObject($chm, $c);
                            $result[] = $itemForUL;
                        }
                        break;
                    case 'ul':
                        if ($itemForUL === null) {
                            throw new Exception('No sitemap object found for a tree item');
                        }
                        $itemForUL->subTree = Tree::fromUL($chm, $c);
                        $itemForUL = null;
                        break;
                }
            }
        }
        if (empty($result)) {
            throw new Exception('No sitemap object found for a tree item');
        }

        return $result;
    }

    /**
     * Create a new instance starting from a LI element or from an OBJECT.
     *
     * @param CHM $chm The parent CHM instance.
     * @param DOMElement $object
     *
     * @throws Exception Throw an Exception in case of errors.
     *
     * @return static
     */
    public static function fromObject(CHM $chm, DOMElement $object)
    {
        $result = new static($chm);
        foreach ($object->childNodes as $p) {
            if ($p instanceof DOMElement && strcasecmp($p->tagName, 'param') === 0) {
                $name = trim((string) $p->getAttribute('name'));
                $value = trim((string) $p->getAttribute('value'));
                switch (strtolower($name)) {
                    case 'name':
                        $result->name = $value;
                        break;
                    case 'keyword':
                        $result->keyword = $value;
                        break;
                    case 'local':
                        $result->local = '/'.str_replace('\\', '/', $value);
                        break;
                    case 'merge':
                        if (!preg_match('%^([^:\\\\/]+)::(.+\.hh[ck])$%i', $value, $matches)) {
                            throw new Exception("Invalid value of the '$name' attribute: $value");
                        }
                        $result->merge = array('chm' => $matches[1], 'entry' => '/'.ltrim(str_replace('\\', '/', $matches[2]), '/'));
                        break;
                    case 'imagenumber':
                        if (is_numeric($value)) {
                            $result->imageNumber = (int) $value;
                        } elseif ($value !== '') {
                            throw new Exception("Invalid value of the '$name' attribute: $value");
                        }
                        break;
                    default:
                        throw new Exception("Unknown parameter name '$name' of a tree item (value: '$value')");
                }
            }
        }
        if ($result->name === 'dlg_vector_image') {
            $result->name = 'dlg_vector_image';
        }

        return $result;
    }
}
