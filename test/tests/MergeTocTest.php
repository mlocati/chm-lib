<?php

namespace CHMLib\Test;

use CHMLib\CHM;
use CHMLib\Map;
use CHMLib\TOCIndex\Tree;
use CHMLib\Test\TestCase;

class MergeTocTest extends TestCase
{
    public function testMergeToc()
    {
        $dir = dirname(dirname(__FILE__)).'/samples';
        $main = CHM::fromFile($dir.'/main.chm');
        $map = new Map();
        $map->add('second.chm', CHM::fromFile($dir.'/second.chm'));
        $toc = $main->getTOC();
        $this->assertNotNull($toc);
        $this->assertSame(
            <<<EOT
Heading in main
    Topic in main
second.chm>/second.hhc
EOT
            ,
            rtrim(static::formatTree($toc, 0))
        );
        $toc->resolve($map);
        $this->assertSame(<<<EOT
Heading in main
    Topic in main
Heading in second
    Topic in second
EOT
            ,
            rtrim(static::formatTree($toc, 0))
        );
    }

    protected static function formatTree(Tree $tree, $depth)
    {
        $result = '';
        foreach ($tree->getItems() as $child) {
            $result .= str_repeat('    ', $depth);
            if ($child->getName() !== '') {
                $result .= $child->getName();
            } elseif ($child->getURL() !== '') {
                $result .= $child->getURL();
            } elseif (is_array($child->getMerge())) {
                $result .= implode('>', $child->getMerge());
            } elseif (is_string($child->getMerge())) {
                $result .= $child->getMerge();
            } else {
                $result .= '?';
            }
            $result .= "\n";
            $result .= static::formatTree($child->getChildren(), $depth + 1);
        }

        return $result;
    }
}
