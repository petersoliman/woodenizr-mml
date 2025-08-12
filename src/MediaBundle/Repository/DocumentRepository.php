<?php

namespace App\MediaBundle\Repository;

use App\MediaBundle\Entity\Document;
use Doctrine\Persistence\ManagerRegistry;
use PN\MediaBundle\Repository\DocumentRepository as BaseDocumentRepository;

class DocumentRepository extends BaseDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }
}
