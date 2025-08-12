<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\SeoBundle\Entity\Seo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use PN\SeoBundle\Entity\SeoBaseRoute;
use PN\SeoBundle\Entity\SeoPage;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240924191959 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

    }

    public function postUp(Schema $schema): void
    {
        $this->newSeo('Brands', 'Brands', 'brands', 'brands');
    }

    private function newSeo($seoPageTitle, $seoTitle, $seoPageType, $seoSlug): void
    {
        //getting entity manager
        $em = $this->container->get('doctrine.orm.entity_manager');

        $seoPage = new SeoPage();
        $seoPage->setTitle($seoPageTitle);
        $seoPage->setType($seoPageType);
        $seoPage->setCreated(new \DateTime());
        $seoPage->setCreator('System');
        $seoPage->setModified(new \DateTime());
        $seoPage->setModifiedBy('System');

        $seo = new Seo();
        $seo->setSeoBaseRoute($em->getRepository(SeoBaseRoute::class)->findByEntity($seoPage));
        $seo->setTitle($seoTitle);
        $seo->setSlug($seoSlug);
        $seo->setState(1);
        $seo->setLastModified(new \DateTime());

        $seoPage->setSeo($seo);

        $em->persist($seoPage);
        $em->persist($seo);

        $em->flush();
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
