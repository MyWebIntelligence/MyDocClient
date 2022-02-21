<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Project;
use XMLWriter;

class Gexf
{

    private XMLWriter $xml;
    private Project $project;

    /**
     * @var Document[]
     */
    private array $nodes = [];

    /**
     * @var array
     */
    private array $edges = [];

    /**
     * @param $project
     * @param Document[] $documents
     */
    public function __construct($project, array $documents)
    {
        $this->xml = new XMLWriter();
        $this->project = $project;

        foreach ($documents as $document) {
            $this->nodes[] = $document;

            foreach ($document->getSourceOf() as $link) {
                $target = $link->getTarget();

                if ($target) {
                    if (!in_array($target, $this->nodes, true)) {
                        $this->nodes[] = $target;
                    }

                    $this->edges[] = [
                        'source' => $document->getId(),
                        'target' => $target->getId(),
                    ];
                }
            }

        }
    }

    public function toXml(): string
    {
        // Header
        $this->xml->openMemory();
        $this->xml->startDocument('1.0', 'utf-8');
        $this->xml->startElement('gexf');
        $this->xml->startAttribute('xmlns');
        $this->xml->text('http://www.gexf.net/1.2');
        $this->xml->endAttribute();
        $this->xml->startAttribute('version');
        $this->xml->text('1.2');

        $this->addMeta();

        $this->startGraph();
        $this->addNodes();
        $this->addEdges();
        $this->xml->endElement(); // graph

        $this->xml->endElement(); // gexf
        $this->xml->endDocument();
        return $this->xml->outputMemory();
    }

    private function addMeta(): void
    {
        $this->xml->startElement('meta');
        $this->xml->startElement('creator');
        $this->xml->text('My Doc Client');
        $this->xml->endElement();

        $this->xml->startElement('description');
        $this->xml->text($this->project->getDescription());
        $this->xml->endElement();
        $this->xml->endElement();
    }

    private function startGraph(): void
    {
        $this->xml->startElement('graph');
        $this->xml->startAttribute('mode');
        $this->xml->text('static');
        $this->xml->endAttribute();
        $this->xml->startAttribute('defaultedgetype');
        $this->xml->text('directed');
        $this->xml->endAttribute();
    }


    private function addNodes(): void
    {
        $this->xml->startElement('nodes');

        foreach ($this->nodes as $node) {
            $this->addNode($node);
        }

        $this->xml->endElement(); // nodes
    }

    private function addNode(Document $document): void
    {
        $this->xml->startElement('node');
        $this->xml->startAttribute('id');
        $this->xml->text($document->getId());
        $this->xml->endAttribute();
        $this->xml->startAttribute('label');
        $this->xml->text($document->getTitle());
        $this->xml->endAttribute();
        $this->xml->endElement();
    }

    private function addEdges(): void
    {
        $this->xml->startElement('edges');

        foreach ($this->edges as $i => $edge) {
            $this->addEdge($i, $edge);
        }

        $this->xml->endElement(); // edges
    }

    private function addEdge($id, $edge): void
    {
        $this->xml->startElement('edge');
        $this->xml->startAttribute('id');
        $this->xml->text($id);
        $this->xml->endAttribute();
        $this->xml->startAttribute('source');
        $this->xml->text($edge['source']);
        $this->xml->endAttribute();
        $this->xml->startAttribute('target');
        $this->xml->text($edge['target']);
        $this->xml->endAttribute();
        $this->xml->endElement();
    }
}