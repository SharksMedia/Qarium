<?php

declare(strict_types=1);

namespace Sharksmedia\Qarium;

use Sharksmedia\Qarium\RelationExpression;
use Sharksmedia\Qarium\RelationNode;

// 2023-07-12

class TableTree
{
    /**
     * 2023-07-12
     * @var RelationExpression
     */
    private RelationExpression $iRelationExpression;

    /**
     * 2023-07-12
     * @var string
     */
    private string $rootModelClass;

    /**
     * 2023-07-12
     * @var string|null
     */
    private ?string $rootTableAlias;

    /**
     * 2023-07-12
     * @var array
     */
    private array $options;

    /**
     * 2023-07-12
     * @var array<int, TableNode>
     */
    private array $iTableNodes = [];
    // private array $iRelationNodes = [];

    /**
     * 2023-07-12
     * @var array<string, RelationNode>
     */
    private array $nodesByAlias = [];

    /**
     * 2023-07-12
     * @var int
     */
    private int $uidCounter = 0;

    public function __construct(RelationExpression $iRelationExpression, string $rootModelClass, ?string $rootTableAlias, array $options)
    {
        $this->iRelationExpression = $iRelationExpression;
        $this->rootModelClass = $rootModelClass;
        $this->rootTableAlias = $rootTableAlias;
        $this->options = $options;

        $this->createNodes($iRelationExpression, $rootModelClass);
    }

    public static function create(RelationExpression $iRelationExpression, string $rootModelClass, ?string $rootTableAlias, array $options): self
    {
        return new self($iRelationExpression, $rootModelClass, $rootTableAlias, $options);
    }

    public function getRootNode(): TableNode
    {
        return $this->iTableNodes[0];
    }

    public function getNodes(): array
    {
        // return $this->iRelationNodes;
        return $this->iTableNodes;
    }

    public function getRootTableAlias(): string
    {
        return $this->rootTableAlias;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function createNextUid(): int
    {
        return $this->uidCounter++;
    }

    private function createNodes(RelationExpression $iRelationExpression, string $modelClass): void
    {
        $rootNode = $this->createRootNode($iRelationExpression, $modelClass);
        $this->createChildNodes($iRelationExpression, $modelClass, $rootNode);

        foreach($this->iTableNodes as $iTableNode) $this->nodesByAlias[$iTableNode->getAlias()] = $iTableNode;
    }

    private function createChildNodes(RelationExpression $iRelationExpression, string $modelClass, TableNode $iParentTableNode)
    {
        RelationExpression::forEachChildExpression($iRelationExpression, $modelClass, function(RelationExpression $iChildRelationExpression, Relations\Relation $iRelation) use($iParentTableNode)
        {
            $iTableNode = TableNode::create($this, $iRelation->getRelatedModelClass(), $iChildRelationExpression, $iParentTableNode, $iRelation);

            $this->iTableNodes[] = $iTableNode;

            $this->createChildNodes($iChildRelationExpression, $iRelation->getRelatedModelClass(), $iTableNode);
        });
    }

    private function createRootNode(RelationExpression $iRelationExpression, string $modelClass): TableNode
    {
        $iRootTableNode = TableNode::create($this, $modelClass, $iRelationExpression);
        $this->iTableNodes[] = $iRootTableNode;

        return $iRootTableNode;
    }

	public function getNodeForColumnAlias($columnAlias)
	{
    	$lastSepIndex = strrpos($columnAlias, $this->options['separator']);

    	if($lastSepIndex === false)
		{
        	return $this->getRootNode();
    	}
		else
		{
        	$tableAlias = substr($columnAlias, 0, $lastSepIndex);
        	return $this->nodesByAlias[$tableAlias];
    	}
	}
}
