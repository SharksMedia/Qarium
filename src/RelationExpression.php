<?php

declare(strict_types=1);

namespace Sharksmedia\Objection;

// 2023-07-10

class RelationExpression
{
    /**
     * 2023-07-10
     * @var RelationNode
     */
    private RelationNode $node;

    /**
     * @param string|RelationExpression|null $expression
     * @return RelationExpression
     */
    public static function create($expression=null): self
    {
        if($expression === null) return new static();

        if($expression instanceof RelationExpression) return $expression;

        if(is_string($expression)) return new static(self::parse($expression));

        throw new \InvalidArgumentException('Invalid expression: '.$expression);
    }

    public static function fromModelGraph($graph)
    {
        return new static(self::modelGraphToNode($graph, self::newNode()));
    }

    public function getMaxRecursiveDepth(): int
    {
        if(!$this->node->recursive) return 0;

        return PHP_INT_MAX;
    }

    public function getChildCount(): int
    {
        return count($this->node->iChildNodes);
    }

    public function isEmpty(): bool
    {
        return count($this->node->iChildNodes) === 0;
    }

    public function getNode(): RelationNode
    {
        return $this->node;
    }

    public function merge($relationExpression)
    {
        $iRelationExpression = self::create($relationExpression);

        if($this->isEmpty()) return $iRelationExpression;

        return new static(self::mergeNodes($this->getNode(), $iRelationExpression->getNode()));
    }

    public static function mergeNodes(RelationNode $node1, RelationNode $node2): RelationNode
    {
        $node = clone $node1;

        $node->modify = Utilities::array_union($node1->modify, $node2->modify);
        $node->recursive = $node1->recursive || $node2->recursive;

        $childNodes = [];
        if(!$node->recursive && !$node->allRecursive)
        {
            $childNode1Map = [];
            $childNode2Map = [];

            foreach($node1->iChildNodes as $childNode1) $childNode1Map[$childNode1->name] = $childNode1;
            foreach($node2->iChildNodes as $childNode2) $childNode2Map[$childNode2->name] = $childNode2;

            $childNodes = array_merge($node1->iChildNodes, $node2->iChildNodes);
            $childNames = array_unique(array_column($childNodes, 'name'));

            foreach($childNames as $name)
            {
                if(isset($childNode1Map[$name]) && isset($childNode2Map[$name]))
                {
                    $childNodes[] = self::mergeNodes($childNode1Map[$name], $childNode2Map[$name]);
                }
                else
                {
                    $childNodes[] = isset($childNode1Map[$name])
                        ? $childNode1Map[$name]
                        : $childNode2Map[$name];
                }
            }
        }

        $node->iChildNodes = $childNodes;

        return $node;
    }

    /**
     * 2023-07-11
     * Returns true if `expr` is contained by this expression. For example `a.b` is contained by `a.[b, c]`.
     * @param RelationExpression $iRelationExpression
     * @return bool
     */
    public function isSubExpression(RelationExpression $expression): bool
    {
        if($this->node->allRecursive) return true;
        if($expression->node->allRecursive) return true;

        if($this->node->relationName !== $expression->node->relationName) return false;

        $maxRecursiveDepth = $expression->getMaxRecursiveDepth();

        if($maxRecursiveDepth > 0) return $this->getMaxRecursiveDepth() >= $maxRecursiveDepth;

        foreach($expression->node->iChildNodes as $childNode)
        {
            $ownSubExpression = $this->childExpression($childNode->name);
            $subExpression = $expression->childExpression($childNode->name);

            if($ownSubExpression === null || !$ownSubExpression->isSubExpression($subExpression)) return false;
        }

        return true;
    }

    /**
     * 2023-07-11
     * Returns a RelationExpression for a child node or null if there is no child with the given name `childName`.
     * @param string $name
     * @return RelationExpression|null
     */
    public function childExpression(string $name): ?RelationExpression
    {
        if($this->node->allRecursive || ($name === $this->node->name && $this->node->recursiveDepth < $this->getMaxRecursiveDepth() - 1))
        {
            return new static(self::newNode($name, true, $this->node->recursiveDepth + 1));
        }

        $child = $this->node->iChildNodes[$name] ?? null;

        if($child === null) return null;

        return new static($child);
    }

    public static function newNode(?string $name=null, bool $allRecursive=false, ?int $recursiveDepth=null): RelationNode
    {
        $node = new RelationNode();
        $node->name = $name;
        $node->allRecursive = $allRecursive;
        $node->recursiveDepth = $recursiveDepth;

        return $node;
    }

    public static function parse(string $expression): RelationNode
    {
        static $expressionCache = [];

        if(isset($expressionCache[$expression])) return clone $expressionCache[$expression];

        $parentNode = new RelationNode();

        $iChildNodes = self::parseRelationQuery($expression, $parentNode);

        $parentNode->iChildNodes = $iChildNodes;

        return $parentNode;
    }

    /**
     * 2023-06-19
     * @return array|null
     */
    public static function parseRelationQuery(string $case): ?array
    {
        // $regex = '/(\w+)\.?(?<R>\[(?:[^\[\]]+|(?&R))*\])?/';
        $regex = '/(\w+)\.?(\[(?:[^\[\]]+|(?R))*\]|(?R))?/';
        
        preg_match_all($regex, $case, $m);
        
        $topLevelGroups = array_shift($m);
        $topLevelNames = array_shift($m);
        $recursiveGroups = array_shift($m);
        
        $groupsToProcess = (count($topLevelGroups) > 1)
            ? $topLevelGroups
            : $recursiveGroups;
        
        if(count($groupsToProcess) === 0) return null;
        
        $isArray = ($case[0] === '[');
        
        $iRelationNodes = [];
        foreach(array_combine($topLevelNames, $groupsToProcess) as $name=>$caseToProcess)
        {
            $node = new RelationNode();
            $node->name = $name;

            $node->iChildNodes = self::parseRelationQuery($caseToProcess);

            $iRelationNodes[$name] = $node;
        }
        
        return $iRelationNodes;
    }
}

