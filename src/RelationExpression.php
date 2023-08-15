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

    public function __construct(?RelationNode $node=null)
    {
        $this->node = $node ?? self::newNode();
    }

    /**
     * @param string|RelationExpression|null $expression
     * @return RelationExpression
     */
    public static function create($expression=null, array $options=[]): self
    {
        if($expression === null) return new static();

        if($expression instanceof RelationExpression) return $expression;

        if(is_string($expression)) return new static(self::parse($expression, $options));

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
                    $childNodes[$name] = self::mergeNodes($childNode1Map[$name], $childNode2Map[$name]);
                }
                else
                {
                    $childNodes[$name] = isset($childNode1Map[$name])
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

    public static function parse(string $expression, array $options=[]): RelationNode
    {
        static $expressionCache = [];

        if(isset($expressionCache[$expression])) return clone $expressionCache[$expression];

        $parentNode = self::newNode();

        $iChildNodes = self::parseRelationQuery($expression, $options);

        $parentNode->iChildNodes = $iChildNodes;

        return $parentNode;
    }

    /**
     * 2023-06-19
     * @return array|null
     */
    public static function parseRelationQuery(string $case, array $options=[]): ?array
    {
        // $regex = '/(\w+)\.?(?<R>\[(?:[^\[\]]+|(?&R))*\])?/';
        $regex = '/(\w+)\.?(\[(?:[^\[\]]+|(?R))*\]|(?R))?/';

        $getAlias = function(string $relationName) use ($options)
        {
            $aliases = $options['alias'] ?? [];

            if(is_array($aliases))
            {
                return $aliases[$relationName] ?? null;
            }

            return $aliases;
        };

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
            $node->relationName = $name;
            $node->name = $getAlias($name) ?? $node->relationName;


            $node->iChildNodes = self::parseRelationQuery($caseToProcess);

            $iRelationNodes[$name] = $node;
        }
        
        return $iRelationNodes;
    }

    private static function findExpressionAtPath(RelationExpression $target, RelationExpression $path, &$results)
    {
        if($path->isEmpty())
        {
            // Path leaf reached, add target node to result set.
            $results[] = $target;
            return $results;
        }

        foreach($this->node->iChildNodes as $iChildNode)
        {
            $pathChild = $path->childExpression($iChildNode->name);
            $targetChild = $target->childExpression($iChildNode->name);

            if($targetChild !== null) self::findExpressionAtPath($targetChild, $pathChild, $results);
        }

        return $results;
    }

    public function expressionAtPath(RelationExpression $path): array
    {
        $results = [];
        self::findExpressionAtPath($this, $path, $results);

        return $results;
    }

    private function _forEachChildExpression(string $modelClass, \Closure $callback)
    {
        $maxRecursiveDepth = $this->getMaxRecursiveDepth();

        if($this->node->allRecursive)
        {
            foreach($modelClass::getRelationNames() as $relationName)
            {
                $node = self::newNode($relationName, true);
                $relation = $modelClass::getRelationUnsafe($relationName);
                $iChildExpression = new RelationExpression($node);

                $callback($iChildExpression, $relation);
            }
        }
        else if($this->recursiveDepth < $maxRecursiveDepth - 1)
        {
            $relation = $modelClass::getRelationUnsafe($this->node->name);
            $iChildExpression = new RelationExpression($this->node, $this->recursiveDepth + 1);

            $callback($iChildExpression, $relation);
        }
        else if($maxRecursiveDepth === 0)
        {
            foreach($this->node->iChildNodes as $iChildNode)
            {
                $relation = $modelClass::getRelationUnsafe($iChildNode->name);

                if($relation === null) throw new RelationDoesNotExistError($modelClass, $iChildNode->name);

                $iChildExpression = new RelationExpression($iChildNode);

                $callback($iChildExpression, $relation);
            }
        }
    }

    public static function forEachChildExpression(RelationExpression $iRelationExpression, string $modelClass, \Closure $callback)
    {
        $ID_LENGTH_LIMIT = 63;
        $RELATION_RECURSION_LIMIT = 64;

        if($iRelationExpression->node->allRecursive || $iRelationExpression->getMaxRecursiveDepth() > $RELATION_RECURSION_LIMIT)
        {
            throw new \Exception('recursion depth of eager expression ${expr.toString()} too big for JoinEagerAlgorithm');
        }

        $iNode = $iRelationExpression->getNode();

        foreach($iNode->iChildNodes ?? [] as $iChildNode)
        {
            if($iChildNode->allRecursive)
            {
                foreach($modelClass::getRelationNames() as $relationName)
                {
                    $node = self::newNode($relationName, true);
                    $relation = $modelClass::getRelationUnsafe($relationName);
                    $iChildExpression = new RelationExpression($node);

                    $callback($iChildExpression, $relation);
                }
            }
            else if($iChildNode->recursiveDepth < $RELATION_RECURSION_LIMIT - 1)
            {
                // $relation = $modelClass::getRelationUnsafe($iChildNode->relationName);
                $relation = $modelClass::getRelationUnsafe($iChildNode->relationName);
                $iChildExpression = new RelationExpression($iChildNode, $iChildNode->recursiveDepth + 1);

                $callback($iChildExpression, $relation);
            }
            else if($RELATION_RECURSION_LIMIT === 0)
            {
                $childNames = array_keys($iChildNode->iChildNodes);

                foreach($childNames as $childName)
                {
                    $node = $iChildNode->iChildNodes[$childName];
                    $relation = $modelClass::getRelationUnsafe($childName);

                    if($relation === null) throw new RelationDoesNotExistError($modelClass, $childName);

                    $iChildExpression = new RelationExpression($node->relationName);

                    $callback($iChildExpression, $relation);
                }
            }
        }

    }
}

