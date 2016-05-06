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
     * Is this item marked as new?
     *
     * @var bool
     */
    protected $isNew;

    /**
     * The comment of the tree item.
     *
     * @var string
     */
    protected $comment;

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
     * The frame name for this item.
     *
     * @var string;
     */
    protected $frameName;

    /**
     * The window name for this item.
     *
     * @var string;
     */
    protected $windowName;

    /**
     * The path to an entry in another CHM file.
     *
     * @var string|array|null If it's an array, it has two keys: 'chm' and 'entry'.
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
        $this->isNew = false;
        $this->comment = '';
        $this->keyword = '';
        $this->seeAlso = '';
        $this->local = '';
        $this->url = '';
        $this->frameName = '';
        $this->windowName = '';
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
                    case 'new':
                        $this->isNew = !empty($value);
                        break;
                    case 'comment':
                        $this->comment = $value;
                        break;
                    case 'keyword':
                        $this->keyword = $value;
                        break;
                    case 'see also':
                        $this->seeAlso = $value;
                        break;
                    case 'local':
                        $this->local = '/'.str_replace('\\', '/', str_replace('%20', ' ', $value));
                        break;
                    case 'url':
                        $this->url = $value;
                        break;
                    case 'framename':
                        $this->frameName = $value;
                        break;
                    case 'windowname':
                        $this->windowName = $value;
                        break;
                    case 'merge':
                        if ($value !== '') {
                            if (preg_match('%^([^:\\\\/]+.chm)::(.+)$%i', $value, $matches)) {
                                $this->merge = array('chm' => $matches[1], 'entry' => '/'.ltrim(str_replace('\\', '/', $matches[2]), '/'));
                            } else {
                                $this->merge = $value;
                            }
                        }
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
     * Is this item marked as new?
     *
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }

    /**
     * Get the comment of the tree item.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
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
     * Get the frame name for this item.
     *
     * @return string
     */
    public function getFrameName()
    {
        return $this->frameName;
    }

    /**
     * Get the window name for this item.
     *
     * @return string
     */
    public function getWindowName()
    {
        return $this->frameName;
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
     * @param bool $ignoreErrors Set to true to ignore missing CHM and/or entries.
     *
     * @throws Exception Throw an Exception in case of errors.
     *
     * @return static[]
     */
    public function resolve(Map $map, $ignoreErrors = false)
    {
        $result = array($this);
        if (is_array($this->merge)) {
            $chm = $map->get($this->merge['chm']);
            if ($chm === null) {
                if (!$ignoreErrors) {
                    throw new Exception("Missing CHM reference from map: {$this->merge['chm']}");
                }
            } else {
                $entry = $chm->getEntryByPath($this->merge['entry']);
                if ($entry === null) {
                    if (!$ignoreErrors) {
                        throw new Exception("Missing entry '{$this->merge['entry']}' in CHM file {$this->merge['chm']}");
                    }
                } else {
                    $tree = Tree::fromString($chm, $entry->getContents());
                    $tree->resolve($map, $ignoreErrors);
                    $result = $tree->getItems();
                }
            }
        }
        foreach ($result as $newItem) {
            $newItem->children->resolve($map, $ignoreErrors);
        }

        return $result;
    }

    /**
     * Search the associated entry in the CHM file.
     *
     * @return \CHMLib\Entry|null
     */
    public function findEntry()
    {
        $result = null;
        if ($this->local !== '') {
            $path = '/'.ltrim(str_replace('\\', '/', $this->local), '/');
            $entry = $this->chm->getEntryByPath($path);
            if ($entry === null) {
                $p = strpos($path, '#');
                if ($p !== false) {
                    $path = substr($path, 0, $p);
                    $entry = $this->chm->getEntryByPath($path);
                }
            }
            if ($entry !== null && $entry->isFile()) {
                $result = $entry;
            }
        }

        return $result;
    }
}
