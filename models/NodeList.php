<?php

require_once 'Node.php';

class NodeList
{
    private $nodeList = array();

    public function add(Node $node)
    {
        $this->nodeList[$node->getID()] = $node;
    }

    public function isEmpty()
    {
        return empty($this->nodeList);
    }

    public function contains($id)
    {
        return isset($this->nodeList[$id]);
    }

    public function extractBest()
    {
        $bestNode = null;
        foreach ($this->nodeList as $node) {
            if ($bestNode === null || $node->getF() < $bestNode->getF()) {
                $bestNode = $node;
            }
        }
        if ($bestNode !== null) {
            $this->remove($bestNode);
        }
        return $bestNode;
    }

    public function get($id)
    {
        if ($this->contains($id)) {
            return $this->nodeList[$id];
        }
        return null;
    }

    public function remove(Node $node)
    {
        unset($this->nodeList[$node->getID()]);
    }
}

?>