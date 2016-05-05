<?php

namespace CHMLib\TOCIndex;

use CHMLib\CHM;
use CHMLib\Map;
use DOMDocument;
use DOMElement;
use DOMXpath;
use Exception;

/**
 * A list of items in the TOC or in the Index of an CHM file.
 */
class Tree
{
    /**
     * The parent CHM instance.
     *
     * @var CHM
     */
    protected $chm;

    /**
     * List of Item instances children of this tree.
     *
     * @var Item[]
     */
    protected $items;

    /**
     * Initializes the instance.
     */
    protected function __construct(CHM $chm)
    {
        $this->chm = $chm;
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
     * Resolve the items contained in other CHM files.
     *
     * @param Map $map
     *
     * @throws Exception Throw an Exception in case of errors.
     */
    public function resolve(Map $map)
    {
        $result = array();
        foreach ($this->items as $item) {
            $merge = $item->getMerge();
            if ($merge === null) {
                $result[] = $item;
            } else {
                $result = array_merge($result, $item->resolve($map));
            }
        }

        $this->items = $result;
    }

    /**
     * Create a new instance starting from an UL element.
     *
     * @param CHM $chm The parent CHM instance.
     * @param DOMElement $ul The UL element to be parsed.
     *
     * @throws Exception Throw an Exception in case of errors.
     *
     * @return static
     */
    public static function fromUL(CHM $chm, DOMElement $ul)
    {
        $result = new static($chm);
        foreach ($ul->childNodes as $li) {
            if ($li instanceof DOMElement && strcasecmp($li->tagName, 'li') === 0) {
                $result->items = array_merge($result->items, Item::fromLI($chm, $li));
            }
        }

        return $result;
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
        $data = trim((string) $data);
        $doc = new DOMDocument();
        $charset = 'UTF-8';
        if (preg_match('/^<\?xml\s+encoding\s*=\s*"([^"]+)"/i', $data, $m)) {
            $charset = $m[1];
        } else {
            if (preg_match('/<meta\s+http-equiv\s*=\s*"Content-Type"\s+content\s*=\s*"text\/html;\s*charset=([^"]+)">/i', $data, $m)) {
                $charset = $m[1];
            }
            $data = '<?xml encoding="'.$charset.'">'.$data;
        }
        if (@$doc->loadHTML($data) !== true) {
            throw new Exception('Failed to parse the .hhc/.hhk file contents');
        }
        $xpath = new DOMXpath($doc);
        $elements = $xpath->query('/html/body/ul');
        switch ($elements->length) {
            case 0:
                $elements = $xpath->query('/html/body/object[@type="text/sitemap"]');
                if ($elements->length === 0) {
                    throw new Exception('No root list in the .hhc/.hhk file contents');
                }
                $result = new static($chm);
                foreach ($elements as $object) {
                    $result->items[] = Item::fromObject($chm, $object);
                }
                break;
            case 1:
                $result = static::fromUL($chm, $elements->item(0));
                break;
            default:
                throw new Exception('More that one root list in the .hhc/.hhk file contents');
        }

        return $result;
    }
}
