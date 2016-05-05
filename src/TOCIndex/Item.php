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
     * The value of the "See Also" parameter.
     *
     * @var string
     */
    protected $seeAlso;

    /**
     * The local path to the tree item.
     *
     * @var string
     */
    protected $local;

    /**
     * The URL to the tree item.
     *
     * @var string
     */
    protected $url;

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
     * @var Tree
     */
    protected $children;

    /**
     * Initializes the instance.
     *
     * @param CHM $chm The parent CHM instance.
     * @param DOMElement $object The OBJECT element.
     *
     * @throws Exception Throw an Exception in case of errors.
     *
     * @return static
     */
    public function __construct(CHM $chm, DOMElement $object)
    {
        $this->chm = $chm;
        $this->name = '';
        $this->keyword = '';
        $this->seeAlso = '';
        $this->local = '';
        $this->url = '';
        $this->merge = null;
        $this->imageNumber = null;
        $this->children = new Tree();
        foreach ($object->childNodes as $p) {
            if ($p instanceof DOMElement && strcasecmp($p->tagName, 'param') === 0) {
                $name = trim((string) $p->getAttribute('name'));
                $value = trim((string) $p->getAttribute('value'));
                switch (strtolower($name)) {
                    case 'name':
                        // Multiple values are allowed: we keep only the last one
                        $this->name = $value;
                        break;
                    case 'keyword':
                        $this->keyword = $value;
                        break;
                    case 'see also':
                        $this->seeAlso = $value;
                        break;
                    case 'local':
                        $this->local = '/'.str_replace('\\', '/', $value);
                        break;
                    case 'url':
                        $this->url = $value;
                        break;
                    case 'merge':
                        if (!preg_match('%^([^:\\\\/]+)::(.+\.hh[ck])$%i', $value, $matches)) {
                            throw new Exception("Invalid value of the '$name' attribute: $value");
                        }
                        $this->merge = array('chm' => $matches[1], 'entry' => '/'.ltrim(str_replace('\\', '/', $matches[2]), '/'));
                        break;
                    case 'imagenumber':
                        if (is_numeric($value)) {
                            $this->imageNumber = (int) $value;
                        } elseif ($value !== '') {
                            throw new Exception("Invalid value of the '$name' attribute: $value");
                        }
                        break;
                    default:
                        throw new Exception("Unknown parameter name '$name' of a tree item (value: '$value')");
                }
            }
        }
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
     * Get the value of the "See Also" parameter.
     *
     * @return string
     */
    public function getSeeAlso()
    {
        return $this->seeAlso;
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
     * Get the URL to the tree item.
     *
     * @return string
     */
    public function getURL()
    {
        return $this->url;
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
     * @return Tree
     */
    public function getChildren()
    {
        return $this->children;
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
}
